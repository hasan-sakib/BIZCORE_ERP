<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Exceptions\AccountLockedException;
use App\Exceptions\AuthException;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

/**
 * LoginController
 *
 * Handles the display of the login form, credential submission,
 * and logout flows.
 */
class LoginController
{
    /** Remember-me cookie lifetime: 30 days in seconds. */
    private const REMEMBER_TTL_SECONDS = 30 * 24 * 3600;

    public function __construct(
        private readonly AuthService $authService,
        private readonly array $appConfig,
    ) {}

    // -------------------------------------------------------------------------
    // Show login form
    // -------------------------------------------------------------------------

    /**
     * Display the login form.
     * Redirects to the dashboard if the user is already authenticated.
     */
    public function showLogin(): Response
    {
        if ($this->isAuthenticated()) {
            return Response::redirect('/dashboard');
        }

        return Response::make($this->renderView('auth/login', [
            'title'         => 'Sign In — ' . ($this->appConfig['name'] ?? 'BizCore ERP'),
            'errors'        => $this->flashGet('errors', []),
            'old'           => $this->flashGet('old', []),
            'csrfToken'     => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Process login
    // -------------------------------------------------------------------------

    /**
     * Process a login form submission.
     *
     * Validates the CSRF token and input, delegates authentication to
     * AuthService, sets the session and optional remember-me cookie, then
     * redirects to the intended destination or the dashboard.
     */
    public function login(Request $request): Response
    {
        // 1. CSRF protection.
        if (!$this->verifyCsrfToken($request)) {
            $this->flashSet('errors', ['form' => ['Invalid security token. Please try again.']]);
            return Response::redirect('/login');
        }

        // 2. Validate input.
        $email    = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $remember = filter_var($request->input('remember', false), FILTER_VALIDATE_BOOLEAN);

        $errors = $this->validateLoginInput($email, $password);

        if ($errors !== []) {
            $this->flashSet('errors', $errors);
            $this->flashSet('old', ['email' => $email]);
            return Response::redirect('/login');
        }

        // 3. Attempt authentication.
        try {
            $result = $this->authService->login(
                email:    $email,
                password: $password,
                remember: $remember,
                ip:       $request->ip(),
                ua:       $request->userAgent(),
            );
        } catch (AccountLockedException $e) {
            $minutes = $e->getRemainingMinutes();
            $message = $minutes > 0
                ? "Your account is locked. Please try again in {$minutes} minute(s)."
                : 'Your account is locked. Please contact an administrator.';

            $this->flashSet('errors', ['form' => [$message]]);
            $this->flashSet('old', ['email' => $email]);
            return Response::redirect('/login');
        } catch (InvalidCredentialsException $e) {
            $this->flashSet('errors', ['form' => [$e->getMessage()]]);
            $this->flashSet('old', ['email' => $email]);
            return Response::redirect('/login');
        } catch (AuthException $e) {
            $this->flashSet('errors', ['form' => [$e->getMessage()]]);
            $this->flashSet('old', ['email' => $email]);
            return Response::redirect('/login');
        }

        // 4. Establish session.
        $this->startAuthenticatedSession($result['user']->id, $result['token']);

        // 5. Optional remember-me cookie (stores the refresh token).
        $response = Response::redirect($this->intendedUrl('/dashboard'));

        if ($remember) {
            $response->withCookie(
                name:     'bizcore_remember',
                value:    $result['refreshToken'],
                expires:  time() + self::REMEMBER_TTL_SECONDS,
                path:     '/',
                domain:   '',
                secure:   true,
                httpOnly: true,
                sameSite: 'Lax',
            );
        }

        return $response;
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    /**
     * Destroy the current session and redirect to the login page.
     */
    public function logout(Request $request): Response
    {
        $userId = $this->getAuthenticatedUserId();

        if ($userId !== null) {
            $this->authService->logout($userId);
        }

        // Destroy PHP session.
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        // Expire the remember-me cookie.
        $response = Response::redirect('/login');
        $response->withCookie(
            name:    'bizcore_remember',
            value:   '',
            expires: time() - 3600,
            path:    '/',
        );

        return $response;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Validate the raw login form input.
     *
     * @return array<string, string[]>
     */
    private function validateLoginInput(string $email, string $password): array
    {
        $errors = [];

        if ($email === '') {
            $errors['email'][] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }

        return $errors;
    }

    /**
     * Start an authenticated PHP session for the given user.
     */
    private function startAuthenticatedSession(int $userId, string $token): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Regenerate session ID to prevent session fixation.
        session_regenerate_id(true);

        $_SESSION['auth_user_id'] = $userId;
        $_SESSION['auth_token']   = $token;
        $_SESSION['auth_at']      = time();
    }

    /**
     * Returns the authenticated user's ID from the session, or null.
     */
    private function getAuthenticatedUserId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return isset($_SESSION['auth_user_id']) ? (int) $_SESSION['auth_user_id'] : null;
    }

    /**
     * Returns true when the current session has an authenticated user.
     */
    private function isAuthenticated(): bool
    {
        return $this->getAuthenticatedUserId() !== null;
    }

    /**
     * Generate and store a CSRF token in the session, returning it.
     */
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

    /**
     * Verify the CSRF token from the request matches the one in the session.
     */
    private function verifyCsrfToken(Request $request): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $expected = $_SESSION['_csrf_token'] ?? '';
        $provided = $request->csrfToken();

        return $expected !== '' && hash_equals($expected, $provided);
    }

    /**
     * Return the URL the user originally intended to visit,
     * defaulting to the provided fallback.
     */
    private function intendedUrl(string $default = '/'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $intended = $_SESSION['intended_url'] ?? null;
        unset($_SESSION['intended_url']);

        return is_string($intended) && $intended !== '' ? $intended : $default;
    }

    /**
     * Store a value in the session flash bag.
     */
    private function flashSet(string $key, mixed $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Retrieve and clear a value from the session flash bag.
     */
    private function flashGet(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Render a view file and return the resulting HTML string.
     *
     * @param  array<string, mixed>  $data
     */
    private function renderView(string $view, array $data = []): string
    {
        $viewPath = dirname(__DIR__, 3) . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$viewPath}");
        }

        $session     = session();
        $currentUser = null;
        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
