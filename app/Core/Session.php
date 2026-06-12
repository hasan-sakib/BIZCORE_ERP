<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');

        session_name('BIZCORE_SESSION');
        session_start();
        $this->started = true;

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function flashInput(array $input): void
    {
        $_SESSION['_old_input'] = $input;
    }

    public function old(string $key, mixed $default = ''): mixed
    {
        $value = $_SESSION['_old_input'][$key] ?? $default;
        unset($_SESSION['_old_input'][$key]);
        return $value;
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public function validateCsrf(string $token): bool
    {
        return hash_equals($_SESSION['_csrf_token'] ?? '', $token);
    }

    public function regenerateCsrf(): string
    {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf_token'];
    }

    public function regenerate(bool $deleteOld = false): void
    {
        session_regenerate_id($deleteOld);
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $this->started = false;
    }

    public function all(): array
    {
        return $_SESSION ?? [];
    }

    public function userId(): ?int
    {
        $id = $_SESSION['auth_user_id'] ?? null;
        return $id !== null ? (int) $id : null;
    }

    public function setUserId(int $userId): void
    {
        $_SESSION['auth_user_id'] = $userId;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }
}
