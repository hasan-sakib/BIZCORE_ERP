<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleMiddleware
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!config("modules.enabled.{$module}", false)) {
            abort(403, "The '{$module}' module is disabled.");
        }

        return $next($request);
    }
}
