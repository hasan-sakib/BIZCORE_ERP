<?php

declare(strict_types=1);

namespace App\Controllers\Reports;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class ReportController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->render('reports/index', [
            'pageTitle'   => 'Reports',
            'breadcrumbs' => ['Reports' => null],
        ]);
    }

    public function sales(Request $request): Response
    {
        return $this->render('reports/sales', [
            'pageTitle'   => 'Sales Report',
            'breadcrumbs' => ['Reports' => '/reports', 'Sales' => null],
        ]);
    }

    public function purchases(Request $request): Response
    {
        return $this->render('reports/purchases', [
            'pageTitle'   => 'Purchase Report',
            'breadcrumbs' => ['Reports' => '/reports', 'Purchases' => null],
        ]);
    }

    public function inventory(Request $request): Response
    {
        return $this->render('reports/inventory', [
            'pageTitle'   => 'Inventory Report',
            'breadcrumbs' => ['Reports' => '/reports', 'Inventory' => null],
        ]);
    }

    public function financial(Request $request): Response
    {
        return $this->render('reports/financial', [
            'pageTitle'   => 'Financial Report',
            'breadcrumbs' => ['Reports' => '/reports', 'Financial' => null],
        ]);
    }

    public function hr(Request $request): Response
    {
        return $this->render('reports/hr', [
            'pageTitle'   => 'HR Report',
            'breadcrumbs' => ['Reports' => '/reports', 'HR' => null],
        ]);
    }

    public function customerAging(Request $request): Response
    {
        return $this->render('reports/customer-aging', [
            'pageTitle'   => 'Customer Aging Report',
            'breadcrumbs' => ['Reports' => '/reports', 'Customer Aging' => null],
        ]);
    }

    public function supplierAging(Request $request): Response
    {
        return $this->render('reports/supplier-aging', [
            'pageTitle'   => 'Supplier Aging Report',
            'breadcrumbs' => ['Reports' => '/reports', 'Supplier Aging' => null],
        ]);
    }

    public function profitLoss(Request $request): Response
    {
        return $this->render('reports/profit-loss', [
            'pageTitle'   => 'Profit & Loss Report',
            'breadcrumbs' => ['Reports' => '/reports', 'Profit & Loss' => null],
        ]);
    }

    public function branchComparison(Request $request): Response
    {
        return $this->render('reports/branch-comparison', [
            'pageTitle'   => 'Branch Comparison Report',
            'breadcrumbs' => ['Reports' => '/reports', 'Branch Comparison' => null],
        ]);
    }

    public function export(Request $request): Response
    {
        $this->error('Export is not yet implemented.');
        return $this->redirect('/reports');
    }
}
