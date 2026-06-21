<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return redirect()->route('auth.login');
        }

        if ($this->checkModulePermission($user, $permission)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        abort(403, "You do not have permission: {$permission}");
    }

    /**
     * 3-part smart permission check:
     *  1. Direct match against roles.permissions array
     *  2. Try with '.access' suffix (e.g. 'hr.employees' → 'hr.employees.access')
     *  3. Strip module namespace (e.g. 'hr.employees.access' → 'employees.access')
     */
    private function checkModulePermission(mixed $user, string $permission): bool
    {
        $role = $user->role;

        if ($role === null) {
            return false;
        }

        // Super-admin wildcard
        $permissions = $role->permissions ?? [];
        if (in_array('*', $permissions, true)) {
            return true;
        }

        // 1. Direct match
        if (in_array($permission, $permissions, true)) {
            return true;
        }

        // 2. Try with .access suffix
        $withAccess = rtrim($permission, '.access') . '.access';
        if ($withAccess !== $permission && in_array($withAccess, $permissions, true)) {
            return true;
        }

        // 3. Strip leading module prefix (e.g. 'hr.employees.access' → 'employees.access')
        $parts = explode('.', $permission);
        if (count($parts) > 2) {
            $stripped = implode('.', array_slice($parts, 1));
            if (in_array($stripped, $permissions, true)) {
                return true;
            }
        }

        return false;
    }
}
