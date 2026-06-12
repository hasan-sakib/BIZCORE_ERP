<?php

declare(strict_types=1);

/**
 * BizCore ERP - Application Bootstrap
 *
 * This file is the very first PHP executed on every request.
 * It performs the following in strict order:
 *
 *  1. Defines core constants (BASE_PATH, PUBLIC_PATH, etc.)
 *  2. Registers the Composer PSR-4 autoloader
 *  3. Loads environment variables from .env via vlucas/phpdotenv
 *  4. Configures PHP error handling, reporting, and error-to-exception conversion
 *  5. Sets the default timezone and multi-byte encoding
 *  6. Checks for maintenance mode
 *  7. Instantiates the Application container and registers core bindings
 *  8. Boots all registered service providers
 *  9. Returns the Application instance to the front controller
 *
 * @package BizCore\ERP
 * @version 1.0.0
 */

// ============================================================================
// 1. PATH CONSTANTS
// ============================================================================

define('BIZCORE_START', microtime(true));
define('BASE_PATH',     dirname(__DIR__));
define('APP_PATH',      BASE_PATH . '/app');
define('CONFIG_PATH',   BASE_PATH . '/config');
define('BOOTSTRAP_PATH',BASE_PATH . '/bootstrap');
define('PUBLIC_PATH',   BASE_PATH . '/public');
define('STORAGE_PATH',  BASE_PATH . '/storage');
define('RESOURCES_PATH',BASE_PATH . '/resources');
define('DATABASE_PATH', BASE_PATH . '/database');
define('ROUTES_PATH',   BASE_PATH . '/routes');

// ============================================================================
// 2. COMPOSER AUTOLOADER
// ============================================================================

$autoloadFile = BASE_PATH . '/vendor/autoload.php';

if (! file_exists($autoloadFile)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit(
        'BizCore ERP: Composer autoloader not found.' . PHP_EOL .
        'Run: composer install --no-dev --optimize-autoloader' . PHP_EOL
    );
}

require $autoloadFile;

// ============================================================================
// 3. ENVIRONMENT VARIABLES (.env)
// ============================================================================

(static function (): void {
    $envFile = BASE_PATH . '/.env';

    if (! file_exists($envFile)) {
        // Allow running without .env only in testing environments where
        // all variables are supplied by the test harness (e.g., CI/CD).
        if (getenv('APP_ENV') === 'testing') {
            return;
        }

        // In all other cases, a missing .env is a configuration error.
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        exit(
            'BizCore ERP: Environment file (.env) not found.' . PHP_EOL .
            'Copy .env.example to .env and fill in the required values.' . PHP_EOL
        );
    }

    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();

    // ── Required variables that must be set in every environment ────────────
    $dotenv->required([
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_URL',
    ])->notEmpty();

    $dotenv->required('APP_ENV')->allowedValues([
        'local', 'development', 'staging', 'production', 'testing',
    ]);

    $dotenv->required('DB_CONNECTION')->allowedValues([
        'mysql', 'sqlite', 'mysql_testing',
    ]);

    // Validate APP_KEY format (must start with "base64:" for AES-256-CBC)
    $appKey = $_ENV['APP_KEY'] ?? '';
    if (! str_starts_with($appKey, 'base64:') && strlen($appKey) < 32) {
        throw new \RuntimeException(
            'APP_KEY must be at least 32 characters or a base64-encoded 32-byte key. ' .
            'Generate one with: php -r "echo \'base64:\'.base64_encode(random_bytes(32));"'
        );
    }
})();

// ============================================================================
// 4. ERROR HANDLING & REPORTING
// ============================================================================

