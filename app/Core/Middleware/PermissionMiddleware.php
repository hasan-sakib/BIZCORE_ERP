<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Http\Request;

class PermissionMiddleware
{
    /** DB permission prefixes differ from route module names for these modules. */
    private const MODULE_ALIASES = [
        'accounting' => ['accounts', 'journals'],
        'sales'      => ['sales_orders', 'invoices', 'quotations', 'payments'],
        'purchasing' => ['purchase_orders', 'goods_receipts'],
    ];

    /** Generic action words — never used as module-level wildcard prefixes. */
    private const GENERIC_ACTIONS = [
        'view', 'create', 'edit', 'update', 'delete', 'manage',
        'approve', 'export', 'access', 'restore', 'post', 'void', 'cancel', 'refund',
    ];

    public function __construct(private readonly Auth $auth) {}

    public function handle(Request $request, callable $next, string $permission = ''): mixed
    {
        $user = $this->auth->user();

        if ($user === null) {
            if ($request->wantsJson()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthenticated.']);
                exit;
            }
            header('Location: /login');
            exit;
        }

        $permissions = $user->permissions ?? [];

        // Super-admin wildcard or no permission required.
        if (in_array('*', $permissions, true) || $permission === '' || in_array($permission, $permissions, true)) {
            return $next();
        }

        // Smart module-aware check covers three mismatch patterns:
        //   A) '.access' suffix  (route: hr.employees.access  / DB: employees.view)
        //   B) 'hr.' namespace   (route: hr.employees.create  / DB: employees.create)
        //   C) sub-module name   (route: sales.payments       / DB: payments.view)
        if ($this->checkModulePermission($permission, $permissions)) {
            return $next();
        }

        if ($request->wantsJson()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Forbidden.']);
            exit;
        }

        http_response_code(403);
        echo '<h1>403 Forbidden</h1>';
        exit;
    }

    private function checkModulePermission(string $permission, array $permissions): bool
    {
        // Case A: .access suffix — grant if user has ANY perm for the module.
        if (str_ends_with($permission, '.access')) {
            $base   = substr($permission, 0, -7); // strip '.access'
            $parts  = explode('.', $base);
            $module = (string) end($parts);        // 'employees', 'accounting', etc.

            $aliases = self::MODULE_ALIASES[$module] ?? [$module];

            foreach ($permissions as $perm) {
                foreach ($aliases as $alias) {
                    if (str_starts_with($perm, $alias . '.')) {
                        return true;
                    }
                }
            }

            return false;
        }

        // Case B: hr.* prefixed permissions — strip namespace, try exact match,
        // also translate .edit -> .update (routes use .edit, DB stores .update).
        if (str_starts_with($permission, 'hr.')) {
            $stripped = substr($permission, 3);

            if (in_array($stripped, $permissions, true)) {
                return true;
            }

            if (str_ends_with($stripped, '.edit')) {
                $asUpdate = substr($stripped, 0, -5) . '.update';
                if (in_array($asUpdate, $permissions, true)) {
                    return true;
                }
            }

            return false;
        }

        // Case C: module.submodule (e.g. 'sales.payments', 'accounting.journals') —
        // extract last segment; if not a generic action, check if user has any
        // permission starting with that segment.
        $parts = explode('.', $permission);
        if (count($parts) >= 2) {
            $last = (string) end($parts);
            if (!in_array($last, self::GENERIC_ACTIONS, true)) {
                foreach ($permissions as $perm) {
                    if (str_starts_with($perm, $last . '.')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
