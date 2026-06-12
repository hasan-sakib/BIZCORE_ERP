<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class ModuleController extends BaseController
{
    private const MODULES = [
        'hr'         => ['name' => 'HR Management',    'icon' => 'fa-users'],
        'payroll'    => ['name' => 'Payroll',           'icon' => 'fa-money-check-alt'],
        'inventory'  => ['name' => 'Inventory',         'icon' => 'fa-warehouse'],
        'purchasing' => ['name' => 'Purchasing',        'icon' => 'fa-shopping-cart'],
        'sales'      => ['name' => 'Sales & CRM',       'icon' => 'fa-chart-line'],
        'expenses'   => ['name' => 'Expenses',          'icon' => 'fa-receipt'],
        'accounting' => ['name' => 'Accounting',        'icon' => 'fa-calculator'],
        'reports'    => ['name' => 'Reports',           'icon' => 'fa-chart-bar'],
    ];

    public function index(Request $request): Response
    {
        return $this->render('settings/modules', [
            'pageTitle'   => 'Module Management',
            'breadcrumbs' => ['Settings' => '/settings', 'Modules' => null],
            'modules'     => self::MODULES,
        ]);
    }

    public function toggle(Request $request, string $module): Response
    {
        $this->success('Module settings updated.');
        return $this->redirect('/settings/modules');
    }
}