(static function (): void {
    $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $isProduction = ($_ENV['APP_ENV'] ?? 'local') === 'production';

    // Always capture all errors internally; display depends on debug flag.
    error_reporting(E_ALL);
    ini_set('display_errors',         $isDebug && ! $isProduction ? '1' : '0');
    ini_set('display_startup_errors', $isDebug && ! $isProduction ? '1' : '0');
    ini_set('log_errors',             '1');
    ini_set('error_log',              STORAGE_PATH . '/logs/php_errors.log');

    // Promote PHP errors to ErrorException so they can be caught uniformly.
    set_error_handler(static function (
        int    $severity,
        string $message,
        string $file,
        int    $line
    ): bool {
        // Respect the current error_reporting level (e.g. @ operator suppression).
        if (! (error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    // Last-resort exception handler for uncaught exceptions that bubble past
    // the front controller's try/catch.
    set_exception_handler(static function (\Throwable $e): void {
        $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Log the raw error unconditionally.
        $logFile = STORAGE_PATH . '/logs/php_errors.log';
        $logDir  = dirname($logFile);
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry  = sprintf(
            "[%s] UNCAUGHT %s: %s in %s:%d\nStack trace:\n%s\n\n",
            $timestamp,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Determine the response format based on the request.
        $isApiRequest = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
        $httpCode     = ($e instanceof \App\Exceptions\HttpException)
            ? $e->getStatusCode()
            : 500;

        if (! headers_sent()) {
            http_response_code($httpCode);
        }

        if ($isApiRequest) {
            header('Content-Type: application/json; charset=UTF-8');
            $payload = ['success' => false, 'message' => 'An unexpected error occurred.'];
            if ($isDebug) {
                $payload['debug'] = [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'trace'     => explode("\n", $e->getTraceAsString()),
                ];
            }
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            // Attempt to render an error view; fall back to plain HTML.
            $errorView = RESOURCES_PATH . '/views/errors/' . $httpCode . '.php';
            $genericView = RESOURCES_PATH . '/views/errors/500.php';

            if (file_exists($errorView)) {
                require $errorView;
            } elseif (file_exists($genericView)) {
                require $genericView;
            } else {
                // Absolute fallback — no views available yet.
                $safeMessage = $isDebug
                    ? htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_HTML5)
                    : 'An internal server error occurred. Please try again later.';

                echo <<<HTML
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <title>Error {$httpCode} — BizCore ERP</title>
                        <style>
                            body { font-family: sans-serif; padding: 2rem; background: #f8f9fa; }
                            .card { background: #fff; border-radius: 6px; padding: 2rem; max-width: 640px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
                            h1 { color: #dc3545; }
                        </style>
                    </head>
                    <body>
                        <div class="card">
                            <h1>HTTP {$httpCode}</h1>
                            <p>{$safeMessage}</p>
                        </div>
                    </body>
                    </html>
                    HTML;
            }
        }

        exit(1);
    });

    // Register a shutdown function to catch fatal errors (e.g. OOM, parse errors
    // in included files) that cannot be promoted to ErrorException.
    register_shutdown_function(static function (): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $logFile = STORAGE_PATH . '/logs/php_errors.log';
            $entry   = sprintf(
                "[%s] FATAL(%d): %s in %s:%d\n\n",
                date('Y-m-d H:i:s'),
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
            file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

            if (! headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8');
            }
            $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
            echo $isDebug
                ? "Fatal error: {$error['message']} in {$error['file']}:{$error['line']}"
                : 'A critical error occurred. Please contact the system administrator.';
        }
    });
})();

// ============================================================================
// 5. TIMEZONE, LOCALE, AND ENCODING
// ============================================================================

(static function (): void {
    $timezone = $_ENV['APP_TIMEZONE'] ?? 'Asia/Dhaka';

    if (! in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
        throw new \RuntimeException(
            "Invalid timezone '{$timezone}'. Check APP_TIMEZONE in your .env file."
        );
    }

    date_default_timezone_set($timezone);

    // Multi-byte string handling — critical for Bengali (UTF-8) support.
    if (extension_loaded('mbstring')) {
        mb_internal_encoding('UTF-8');
        mb_language('uni');
        mb_regex_encoding('UTF-8');
        mb_detect_order(['UTF-8', 'ASCII', 'ISO-8859-1']);
    }

    // Set locale for number/date formatting (en_US.UTF-8 is a safe system default;
    // the application applies bn_BD locale contextually for currency display).
    setlocale(LC_ALL, 'C.UTF-8', 'C');
})();

// ============================================================================
// 6. MAINTENANCE MODE
// ============================================================================

(static function (): void {
    $maintenanceFile = STORAGE_PATH . '/framework/down';

    if (! file_exists($maintenanceFile)) {
        return;
    }

    // Allow certain IPs through even during maintenance (e.g. dev team).
    $allowedIps = array_filter(
        explode(',', $_ENV['MAINTENANCE_ALLOWED_IPS'] ?? '')
    );
    $clientIp   = $_SERVER['REMOTE_ADDR'] ?? '';

    if (in_array($clientIp, $allowedIps, true)) {
        return;
    }

    $payload = file_exists($maintenanceFile)
        ? json_decode(file_get_contents($maintenanceFile), true)
        : [];

    $retryAfter = $payload['retry'] ?? 60;
    $message    = $payload['message']
        ?? 'BizCore ERP is currently undergoing scheduled maintenance. We will be back shortly.';

    http_response_code(503);
    header('Retry-After: ' . $retryAfter);

    $isApiRequest = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');

    if ($isApiRequest) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success'      => false,
            'message'      => $message,
            'retry_after'  => $retryAfter,
        ], JSON_PRETTY_PRINT);
    } else {
        $maintenanceView = RESOURCES_PATH . '/views/errors/503.php';
        if (file_exists($maintenanceView)) {
            require $maintenanceView;
        } else {
            echo <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta http-equiv="refresh" content="{$retryAfter}">
                    <title>Maintenance — BizCore ERP</title>
                    <style>
                        body { font-family: sans-serif; text-align: center; padding-top: 10vh; background: #f1f3f5; }
                        h1 { font-size: 2rem; color: #343a40; }
                        p  { color: #6c757d; max-width: 500px; margin: 1rem auto; }
                    </style>
                </head>
                <body>
                    <h1>🔧 Under Maintenance</h1>
                    <p>{$message}</p>
                    <p><small>This page will refresh automatically in {$retryAfter} seconds.</small></p>
                </body>
                </html>
                HTML;
        }
    }

    exit;
})();

// ============================================================================
// 7. ENSURE REQUIRED STORAGE DIRECTORIES EXIST
// ============================================================================

(static function (): void {
    $dirs = [
        STORAGE_PATH . '/logs',
        STORAGE_PATH . '/framework/cache/data',
        STORAGE_PATH . '/framework/cache/locks',
        STORAGE_PATH . '/framework/sessions',
        STORAGE_PATH . '/framework/views',
        STORAGE_PATH . '/app/public',
        STORAGE_PATH . '/app/private',
        STORAGE_PATH . '/app/temp',
        STORAGE_PATH . '/backups/database',
        STORAGE_PATH . '/exports',
        STORAGE_PATH . '/imports',
    ];

    foreach ($dirs as $dir) {
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Failed to create required storage directory: {$dir}");
        }
    }
})();

// ============================================================================
// 8. BUILD AND CONFIGURE THE APPLICATION CONTAINER
// ============================================================================

/** @var \App\Foundation\Application $app */
$app = require BOOTSTRAP_PATH . '/container.php';

// ============================================================================
// 9. BOOT SERVICE PROVIDERS
// ============================================================================

$app->boot();

// ============================================================================
// Return the fully-booted Application to the caller (public/index.php)
// ============================================================================

return $app;
