<?php

declare(strict_types=1);

/**
 * BizCore ERP - Front Controller (public/index.php)
 *
 * All HTTP requests are routed through this single entry point.
 * The web server (Nginx / Apache) rewrites every request that does not
 * match a real file or directory to this script.
 *
 * Responsibilities:
 *  1. Security: block direct execution from CLI, enforce HTTPS in production
 *  2. Bootstrap the application (loads .env, DI container, service providers)
 *  3. Retrieve the Router from the container
 *  4. Dispatch the incoming HTTP request through the middleware pipeline
 *  5. Send the final Response (headers + body) to the client
 *  6. Handle any uncaught exceptions with a user-friendly error response
 *
 * @package BizCore\ERP
 * @version 1.0.0
 */

// ============================================================================
// 1. DEFINE PUBLIC PATH CONSTANT (used before bootstrap runs)
// ============================================================================

define('BIZCORE_PUBLIC_PATH', __DIR__);

// ============================================================================
// 2. BLOCK DIRECT CLI INVOCATION
// ============================================================================

if (PHP_SAPI === 'cli' && ! defined('BIZCORE_ALLOW_CLI')) {
    fwrite(STDERR, "This script must be run via a web server, not from the CLI.\n");
    exit(1);
}

// ============================================================================
// 3. BOOTSTRAP THE APPLICATION
// ============================================================================

/** @var \App\Foundation\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

// ============================================================================
// 4. HTTPS ENFORCEMENT (production only)
// ============================================================================

(static function () use ($app): void {
    if (! $app->isProduction()) {
        return;
    }

    $isHttps = (
        (isset($_SERVER['HTTPS'])       && strtolower($_SERVER['HTTPS'])       !== 'off') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (isset($_SERVER['HTTP_X_FORWARDED_SSL'])   && strtolower($_SERVER['HTTP_X_FORWARDED_SSL'])   !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
    );

    if (! $isHttps) {
        $host = $_SERVER['HTTP_HOST'] ?? parse_url($app->config('app.url'), PHP_URL_HOST);
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';

        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
})();

// ============================================================================
// 5. GLOBAL SECURITY HEADERS
// ============================================================================

(static function () use ($app): void {
    if (headers_sent()) {
        return;
    }

    $isProduction = $app->isProduction();

    // Prevent clickjacking.
    header('X-Frame-Options: SAMEORIGIN');

    // Block MIME-type sniffing.
    header('X-Content-Type-Options: nosniff');

    // Enable built-in XSS filter in legacy browsers.
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy — safe for mixed SPA/server-rendered pages.
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions policy — restrict sensitive browser APIs.
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

    // Content-Security-Policy (tightened in production).
    if ($isProduction) {
        header(
            "Content-Security-Policy: " .
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline'; " .   // unsafe-inline required by many UI libs; tighten with nonce later
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "font-src 'self' https://fonts.gstatic.com data:; " .
            "img-src 'self' data: blob:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'self'; " .
            "base-uri 'self'; " .
            "form-action 'self';"
        );
    }

    // HSTS — only in production over HTTPS.
    if ($isProduction) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    // Remove the PHP version header for security.
    header_remove('X-Powered-By');
    header('Server: BizCore/1.0');
})();

// ============================================================================
// 6. RATE LIMITING (fast-path, before full controller dispatch)
// ============================================================================

(static function () use ($app): void {
    // Only apply to API requests at the front-controller level.
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (! str_starts_with($uri, '/api/')) {
        return;
    }

    try {
        /** @var \App\Security\RateLimiter $limiter */
        $limiter = $app->get('rate_limiter');

        // Key on IP + User-Agent to balance security and proxy fairness.
        $ip     = $_SERVER['REMOTE_ADDR']          ?? '0.0.0.0';
        $ua     = $_SERVER['HTTP_USER_AGENT']       ?? '';
        $key    = 'api:' . md5($ip . $ua);

        $result = $limiter->attempt($key);

        header('X-RateLimit-Limit: '     . $result['limit']);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: '     . $result['reset_at']);

        if ($result['exceeded']) {
            http_response_code(429);
            header('Retry-After: ' . $result['retry_after']);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => $result['retry_after'],
            ]);
            exit;
        }
    } catch (\Throwable $e) {
        // If the rate limiter itself fails (e.g. Redis down), log and continue.
        // Do NOT block the request over a monitoring component failure.
        if ($app->has('logger')) {
            $app->get('logger')->error('Rate limiter failure', ['exception' => $e->getMessage()]);
        }
    }
})();

