<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\DTOs\ResetPasswordDTO;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

/**
 * ForgotPasswordController
 *
 * Handles the "Forgot Password" and "Reset Password" flows.
 *
 * Rate-limiting for the send-reset-link endpoint is implemented via a
 * Redis sliding-window counter keyed on the client IP address.
 */
final class ForgotPasswordController
{
    /** Max reset-link requests per IP per sliding window. */
    private const RATE_LIMIT_MAX = 5;

    /** Rate-limit window in seconds (15 minutes). */
    private const RATE_LIMIT_WINDOW = 900;

    public function __construct(
        private readonly AuthService $authService,
        private readonly \Predis\Client $redis,
        private readonly array $appConfig,
    ) {}

    // -------------------------------------------------------------------------
    // Forgot password form
    // -------------------------------------------------------------------------

    /**
     * Display the "Forgot Password" form.
     */
    public function showForm(): Response
    {
        return Response::make($this->renderView('auth/forgot-password', [
            'title'     => 'Forgot Password — ' . ($this->appConfig['name'] ?? 'BizCore ERP'),
            'success'   => $this->flashGet('success'),
            'errors'    => $this->flashGet('errors', []),
            'old'       => $this->flashGet('old', []),
            'csrfToken' => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Send reset link (rate-limited)
    // -------------------------------------------------------------------------

    /**
     * Validate the submitted email and dispatch a password-reset link.
     *
     * Always responds with a success message to prevent email enumeration.
     */
    public function sendResetLink(Request $request): Response
    {
        // CSRF check.
        if (!$this->verifyCsrfToken($request)) {
            $this->flashSet('errors', ['form' => ['Invalid security token. Please try again.']]);
            return Response::redirect('/password/forgot');
        }

        // IP-based rate limiting.
        if ($this->isIpRateLimited($request->ip())) {
            $this->flashSet('errors', ['form' => [
                'Too many requests. Please wait a few minutes before trying again.',
            ]]);
            return Response::redirect('/password/forgot');
        }

        $email = strtolower(trim((string) $request->input('email', '')));

        // Basic email validation.
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flashSet('errors', ['email' => ['Please enter a valid email address.']]);
            $this->flashSet('old', ['email' => $email]);
            return Response::redirect('/password/forgot');
        }

        // Increment rate-limit counter before dispatching (prevents rapid-fire bypass).
        $this->incrementIpRateLimit($request->ip());

        // Dispatch — intentionally swallows all exceptions so the response
        // does not vary based on whether the email exists.
        try {
            $this->authService->forgotPassword($email);
        } catch (\Throwable) {
            // Log internally but show the same success message.
        }

        $this->flashSet(
            'success',
            'If an account with that email address exists, a password reset link has been sent.',
        );

        return Response::redirect('/password/forgot');
    }

    // -------------------------------------------------------------------------
    // Show reset form
    // -------------------------------------------------------------------------

    /**
     * Display the password-reset form for the given token.
     *
     * The token is validated at submission time; we do not pre-validate here
     * to avoid a double-lookup and to prevent timing-based token discovery.
     */
    public function showResetForm(Request $request, string $token): Response
    {
        if ($token === '') {
            return Response::redirect('/password/forgot');
        }

        return Response::make($this->renderView('auth/reset-password', [
            'title'     => 'Reset Password — ' . ($this->appConfig['name'] ?? 'BizCore ERP'),
            'token'     => htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'email'     => htmlspecialchars((string) $request->query('email', ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'errors'    => $this->flashGet('errors', []),
            'csrfToken' => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Process password reset
    // -------------------------------------------------------------------------

    /**
     * Validate and apply the password-reset request.
     *
     * On success, the user is redirected to the login page with a flash
     * success message. On failure, they are redirected back with errors.
     */
    public function resetPassword(Request $request): Response
    {
        // CSRF check.
        if (!$this->verifyCsrfToken($request)) {
            $this->flashSet('errors', ['form' => ['Invalid security token. Please try again.']]);
            return Response::redirect('/password/forgot');
        }

        $dto = ResetPasswordDTO::fromArray($request->all());

        // Validate passwords match before hitting the service.
        if (!$dto->passwordsMatch()) {
            $this->flashSet('errors', ['password_confirmation' => ['Passwords do not match.']]);
            return Response::redirect('/password/reset/' . rawurlencode($dto->token) . '?email=' . rawurlencode($dto->email));
        }

        try {
            $this->authService->resetPassword($dto->token, $dto->password, $dto->passwordConfirmation);
        } catch (ValidationException $e) {
            $this->flashSet('errors', $e->getErrors());
            return Response::redirect('/password/reset/' . rawurlencode($dto->token) . '?email=' . rawurlencode($dto->email));
        } catch (\Throwable $e) {
            $this->flashSet('errors', ['form' => ['An unexpected error occurred. Please try again.']]);
            return Response::redirect('/password/forgot');
        }

        $this->flashSet('success', 'Your password has been reset. You may now sign in with your new password.');

        return Response::redirect('/login');
    }

    // -------------------------------------------------------------------------
    // Rate-limiting helpers
    // -------------------------------------------------------------------------

    private function rateLimitKey(string $ip): string
    {
        return 'bizcore:pw_reset_attempts:' . hash('sha256', $ip);
    }

    private function isIpRateLimited(string $ip): bool
    {
        $count = (int) ($this->redis->get($this->rateLimitKey($ip)) ?? 0);
        return $count >= self::RATE_LIMIT_MAX;
    }

    private function incrementIpRateLimit(string $ip): void
    {
        $key = $this->rateLimitKey($ip);
        $this->redis->incr($key);
        $this->redis->expire($key, self::RATE_LIMIT_WINDOW);
    }

    // -------------------------------------------------------------------------
    // Session / flash helpers
    // -------------------------------------------------------------------------

    private function generateCsrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    private function verifyCsrfToken(Request $request): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $expected = $_SESSION['csrf_token'] ?? '';
        $provided = $request->csrfToken();

        return $expected !== '' && hash_equals($expected, $provided);
    }

    private function flashSet(string $key, mixed $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['_flash'][$key] = $value;
    }

    private function flashGet(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    // -------------------------------------------------------------------------
    // View rendering
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderView(string $view, array $data = []): string
    {
        $viewPath = dirname(__DIR__, 3) . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$viewPath}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
