<?php

declare(strict_types=1);

use App\Foundation\Application;
use App\Core\Session;
use App\Core\Cache;

if (!function_exists('app')) {
    function app(string $abstract = ''): mixed
    {
        $app = Application::getInstance();
        return $abstract ? $app->get($abstract) : $app;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $configs = [];
        [$file, $subKey] = array_pad(explode('.', $key, 2), 2, null);

        if (!isset($configs[$file])) {
            $path = Application::getInstance()->configPath("{$file}.php");
            $configs[$file] = file_exists($path) ? require $path : [];
        }

        if ($subKey === null) {
            return $configs[$file] ?? $default;
        }

        $value = $configs[$file];
        foreach (explode('.', $subKey) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match(strtolower((string)$value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', ''        => '',
            default            => $value,
        };
    }
}

if (!function_exists('session')) {
    function session(): Session
    {
        return app(Session::class);
    }
}

if (!function_exists('cache')) {
    function cache(): Cache
    {
        return app(Cache::class);
    }
}

if (!function_exists('auth')) {
    function auth(): \App\Core\Auth
    {
        return app(\App\Core\Auth::class);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): string
    {
        $path = Application::getInstance()->resourcePath("views/{$template}.php");
        if (!file_exists($path)) {
            throw new RuntimeException("View [{$template}] not found.");
        }
        extract($data, EXTR_SKIP);
        $currentUser = auth()->user();
        $session = session();
        ob_start();
        require $path;
        return ob_get_clean();
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = config('app.url', '');
        return rtrim($base, '/') . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('storage_url')) {
    function storage_url(string $path): string
    {
        $base = config('app.url', '');
        return rtrim($base, '/') . '/storage/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return app(\App\Core\Router::class)->route($name, $params);
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney(float $amount, string $currency = 'BDT', string $locale = 'en'): string
    {
        $symbol = $currency === 'BDT' ? '৳' : $currency;
        return $symbol . ' ' . number_format($amount, 2);
    }
}

if (!function_exists('formatDate')) {
    function formatDate(?string $date, string $format = 'd/m/Y'): string
    {
        if (!$date) return '-';
        try {
            return (new DateTime($date))->format($format);
        } catch (Exception) {
            return $date;
        }
    }
}

if (!function_exists('sanitize')) {
    function sanitize(mixed $input): string
    {
        return htmlspecialchars((string)$input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('encrypt')) {
    function encrypt(string $data): string
    {
        $key = base64_decode(config('app.key', ''));
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
}

if (!function_exists('decrypt')) {
    function decrypt(string $data): string
    {
        $key = base64_decode(config('app.key', ''));
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed.');
        }
        return $decrypted;
    }
}

if (!function_exists('generateCode')) {
    function generateCode(string $prefix, int $length = 6): string
    {
        return strtoupper($prefix) . '-' . str_pad((string)random_int(1, (int)str_repeat('9', $length)), $length, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('paginate')) {
    function paginate(int $total, int $page, int $perPage): array
    {
        $lastPage = (int)ceil($total / $perPage);
        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'from'         => ($page - 1) * $perPage + 1,
            'to'           => min($page * $perPage, $total),
            'has_previous' => $page > 1,
            'has_next'     => $page < $lastPage,
        ];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = session()->csrfToken();
        return "<input type=\"hidden\" name=\"_token\" value=\"{$token}\">";
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return session()->csrfToken();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return session()->old($key, $default);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        exit(1);
    }
}

if (!function_exists('now')) {
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return (new DateTime('now', new DateTimeZone(config('app.timezone', 'Asia/Dhaka'))))->format($format);
    }
}

if (!function_exists('trans')) {
    function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return app(\App\Helpers\Localization::class)->trans($key, $replace, $locale);
    }
}
