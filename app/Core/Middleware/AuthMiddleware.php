<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Session;
use App\Http\Request;

class AuthMiddleware
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session
    ) {}

    public function handle(Request $request, callable $next): mixed
    {
        if (!$this->auth->check()) {
            if ($request->wantsJson()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthenticated.']);
                exit;
            }

            $this->session->flash('error', 'Please log in to continue.');
            $intended = $request->path();
            if ($intended !== '/login') {
                $this->session->set('url.intended', $intended);
            }
            header('Location: /login');
            exit;
        }

        return $next();
    }
}
