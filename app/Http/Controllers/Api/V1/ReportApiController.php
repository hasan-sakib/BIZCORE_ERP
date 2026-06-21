<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends BaseApiController
{
    public function __construct(private readonly ReportService $reportService) {}

    public function dashboard(): JsonResponse
    {
        return $this->success($this->reportService->getDashboardMetrics());
    }

    public function sales(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));
        return $this->success($this->reportService->getSalesReport($from, $to, $request->integer('branch_id') ?: null));
    }

    public function purchasing(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));
        $data = \App\Models\PurchaseOrder::whereBetween('order_date', [$from, $to])
            ->with('supplier')
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->first();
        return $this->success($data);
    }

    public function inventory(Request $request): JsonResponse
    {
        return $this->success($this->reportService->getInventoryReport($request->all()));
    }

    public function payroll(Request $request): JsonResponse
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);
        return $this->success($this->reportService->getPayrollReport($month, $year, null));
    }

    public function expenses(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));
        return $this->success($this->reportService->getExpenseReport($from, $to, null));
    }

    public function vat(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));
        $data = \App\Models\Invoice::whereBetween('invoice_date', [$from, $to])
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('SUM(vat_amount) as output_vat, SUM(sub_total) as taxable_amount, COUNT(*) as invoice_count')
            ->first();
        return $this->success(['from' => $from, 'to' => $to, ...(array)$data]);
    }
}
