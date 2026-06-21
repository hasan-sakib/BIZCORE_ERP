<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\BaseController;
use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends BaseController
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(): View
    {
        return view('reports.index');
    }

    public function sales(Request $request): View
    {
        $from     = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to       = $request->get('to', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $report   = $this->reportService->getSalesReport($from, $to, $branchId ? (int) $branchId : null);
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        return view('reports.sales', compact('report', 'from', 'to', 'branches'));
    }

    public function inventory(Request $request): View
    {
        $report = $this->reportService->getInventoryReport($request->all());
        return view('reports.inventory', compact('report'));
    }

    public function hr(Request $request): View
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $report = $this->reportService->getPayrollReport($month, $year, null);
        return view('reports.hr', compact('report', 'month', 'year'));
    }

    public function financial(Request $request): View
    {
        $from   = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to     = $request->get('to', now()->format('Y-m-d'));
        $report = $this->reportService->getExpenseReport($from, $to, null);
        return view('reports.financial', compact('report', 'from', 'to'));
    }

    public function branchComparison(Request $request): View
    {
        $from     = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to       = $request->get('to', now()->format('Y-m-d'));
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $reports  = $branches->map(fn ($b) => [
            'branch' => $b,
            'sales'  => $this->reportService->getSalesReport($from, $to, $b->id),
        ]);
        return view('reports.branch-comparison', compact('reports', 'branches', 'from', 'to'));
    }
}
