<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Http\Request;

class VerifiedMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        return $next();
    }
}
