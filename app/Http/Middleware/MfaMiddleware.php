<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MfaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('auth.mfa_enabled', false)) {
            return $next($request);
        }

        if (!Auth::check()) {
            return $next($request);
        }

        if (!session('mfa_verified')) {
            return redirect()->route('auth.mfa');
        }

        return $next($request);
    }
}
