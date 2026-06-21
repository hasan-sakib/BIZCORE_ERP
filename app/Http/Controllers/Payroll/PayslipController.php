<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\BaseController;
use App\Models\Payroll;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayslipController extends BaseController
{
    public function __construct(private readonly PdfService $pdfService) {}

    public function index(Request $request): View
    {
        $user     = Auth::user();
        $employee = $user->employee;

        $payslips = Payroll::when($employee, fn ($q) => $q->where('employee_id', $employee->id))
            ->when($request->get('month'), fn ($q, $m) => $q->where('month', $m))
            ->when($request->get('year'),  fn ($q, $y) => $q->where('year', $y))
            ->with('employee')
            ->latest()
            ->paginate(12);

        return view('payroll.payslips.index', compact('payslips'));
    }

    public function show(int $id): View
    {
        $payroll = Payroll::with(['employee.department', 'employee.designation', 'employee.branch'])->findOrFail($id);
        return view('payroll.payslips.show', compact('payroll'));
    }

    public function pdf(int $id): Response
    {
        $payroll = Payroll::with(['employee.department', 'employee.designation', 'employee.branch'])->findOrFail($id);
        return $this->pdfService->download('payroll.payslips.pdf', compact('payroll'), "payslip-{$payroll->id}.pdf");
    }
}
