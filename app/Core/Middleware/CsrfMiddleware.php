<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Session;
use App\Http\Request;

class CsrfMiddleware
{
    private array $except = [
        '/api/*',
        '/webhook/*',
    ];

    public function __construct(private readonly Session $session) {}

    public function handle(Request $request, callable $next): mixed
    {
        if ($this->shouldSkip($request)) {
            return $next();
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_token')
                ?? $request->input('_csrf_token')
                ?? $request->header('X-CSRF-TOKEN')
                ?? $request->header('X-Csrf-Token');

            if (!$this->session->validateCsrf((string)$token)) {
                http_response_code(419);
                if ($request->wantsJson()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'CSRF token mismatch.']);
                } else {
                    echo '<h1>419 - CSRF Token Mismatch</h1><p>Please go back and try again.</p>';
                }
                exit;
            }
        }

        return $next();
    }

    private function shouldSkip(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            $regex = '#^' . str_replace('*', '.*', $pattern) . '$#';
            if (preg_match($regex, $request->path())) {
                return true;
            }
        }
        return false;
    }
}
