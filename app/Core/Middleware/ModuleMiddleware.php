<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Http\Request;

class ModuleMiddleware
{
    public function handle(Request $request, callable $next, string $module = ''): mixed
    {
        return $next();
    }
}