// ============================================================================
// 7. DISPATCH THE REQUEST
// ============================================================================

try {
    /** @var \App\Http\Request $request */
    $request = $app->get('request');

    /** @var \App\Core\Router $router */
    $router  = $app->get('router');

    /** @var \App\Http\Response $response */
    $response = $router->dispatch($request);

    // ── Send response headers ──────────────────────────────────────────────

    if (! headers_sent()) {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $value) {
            header("{$name}: {$value}", true);
        }
    }

    // ── Emit body (handle HEAD requests correctly) ─────────────────────────

    if ($request->method() !== 'HEAD') {
        echo $response->getBody();
    }

// ── Handle HTTP exceptions (404, 403, 422, etc.) ──────────────────────────
} catch (\App\Exceptions\HttpException $e) {
    $statusCode = $e->getStatusCode();
    $isApi      = str_starts_with($_SERVER['REQUEST_URI'] ?? '/', '/api/');

    if (! headers_sent()) {
        http_response_code($statusCode);
    }

    if ($isApi) {
        header('Content-Type: application/json; charset=UTF-8');
        $payload = [
            'success' => false,
            'message' => $e->getMessage() ?: 'HTTP ' . $statusCode,
            'code'    => $statusCode,
        ];
        if ($app->isDebug() && ! $app->isProduction()) {
            $payload['trace'] = explode("\n", $e->getTraceAsString());
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        $viewFile = BASE_PATH . '/resources/views/errors/' . $statusCode . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            // Minimal inline error page.
            $safeMsg = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $titles  = [
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Page Not Found',
                405 => 'Method Not Allowed',
                419 => 'CSRF Token Mismatch',
                422 => 'Validation Error',
                429 => 'Too Many Requests',
                500 => 'Server Error',
                503 => 'Service Unavailable',
            ];
            $title = $titles[$statusCode] ?? 'Error';

            echo <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>{$statusCode} {$title} — BizCore ERP</title>
                    <style>
                        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
                        body {
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                            background: #f1f3f5;
                            min-height: 100vh;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .card {
                            background: #fff;
                            border-radius: 8px;
                            padding: 3rem;
                            max-width: 520px;
                            width: 90%;
                            text-align: center;
                            box-shadow: 0 4px 24px rgba(0,0,0,.08);
                        }
                        .code { font-size: 5rem; font-weight: 700; color: #dee2e6; line-height: 1; }
                        h1   { font-size: 1.5rem; color: #212529; margin: .75rem 0 1rem; }
                        p    { color: #6c757d; margin-bottom: 1.5rem; }
                        a    {
                            display: inline-block;
                            padding: .5rem 1.5rem;
                            background: #0d6efd;
                            color: #fff;
                            border-radius: 4px;
                            text-decoration: none;
                            font-size: .9rem;
                        }
                        a:hover { background: #0b5ed7; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <div class="code">{$statusCode}</div>
                        <h1>{$title}</h1>
                        <p>{$safeMsg}</p>
                        <a href="/">Go to Dashboard</a>
                    </div>
                </body>
                </html>
                HTML;
        }
    }

// ── Handle validation exceptions ───────────────────────────────────────────
} catch (\App\Exceptions\ValidationException $e) {
    if (! headers_sent()) {
        http_response_code(422);
    }

    $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '/', '/api/');

    if ($isApi) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $e->getErrors(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // For web requests, flash errors to session and redirect back.
        try {
            /** @var \App\Session\SessionManager $session */
            $session = $app->get('session');
            $session->flash('validation_errors', $e->getErrors());
            $session->flash('old_input',         $_POST);
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            header('Location: ' . $referer, true, 302);
        } catch (\Throwable) {
            http_response_code(422);
            echo 'Validation error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

// ── Handle all other exceptions ────────────────────────────────────────────
} catch (\Throwable $e) {
    // The global exception handler in bootstrap/app.php will deal with logging
    // and rendering. Re-throw so it fires.
    throw $e;

} finally {

    // ── Request lifecycle: close session, flush output, emit metrics ────────

    // Flush any buffered output.
    if (ob_get_level() > 0) {
        ob_end_flush();
    }

    // Emit timing header for debugging (non-production only).
    if (! $app->isProduction() && ! headers_sent()) {
        $elapsed = round((microtime(true) - BIZCORE_START) * 1000, 2);
        header('X-Response-Time: ' . $elapsed . 'ms');
    }
}
