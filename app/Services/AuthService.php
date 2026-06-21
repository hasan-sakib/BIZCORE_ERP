<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\LoginHistory;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    private const MAX_ATTEMPTS       = 5;
    private const LOCK_SECONDS       = 900;   // 15 min
    private const HISTORY_DEPTH      = 5;
    private const PASSWORD_MIN_LEN   = 8;

    public function __construct(private readonly MailService $mailService) {}

    public function attemptLogin(string $email, string $password, string $ip = '', string $ua = ''): User
    {
        $this->checkIpRateLimit($ip);

        $user = User::where('email', $email)->with('role')->first();

        if ($user === null) {
            password_verify($password, '$2y$12$dummyhashXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
            $this->incrementIpRateLimit($ip);
            throw new \App\Exceptions\InvalidCredentialsException();
        }

        if ($user->isLocked()) {
            $this->recordLoginHistory($user->id, $ip, $ua, 'failed', 'account_locked');
            throw new \App\Exceptions\AccountLockedException($user->locked_until);
        }

        if ($user->status === UserStatus::Inactive) {
            throw new \App\Exceptions\AuthException('Your account is inactive. Please contact an administrator.');
        }

        if (!Hash::check($password, $user->password)) {
            $this->handleFailedAttempt($user, $ip, $ua);
            throw new \App\Exceptions\InvalidCredentialsException();
        }

        if (Hash::needsRehash($user->password)) {
            $user->update(['password' => Hash::make($password)]);
        }

        $user->update(['failed_login_attempts' => 0, 'last_login_at' => now(), 'last_login_ip' => $ip]);
        $this->recordLoginHistory($user->id, $ip, $ua, 'success');
        $this->resetIpRateLimit($ip);

        Cache::forget("auth_user_{$user->id}");

        return $user;
    }

    public function forgotPassword(string $email): void
    {
        $user = User::where('email', $email)->where('status', UserStatus::Active)->first();

        if ($user === null) {
            return;
        }

        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(string $token, string $email, string $password, string $confirmation): bool
    {
        if ($password !== $confirmation) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json(['errors' => ['password_confirmation' => ['Passwords do not match.']]], 422)
            );
        }

        $errors = $this->validatePasswordStrength($password);
        if ($errors !== []) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json(['errors' => ['password' => $errors]], 422)
            );
        }

        $status = \Illuminate\Support\Facades\Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $confirmation, 'token' => $token],
            function (User $user, string $newPassword) {
                $this->assertNotInHistory($user->id, $newPassword);
                $hash = Hash::make($newPassword);
                $user->update(['password' => $hash]);
                $this->savePasswordHistory($user->id, $hash);
                Cache::forget("auth_user_{$user->id}");
            }
        );

        return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $user = User::findOrFail($userId);

        if (!Hash::check($currentPassword, $user->password)) {
            throw new \App\Exceptions\InvalidCredentialsException('Your current password is incorrect.');
        }

        $errors = $this->validatePasswordStrength($newPassword);
        if ($errors !== []) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json(['errors' => ['password' => $errors]], 422)
            );
        }

        $this->assertNotInHistory($userId, $newPassword);
        $hash = Hash::make($newPassword);
        $user->update(['password' => $hash]);
        $this->savePasswordHistory($userId, $hash);
        Cache::forget("auth_user_{$userId}");
    }

    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (mb_strlen($password) < self::PASSWORD_MIN_LEN) {
            $errors[] = 'Password must be at least ' . self::PASSWORD_MIN_LEN . ' characters.';
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
            $errors[] = 'Password must contain at least one special character.';
        }

        return $errors;
    }

    private function handleFailedAttempt(User $user, string $ip, string $ua): void
    {
        $attempts = $user->failed_login_attempts + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockedUntil = now()->addSeconds(self::LOCK_SECONDS);
            $user->update(['failed_login_attempts' => $attempts, 'locked_until' => $lockedUntil]);
            $this->recordLoginHistory($user->id, $ip, $ua, 'failed', 'max_attempts_exceeded');
            $this->incrementIpRateLimit($ip);
            throw new \App\Exceptions\AccountLockedException($lockedUntil);
        }

        $user->update(['failed_login_attempts' => $attempts]);
        $this->recordLoginHistory($user->id, $ip, $ua, 'failed', 'invalid_password');
        $this->incrementIpRateLimit($ip);
    }

    private function assertNotInHistory(int $userId, string $password): void
    {
        $history = PasswordHistory::where('user_id', $userId)
            ->latest('created_at')
            ->limit(self::HISTORY_DEPTH)
            ->pluck('password');

        foreach ($history as $oldHash) {
            if (Hash::check($password, $oldHash)) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    response()->json(['errors' => ['password' => [
                        sprintf('You cannot reuse any of your last %d passwords.', self::HISTORY_DEPTH),
                    ]]], 422)
                );
            }
        }
    }

    private function savePasswordHistory(int $userId, string $hash): void
    {
        PasswordHistory::create(['user_id' => $userId, 'password' => $hash, 'created_at' => now()]);

        PasswordHistory::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->skip(self::HISTORY_DEPTH)
            ->get()
            ->each->delete();
    }

    private function recordLoginHistory(int $userId, string $ip, string $ua, string $status, ?string $reason = null): void
    {
        LoginHistory::create([
            'user_id'        => $userId,
            'ip_address'     => $ip,
            'user_agent'     => $ua,
            'status'         => $status,
            'failure_reason' => $reason,
        ]);
    }

    private function ipRateLimitKey(string $ip): string
    {
        return 'bizcore:login_attempts:ip:' . hash('sha256', $ip);
    }

    private function checkIpRateLimit(string $ip): void
    {
        if ($ip === '') {
            return;
        }
        $count = (int) Cache::get($this->ipRateLimitKey($ip), 0);
        if ($count >= 20) {
            throw new \App\Exceptions\AccountLockedException(
                null,
                'Too many login attempts from your IP. Please try again later.'
            );
        }
    }

    private function incrementIpRateLimit(string $ip): void
    {
        if ($ip === '') {
            return;
        }
        $key = $this->ipRateLimitKey($ip);
        Cache::increment($key);
        Cache::put($key, Cache::get($key, 1), self::LOCK_SECONDS);
    }

    private function resetIpRateLimit(string $ip): void
    {
        if ($ip !== '') {
            Cache::forget($this->ipRateLimitKey($ip));
        }
    }
}
