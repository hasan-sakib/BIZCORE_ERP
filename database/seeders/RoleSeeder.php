<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;

/**
 * RoleSeeder
 *
 * Seeds all seven system roles.  Permissions are stored as a JSON array of
 * "module.action" strings in the `roles.permissions` column — there is no
 * separate permissions table or pivot table.
 */
final class RoleSeeder
{
    /** @var list<string> */
    private const MODULES = [
        'users', 'roles', 'branches',
        'employees', 'departments', 'designations', 'attendance', 'payroll',
        'customers', 'suppliers',
        'products', 'categories', 'inventory', 'warehouses',
        'purchase_orders', 'goods_receipts',
        'sales_orders', 'invoices', 'payments', 'expenses',
        'accounts', 'journals', 'reports', 'settings',
    ];

    /** @var list<string> */
    private const ACTIONS = ['view', 'create', 'update', 'delete', 'approve', 'export'];

    /**
     * @var array<string, array{
     *     name: string,
     *     description: string,
     *     modules: list<string>|null,
     *     actions: list<string>|null,
     *     extra: list<string>,
     *     exclude: list<string>
     * }>
     */
    private const ROLE_DEFINITIONS = [
        'super_admin' => [
            'name'        => 'Super Admin',
            'description' => 'Unrestricted access to all modules and system settings.',
            'modules'     => null,  // wildcard handled separately
            'actions'     => null,
            'extra'       => [],
            'exclude'     => [],
        ],

        'branch_manager' => [
            'name'        => 'Branch Manager',
            'description' => 'Full operational access; cannot manage system settings or user/role administration.',
            'modules'     => [
                'employees', 'departments', 'designations', 'attendance', 'payroll',
                'customers', 'suppliers', 'products', 'categories', 'inventory',
                'warehouses', 'purchase_orders', 'goods_receipts',
                'sales_orders', 'invoices', 'payments', 'expenses',
                'accounts', 'journals', 'reports',
            ],
            'actions'  => ['view', 'create', 'update', 'delete', 'approve', 'export'],
            'extra'    => [],
            'exclude'  => [],
        ],

        'accountant' => [
            'name'        => 'Accountant',
            'description' => 'Manages accounting entries, processes payments, and generates financial reports.',
            'modules'     => [
                'accounts', 'journals', 'expenses', 'payments', 'invoices', 'reports',
            ],
            'actions'  => ['view', 'create', 'update', 'export'],
            'extra'    => [
                'sales_orders.view',
                'invoices.approve',
                'payments.approve',
            ],
            'exclude'  => [],
        ],

        'inventory_manager' => [
            'name'        => 'Inventory Manager',
            'description' => 'Controls products, stock levels, warehouses, and purchase procurement.',
            'modules'     => [
                'products', 'categories', 'inventory', 'warehouses',
                'purchase_orders', 'goods_receipts', 'suppliers',
            ],
            'actions'  => ['view', 'create', 'update', 'delete', 'approve', 'export'],
            'extra'    => [],
            'exclude'  => [],
        ],

        'hr_officer' => [
            'name'        => 'HR Officer',
            'description' => 'Manages employees, departments, attendance recording, and payroll preparation.',
            'modules'     => [
                'employees', 'departments', 'designations', 'attendance', 'payroll',
            ],
            'actions'  => ['view', 'create', 'update', 'delete', 'approve', 'export'],
            'extra'    => [],
            'exclude'  => [],
        ],

        'sales_executive' => [
            'name'        => 'Sales Executive',
            'description' => 'Handles customer relationships, quotations, sales orders, invoicing, and payment recording.',
            'modules'     => [
                'customers', 'sales_orders', 'invoices', 'payments',
            ],
            'actions'  => ['view', 'create', 'update', 'export'],
            'extra'    => [],
            'exclude'  => [],
        ],

        'employee' => [
            'name'        => 'Employee',
            'description' => 'Can view own attendance records and own payslip only.',
            'modules'     => [],   // no full-module access
            'actions'     => [],
            'extra'       => [
                'attendance.view',
                'payroll.view',
            ],
            'exclude'  => [],
        ],
    ];

    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function run(): void
    {
        $allPermissions = $this->buildAllPermissions();

        foreach (self::ROLE_DEFINITIONS as $slug => $def) {
            $permissions = $this->computePermissions($slug, $def, $allPermissions);
            $this->upsertRole($slug, $def, $permissions);
        }
    }

    // -------------------------------------------------------------------------

    /** @return list<string> */
    private function buildAllPermissions(): array
    {
        $all = [];
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $all[] = $module . '.' . $action;
            }
        }
        return $all;
    }

    /**
     * @param  array{modules:list<string>|null, actions:list<string>|null, extra:list<string>, exclude:list<string>} $def
     * @param  list<string> $allPermissions
     * @return list<string>
     */
    private function computePermissions(string $slug, array $def, array $allPermissions): array
    {
        if ($slug === 'super_admin') {
            return ['*'];
        }

        if ($def['modules'] === [] && $def['actions'] === []) {
            return $def['extra'];
        }

        $set = [];

        if ($def['modules'] !== null && $def['actions'] !== null) {
            foreach ($def['modules'] as $module) {
                foreach ($def['actions'] as $action) {
                    $perm = $module . '.' . $action;
                    if (in_array($perm, $allPermissions, true) && !in_array($perm, $def['exclude'], true)) {
                        $set[] = $perm;
                    }
                }
            }
        }

        foreach ($def['extra'] as $perm) {
            if (!in_array($perm, $set, true)) {
                $set[] = $perm;
            }
        }

        return $set;
    }

    /** @param list<string> $permissions */
    private function upsertRole(string $slug, array $def, array $permissions): void
    {
        $now = date('Y-m-d H:i:s');

        $sql = <<<'SQL'
            INSERT INTO roles (name, slug, description, permissions, is_system, created_at, updated_at)
            VALUES (:name, :slug, :description, :permissions, 1, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                name        = VALUES(name),
                description = VALUES(description),
                permissions = VALUES(permissions),
                updated_at  = VALUES(updated_at)
        SQL;

        $this->pdo->prepare($sql)->execute([
            'name'        => $def['name'],
            'slug'        => $slug,
            'description' => $def['description'],
            'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }
}
