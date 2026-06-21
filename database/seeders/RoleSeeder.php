<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'        => 'Super Admin',
                'slug'        => 'super_admin',
                'permissions' => ['*'],
                'is_system'   => true,
                'description' => 'Full system access',
            ],
            [
                'name'        => 'Admin',
                'slug'        => 'admin',
                'permissions' => [
                    'users.*', 'roles.*', 'branches.*', 'settings.*',
                    'hr.*', 'payroll.*', 'inventory.*',
                    'sales.*', 'purchasing.*', 'accounting.*',
                    'expenses.*', 'reports.*', 'customers.*', 'suppliers.*',
                ],
                'is_system'   => true,
                'description' => 'Administrative access',
            ],
            [
                'name'        => 'HR Manager',
                'slug'        => 'hr_manager',
                'permissions' => ['hr.*', 'payroll.*', 'reports.hr'],
                'is_system'   => false,
                'description' => 'HR and payroll management',
            ],
            [
                'name'        => 'Accountant',
                'slug'        => 'accountant',
                'permissions' => [
                    'accounting.*', 'expenses.*', 'reports.financial',
                    'reports.vat', 'sales.view', 'purchasing.view',
                ],
                'is_system'   => false,
                'description' => 'Accounting and financial management',
            ],
            [
                'name'        => 'Sales Manager',
                'slug'        => 'sales_manager',
                'permissions' => [
                    'sales.*', 'customers.*', 'inventory.view',
                    'reports.sales', 'products.view',
                ],
                'is_system'   => false,
                'description' => 'Sales and customer management',
            ],
            [
                'name'        => 'Inventory Manager',
                'slug'        => 'inventory_manager',
                'permissions' => ['inventory.*', 'products.*', 'reports.inventory'],
                'is_system'   => false,
                'description' => 'Inventory and warehouse management',
            ],
            [
                'name'        => 'Purchase Manager',
                'slug'        => 'purchase_manager',
                'permissions' => ['purchasing.*', 'suppliers.*', 'inventory.view', 'reports.purchasing'],
                'is_system'   => false,
                'description' => 'Purchasing and supplier management',
            ],
            [
                'name'        => 'Employee',
                'slug'        => 'employee',
                'permissions' => ['hr.attendance.self', 'hr.leave.self', 'payroll.payslip.self'],
                'is_system'   => false,
                'description' => 'Basic employee access',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
