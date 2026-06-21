<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends BaseController
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(): View
    {
        $branchId = Auth::user()->branch_id;
        $metrics  = $this->reportService->getDashboardMetrics($branchId);
        return view('dashboard.index', compact('metrics'));
    }

    public function salesWidget(): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $metrics  = $this->reportService->getDashboardMetrics($branchId);
        return response()->json(['success' => true, 'data' => [
            'today_revenue'    => $metrics['current_revenue'],
            'pending_invoices' => $metrics['overdue_invoices'],
        ]]);
    }

    public function revenueWidget(): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $metrics  = $this->reportService->getDashboardMetrics($branchId);
        return response()->json(['success' => true, 'data' => $metrics['revenue_chart']]);
    }

    public function inventoryWidget(): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $data = $this->reportService->getInventoryReport($branchId);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function hrWidget(): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $metrics  = $this->reportService->getDashboardMetrics($branchId);
        return response()->json(['success' => true, 'data' => [
            'active_employees' => $metrics['active_employees'],
        ]]);
    }

    public function financeWidget(): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $metrics  = $this->reportService->getDashboardMetrics($branchId);
        return response()->json(['success' => true, 'data' => [
            'outstanding_receivables' => $metrics['outstanding_receivables'],
            'overdue_invoices'        => $metrics['overdue_invoices'],
        ]]);
    }
}
