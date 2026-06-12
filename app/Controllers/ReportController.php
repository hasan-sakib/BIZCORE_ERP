<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Http\Request;
use App\Services\AccountingService;
use App\Services\ReportService;

class ReportController extends BaseController
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly AccountingService $accountingService
    ) {}

    public function sales(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;
        $from     = $request->query('from', date('Y-m-01'));
        $to       = $request->query('to', date('Y-m-d'));

        $report = $this->reportService->getSalesReport($branchId, $from, $to);

        $this->view('reports/sales', [
            'pageTitle'   => 'Sales Report',
            'breadcrumbs' => ['Reports' => null, 'Sales' => null],
            'report'      => $report,
            'currentUser' => $user,
            'filters'     => compact('from', 'to'),
        ]);
    }

    public function expenses(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;
        $from     = $request->query('from', date('Y-m-01'));
        $to       = $request->query('to', date('Y-m-d'));

        $report = $this->reportService->getExpenseReport($branchId, $from, $to);

        $this->view('reports/expenses', [
            'pageTitle'   => 'Expense Report',
            'breadcrumbs' => ['Reports' => null, 'Expenses' => null],
            'report'      => $report,
            'currentUser' => $user,
            'filters'     => compact('from', 'to'),
        ]);
    }

    public function payroll(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;
        $year     = (int)($request->query('year') ?? date('Y'));

        $report = $this->reportService->getPayrollReport($branchId, $year);

        $this->view('reports/payroll', [
            'pageTitle'   => 'Payroll Report',
            'breadcrumbs' => ['Reports' => null, 'Payroll' => null],
            'months'      => $report,
            'year'        => $year,
            'currentUser' => $user,
        ]);
    }

    public function inventory(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;
        $report   = $this->reportService->getInventoryReport($branchId);

        $this->view('reports/inventory', [
            'pageTitle'   => 'Inventory Report',
            'breadcrumbs' => ['Reports' => null, 'Inventory' => null],
            'report'      => $report,
            'currentUser' => $user,
        ]);
    }

    public function trialBalance(Request $request): void
    {
        $asOf   = $request->query('as_of', date('Y-m-d'));
        $result = $this->accountingService->getTrialBalance($asOf);

        $this->view('accounting/trial_balance', [
            'pageTitle'   => 'Trial Balance',
            'breadcrumbs' => ['Accounting' => null, 'Trial Balance' => null],
            'accounts'    => $result,
            'asOf'        => $asOf,
            'currentUser' => $this->currentUser(),
        ]);
    }

    public function incomeStatement(Request $request): void
    {
        $from   = $request->query('from', date('Y-01-01'));
        $to     = $request->query('to', date('Y-m-d'));
        $result = $this->accountingService->getIncomeStatement($from, $to);

        $this->view('reports/income_statement', [
            'pageTitle'   => 'Income Statement',
            'breadcrumbs' => ['Reports' => null, 'Income Statement' => null],
            'result'      => $result,
            'currentUser' => $this->currentUser(),
            'filters'     => compact('from', 'to'),
        ]);
    }

    public function balanceSheet(Request $request): void
    {
        $asOf   = $request->query('as_of', date('Y-m-d'));
        $result = $this->accountingService->getBalanceSheet($asOf);

        $this->view('reports/balance_sheet', [
            'pageTitle'   => 'Balance Sheet',
            'breadcrumbs' => ['Reports' => null, 'Balance Sheet' => null],
            'result'      => $result,
            'asOf'        => $asOf,
            'currentUser' => $this->currentUser(),
        ]);
    }

    public function vatReturn(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;
        $from     = $request->query('from', date('Y-m-01'));
        $to       = $request->query('to', date('Y-m-d'));

        $salesReport = $this->reportService->getSalesReport($branchId, $from, $to);

        $this->view('reports/vat_return', [
            'pageTitle'   => 'VAT Return',
            'breadcrumbs' => ['Reports' => null, 'VAT Return' => null],
            'outputVat'   => $salesReport['summary']['total_vat'] ?? 0,
            'vatRate'     => config('vat.standard_rate', 15),
            'currentUser' => $user,
            'filters'     => compact('from', 'to'),
        ]);
    }
}
