<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Http\Request;

class GuestMiddleware
{
    public function __construct(private readonly Auth $auth) {}

    public function handle(Request $request, callable $next): mixed
    {
        if ($this->auth->check()) {
            header('Location: /dashboard');
            exit;
        }

        return $next();
    }
}
