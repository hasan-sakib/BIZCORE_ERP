<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollApiController extends BaseApiController
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function index(Request $request): JsonResponse
    {
        $payrolls = Payroll::with('employee')
            ->when($request->get('month'), fn ($q, $m) => $q->where('month', $m))
            ->when($request->get('year'),  fn ($q, $y) => $q->where('year', $y))
            ->latest()
            ->paginate(30);
        return $this->paginate($payrolls);
    }

    public function process(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month'     => ['required', 'integer', 'min:1', 'max:12'],
            'year'      => ['required', 'integer', 'min:2000'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $this->payrollService->processMonthly($data['month'], $data['year'], $data['branch_id'] ?? null);
        return $this->success(['message' => 'Payroll processed successfully.']);
    }

    public function approve(int $id): JsonResponse
    {
        return $this->success($this->payrollService->approve($id, $this->currentUser()?->id));
    }

    public function disburse(int $id): JsonResponse
    {
        return $this->success($this->payrollService->disburse($id, $this->currentUser()?->id));
    }

    public function payslip(int $id): JsonResponse
    {
        return $this->success(Payroll::with(['employee.department', 'employee.designation'])->findOrFail($id));
    }

    public function summary(Request $request): JsonResponse
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);
        return $this->success($this->payrollService->getSummary($month, $year, null));
    }
}
