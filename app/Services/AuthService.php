<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\ResetPasswordDTO;
use App\Entities\User;
use App\Entities\UserStatus;
use App\Exceptions\AccountLockedException;
use App\Exceptions\AuthException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use DateTime;
use DateInterval;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Predis\Client as RedisClient;
use Ramsey\Uuid\Uuid;

/**
 * AuthService
 *
 * Handles all authentication flows: login, logout, password reset,
 * token refresh, and password policy enforcement.
 */
final class AuthService
{
    /** Maximum failed login attempts before account lock. */
    private const MAX_ATTEMPTS = 5;

    /** Lock duration in seconds (15 minutes). */
    private const LOCK_DURATION_SECONDS = 900;

    /** Reset token TTL in seconds (60 minutes). */
    private const RESET_TOKEN_TTL_SECONDS = 3600;

    /** bcrypt cost factor. */
    private const BCRYPT_COST = 12;

    /** Password history depth (no re-use of last N passwords). */
    private const PASSWORD_HISTORY_DEPTH = 5;

    /** Minimum password length. */
    private const PASSWORD_MIN_LENGTH = 8;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MailService $mailService,
        private readonly RedisClient $redis,
        private readonly array $jwtConfig,
        private readonly array $appConfig,
    ) {}

    // -------------------------------------------------------------------------
    // Login / Logout
    // -------------------------------------------------------------------------

    /**
     * Authenticate a user and issue an access + refresh token pair.
     *
     * @return array{user: User, token: string, refreshToken: string, expiresIn: int}
     *
     * @throws InvalidCredentialsException  Wrong email or password.
     * @throws AccountLockedException       Account is locked.
     * @throws AuthException                Account is inactive.
     */
    public function login(string $email, string $password, bool $remember, string $ip = '', string $ua = ''): array
    {
        // 1. Rate-limit check (IP-based, Redis sliding window).
        $this->checkIpRateLimit($ip);

        // 2. Resolve user — use a timing-safe path even on miss.
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            // Perform a dummy verify to prevent timing attacks on enumeration.
            password_verify($password, '$2y$12$invalidhashplaceholderXXXXXXXXXXXXXXXXXXXXXX');
            $this->incrementIpRateLimit($ip);
            throw new InvalidCredentialsException();
        }

        // 3. Account lock check.
        if ($user->isLocked()) {
            $this->userRepository->logLoginAttempt($user->id, $ip, $ua, 'locked', 'account_locked');
            throw new AccountLockedException($user->lockedUntil);
        }

        // 4. Inactive account check.
        if ($user->status === UserStatus::Inactive) {
            throw new AuthException('Your account is inactive. Please contact an administrator.');
        }

        // 5. Password verification.
        $passwordRow = $this->fetchPasswordHash($user->id);

        if (!password_verify($password, $passwordRow)) {
            $this->handleFailedAttempt($user, $ip, $ua);
            throw new InvalidCredentialsException();
        }

        // 6. Rehash if bcrypt cost has changed.
        if (password_needs_rehash($passwordRow, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST])) {
            $newHash = $this->hashPassword($password);
            $this->userRepository->update($user->id, ['password' => $newHash]);
        }

        // 7. Reset failed attempts and record successful login.
        $this->userRepository->updateLoginAttempts($user->id, 0);
        $this->userRepository->updateLastLogin($user->id);
        $this->userRepository->logLoginAttempt($user->id, $ip, $ua, 'success', null);
        $this->resetIpRateLimit($ip);

        // 8. Issue tokens.
        [$token, $refreshToken, $expiresIn] = $this->issueTokens($user, $remember);

        return [
            'user'         => $user,
            'token'        => $token,
            'refreshToken' => $refreshToken,
            'expiresIn'    => $expiresIn,
        ];
    }

    /**
     * Revoke all sessions for the given user (web logout).
     */
    public function logout(int $userId): void
    {
        $this->userRepository->revokeAllSessions($userId);
    }

    // -------------------------------------------------------------------------
    // Forgot / Reset password
    // -------------------------------------------------------------------------

    /**
     * Generate a password-reset token and dispatch the reset email.
     * Always returns successfully to avoid email enumeration.
     */
    public function forgotPassword(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || $user->status === UserStatus::Inactive) {
            // Intentional no-op: do not reveal whether email exists.
            return;
        }

        $plainToken = bin2hex(random_bytes(32));   // 64 hex chars
        $tokenHash  = hash('sha256', $plainToken);
        $expiresAt  = (new DateTime())->add(new DateInterval('PT' . self::RESET_TOKEN_TTL_SECONDS . 'S'));

        $this->userRepository->upsertPasswordResetToken($user->id, $tokenHash, $expiresAt);

        $resetUrl = rtrim($this->appConfig['url'], '/') . '/password/reset/' . $plainToken;

        $this->mailService->send(
            to:       $user->email,
            template: 'auth/password-reset',
            data:     [
                'user'      => $user,
                'resetUrl'  => $resetUrl,
                'expiresAt' => $expiresAt,
            ],
        );
    }

    /**
     * Complete a password reset using the plain-text token from the email.
     *
     * @throws ValidationException   Token is invalid/expired, passwords mismatch, or new
     *                               password fails policy or history checks.
     */
    public function resetPassword(string $token, string $password, string $passwordConfirmation): bool
    {
        // 1. Confirm passwords match.
        if ($password !== $passwordConfirmation) {
            throw new ValidationException(['password_confirmation' => ['Passwords do not match.']]);
        }

        // 2. Validate password strength.
        $errors = $this->validatePasswordStrength($password);
        if ($errors !== []) {
            throw new ValidationException(['password' => $errors]);
        }

        // 3. Look up the token.
        $tokenHash  = hash('sha256', $token);
        $tokenRow   = $this->userRepository->findValidPasswordResetToken($tokenHash);

        if ($tokenRow === null) {
            throw new ValidationException(['token' => ['This password reset link is invalid or has expired.']]);
        }

        $userId = (int) $tokenRow['user_id'];

        // 4. Password history check.
        $this->assertNotInHistory($userId, $password);

        // 5. Hash and store new password.
        $newHash = $this->hashPassword($password);
        $this->userRepository->update($userId, ['password' => $newHash]);
        $this->userRepository->savePasswordHistory($userId, $newHash);

        // 6. Invalidate the token and all existing sessions.
        $this->userRepository->markTokenUsed($tokenHash);
        $this->userRepository->revokeAllSessions($userId);

        return true;
    }

    // -------------------------------------------------------------------------
    // Token refresh
    // -------------------------------------------------------------------------

    /**
     * Issue a fresh access token in exchange for a valid refresh token.
     *
     * @return array{user: User, token: string, refreshToken: string, expiresIn: int}
     *
     * @throws AuthException  Refresh token is invalid, expired, or revoked.
     */
    public function refreshToken(string $refreshToken): array
    {
        $sessionRow = $this->userRepository->findSession($refreshToken);

        if ($sessionRow === null) {
            throw new AuthException('Invalid or expired refresh token.');
        }

        $user = $this->userRepository->findById((int) $sessionRow['user_id']);

        if ($user === null || !$user->isActive()) {
            throw new AuthException('User account is no longer active.');
        }

        // Single-use refresh token: revoke old, issue new pair.
        $this->userRepository->revokeSession($refreshToken);

        [$newToken, $newRefreshToken, $expiresIn] = $this->issueTokens($user, false);

        return [
            'user'         => $user,
            'token'        => $newToken,
            'refreshToken' => $newRefreshToken,
            'expiresIn'    => $expiresIn,
        ];
    }

    // -------------------------------------------------------------------------
    // Password policy
    // -------------------------------------------------------------------------

    /**
     * Validate a candidate password against the configured policy rules.
     *
     * @return string[]  A (possibly empty) list of human-readable error messages.
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters long.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        if (!preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&*…).';
        }

        return $errors;
    }

    /**
     * Change the password for an authenticated user after verifying the current one.
     *
     * @throws InvalidCredentialsException  Current password is wrong.
     * @throws ValidationException          New password fails policy or history check.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $user    = $this->userRepository->findById($userId);
        $curHash = $this->fetchPasswordHash($userId);

        if ($user === null || !password_verify($currentPassword, $curHash)) {
            throw new InvalidCredentialsException('Your current password is incorrect.');
        }

        $errors = $this->validatePasswordStrength($newPassword);
        if ($errors !== []) {
            throw new ValidationException(['password' => $errors]);
        }

        $this->assertNotInHistory($userId, $newPassword);

        $newHash = $this->hashPassword($newPassword);
        $this->userRepository->update($userId, ['password' => $newHash]);
        $this->userRepository->savePasswordHistory($userId, $newHash);

        // Invalidate all other sessions on password change.
        $this->userRepository->revokeAllSessions($userId);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Issue a JWT access token and a refresh token for the given user.
     *
     * @return array{0: string, 1: string, 2: int}  [jwtToken, refreshToken, ttlSeconds]
     */
    private function issueTokens(User $user, bool $remember): array
    {
        $now      = time();
        $ttl      = (int) ($this->jwtConfig['ttl'] ?? 60) * 60;   // minutes → seconds
        $jti      = Uuid::uuid4()->toString();

        $payload = [
            'iss' => $this->jwtConfig['issuer'] ?? '',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'jti' => $jti,
            'sub' => (string) $user->id,
            'uid' => $user->id,
            'bid' => $user->branchId,
            'rol' => $user->roleId,
            'sid' => Uuid::uuid4()->toString(),
        ];

        $algo  = $this->jwtConfig['algo'] ?? 'HS256';
        $secret = $this->jwtConfig['secret'] ?? '';

        $accessToken  = JWT::encode($payload, $secret, $algo);
        $refreshToken = bin2hex(random_bytes(64));   // 128 hex chars, stored in DB

        $refreshTtl   = $remember
            ? (int) ($this->jwtConfig['refresh_ttl'] ?? 20160) * 60
            : (int) ($this->jwtConfig['ttl'] ?? 60) * 60 * 8;    // 8-hour session TTL

        $expiresAt = (new DateTime())->add(new DateInterval('PT' . $refreshTtl . 'S'));

        $this->userRepository->createSession(
            userId:       $user->id,
            sessionToken: $jti,
            refreshToken: $refreshToken,
            ip:           '',
            ua:           '',
            expiresAt:    $expiresAt,
        );

        return [$accessToken, $refreshToken, $ttl];
    }

    /**
     * Handle a failed login attempt: increment counter, lock after threshold.
     */
    private function handleFailedAttempt(User $user, string $ip, string $ua): void
    {
        $attempts = $user->failedLoginAttempts + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockedUntil = (new DateTime())->add(
                new DateInterval('PT' . self::LOCK_DURATION_SECONDS . 'S'),
            );
            $this->userRepository->lockUser($user->id, $lockedUntil);
            $this->userRepository->logLoginAttempt($user->id, $ip, $ua, 'locked', 'max_attempts_exceeded');
            $this->incrementIpRateLimit($ip);
            throw new AccountLockedException($lockedUntil);
        }

        $this->userRepository->updateLoginAttempts($user->id, $attempts);
        $this->userRepository->logLoginAttempt($user->id, $ip, $ua, 'failed', 'invalid_password');
        $this->incrementIpRateLimit($ip);
    }

    /**
     * Throw a ValidationException when the candidate password matches any of the
     * last N stored hashes for the user.
     *
     * @throws ValidationException
     */
    private function assertNotInHistory(int $userId, string $candidatePassword): void
    {
        $history = $this->userRepository->getPasswordHistory($userId, self::PASSWORD_HISTORY_DEPTH);

        foreach ($history as $oldHash) {
            if (password_verify($candidatePassword, $oldHash)) {
                throw new ValidationException([
                    'password' => [
                        sprintf(
                            'You cannot reuse any of your last %d passwords.',
                            self::PASSWORD_HISTORY_DEPTH,
                        ),
                    ],
                ]);
            }
        }
    }

    /**
     * Hash a plain-text password using bcrypt.
     */
    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
    }

    /**
     * Fetch only the password column for a user (avoids hydrating the full entity).
     */
    private function fetchPasswordHash(int $userId): string
    {
        // Direct PDO call via the repository's protected pdo property would require
        // a dedicated method. We expose a lightweight helper on the repository instead.
        return $this->userRepository->getPasswordHash($userId) ?? '';
    }

    // -------------------------------------------------------------------------
    // IP rate-limiting via Redis
    // -------------------------------------------------------------------------

    /** Redis key for the per-IP attempt counter. */
    private function ipRateLimitKey(string $ip): string
    {
        return 'bizcore:login_attempts:ip:' . hash('sha256', $ip);
    }

    private function checkIpRateLimit(string $ip): void
    {
        if ($ip === '') {
            return;
        }

        $count = (int) ($this->redis->get($this->ipRateLimitKey($ip)) ?? 0);

        $ipMax = (int) (config('auth.lockout.ip_max_attempts') ?? 20);

        if ($count >= $ipMax) {
            throw new AccountLockedException(
                null,
                'Too many login attempts from your IP address. Please try again later.',
            );
        }
    }

    private function incrementIpRateLimit(string $ip): void
    {
        if ($ip === '') {
            return;
        }

        $key = $this->ipRateLimitKey($ip);
        $this->redis->incr($key);
        $this->redis->expire($key, self::LOCK_DURATION_SECONDS);
    }

    private function resetIpRateLimit(string $ip): void
    {
        if ($ip === '') {
            return;
        }

        $this->redis->del($this->ipRateLimitKey($ip));
    }
}
