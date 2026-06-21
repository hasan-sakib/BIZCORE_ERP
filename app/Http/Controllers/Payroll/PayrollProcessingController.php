<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\BaseController;
use App\Models\Branch;
use App\Models\Department;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayrollProcessingController extends BaseController
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function index(Request $request): View
    {
        $month       = (int) $request->get('month', now()->month);
        $year        = (int) $request->get('year', now()->year);
        $branchId    = $request->get('branch_id', Auth::user()->branch_id);
        $summary     = $this->payrollService->getSummary($month, $year, (int) $branchId);
        $departments = Department::orderBy('name')->get();
        $branches    = Branch::where('status', 'active')->orderBy('name')->get();
        return view('payroll.process.index', compact('summary', 'month', 'year', 'branchId', 'departments', 'branches'));
    }

    public function run(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month'     => ['required', 'integer', 'min:1', 'max:12'],
            'year'      => ['required', 'integer', 'min:2000'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $this->payrollService->processMonthly(
            month:    $data['month'],
            year:     $data['year'],
            branchId: $data['branch_id'] ?? null,
        );

        $this->success("Payroll for {$data['month']}/{$data['year']} processed successfully.");
        return redirect()->route('payroll.index');
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $this->payrollService->approve($id, Auth::id());
        $this->success('Payroll approved.');
        return back();
    }

    public function disburse(Request $request, int $id): RedirectResponse
    {
        $this->payrollService->disburse($id, Auth::id());
        $this->success('Payroll marked as disbursed.');
        return back();
    }

    public function reports(Request $request): View
    {
        $month    = (int) $request->get('month', now()->month);
        $year     = (int) $request->get('year', now()->year);
        $summary  = $this->payrollService->getSummary($month, $year, null);
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        return view('payroll.reports.index', compact('summary', 'month', 'year', 'branches'));
    }
}
