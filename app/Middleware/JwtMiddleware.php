<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Http\Request;

class JwtMiddleware
{
    public function __construct(private readonly Auth $auth) {}

    public function handle(Request $request, callable $next): mixed
    {
        $authHeader = $request->header('Authorization') ?? $request->header('authorization') ?? '';
        $token = null;

        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }

        if (!$token) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No authentication token provided.']);
            exit;
        }

        $user = $this->auth->getUserFromJWT($token);
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token.']);
            exit;
        }

        $request->setAuthUser($user);
        $request->setAttribute('jwt_token', $token);

        return $next();
    }
}
