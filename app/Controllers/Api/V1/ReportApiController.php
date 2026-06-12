<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use App\Http\Request;
use App\Services\AccountingService;
use App\Services\ReportService;

class ReportApiController extends BaseApiController
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly AccountingService $accountingService
    ) {}

    public function dashboard(Request $request): void
    {
        $branchId = $this->getBranchId($request);
        $metrics  = $this->reportService->getDashboardMetrics($branchId);
        $this->success($metrics);
    }

    public function sales(Request $request): void
    {
        $branchId = $this->getBranchId($request);
        $from     = $request->query('from', date('Y-m-01'));
        $to       = $request->query('to', date('Y-m-d'));

        $report = $this->reportService->getSalesReport($branchId, $from, $to);
        $this->success(array_merge($report, ['period' => compact('from', 'to')]));
    }

    public function expenses(Request $request): void
    {
        $branchId = $this->getBranchId($request);
        $from     = $request->query('from', date('Y-m-01'));
        $to       = $request->query('to', date('Y-m-d'));

        $report = $this->reportService->getExpenseReport($branchId, $from, $to);
        $this->success(array_merge($report, ['period' => compact('from', 'to')]));
    }

    public function payroll(Request $request): void
    {
        $branchId = $this->getBranchId($request);
        $year     = (int)($request->query('year') ?? date('Y'));

        $report = $this->reportService->getPayrollReport($branchId, $year);
        $this->success(['year' => $year, 'months' => $report]);
    }

    public function inventory(Request $request): void
    {
        $branchId = $this->getBranchId($request);
        $report   = $this->reportService->getInventoryReport($branchId);
        $this->success($report);
    }

    public function trialBalance(Request $request): void
    {
        $asOf   = $request->query('as_of', date('Y-m-d'));
        $result = $this->accountingService->getTrialBalance($asOf);
        $this->success(['as_of' => $asOf, 'accounts' => $result]);
    }

    public function incomeStatement(Request $request): void
    {
        $from   = $request->query('from', date('Y-01-01'));
        $to     = $request->query('to', date('Y-m-d'));
        $result = $this->accountingService->getIncomeStatement($from, $to);
        $this->success(array_merge($result, ['period' => compact('from', 'to')]));
    }

    public function balanceSheet(Request $request): void
    {
        $asOf   = $request->query('as_of', date('Y-m-d'));
        $result = $this->accountingService->getBalanceSheet($asOf);
        $this->success(array_merge($result, ['as_of' => $asOf]));
    }

    public function vatReturn(Request $request): void
    {
        $branchId = $this->getBranchId($request);
        $from     = $request->query('from', date('Y-m-01'));
        $to       = $request->query('to', date('Y-m-d'));

        $salesVat = $this->reportService->getSalesReport($branchId, $from, $to);

        $this->success([
            'period'        => compact('from', 'to'),
            'output_vat'    => $salesVat['summary']['total_vat'] ?? 0,
            'net_vat_payable'=> $salesVat['summary']['total_vat'] ?? 0,
            'vat_rate'      => config('vat.standard_rate', 15),
        ]);
    }
}
