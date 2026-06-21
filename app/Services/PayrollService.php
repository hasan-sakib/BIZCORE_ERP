<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function processSingle(int $employeeId, int $month, int $year): Payroll
    {
        $existing = Payroll::where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing && $existing->status !== 'draft') {
            throw new \RuntimeException('Payroll already processed for this period.');
        }

        $structure = SalaryStructure::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->latest('effective_date')
            ->firstOrFail();

        $components = SalaryComponent::where('salary_structure_id', $structure->id)->get();

        $attendance  = $this->getAttendanceSummary($employeeId, $month, $year);
        $workingDays = $attendance['working_days'];
        $presentDays = $attendance['present_days'];
        $absentDays  = $workingDays - $presentDays;

        $basicSalary = ($presentDays > 0 && $workingDays > 0)
            ? (float) $structure->basic_salary * ($presentDays / $workingDays)
            : 0;

        $totalAllowances = 0.0;
        $totalDeductions = 0.0;

        foreach ($components as $comp) {
            $amount = $comp->is_percentage
                ? $basicSalary * ((float) $comp->percentage / 100)
                : (float) $comp->amount;

            if ($comp->component_type === 'allowance') {
                $totalAllowances += $amount;
            } else {
                $totalDeductions += $amount;
            }
        }

        $overtimeHours  = $attendance['overtime_hours'];
        $hourlyRate     = (float) $structure->basic_salary / 208;
        $overtimeAmount = $overtimeHours * $hourlyRate * 2;

        $grossSalary = $basicSalary + $totalAllowances + $overtimeAmount;
        $taxAmount   = $this->calculateTax($grossSalary * 12) / 12;
        $netSalary   = $grossSalary - $totalDeductions - $taxAmount;

        $branchId = Employee::findOrFail($employeeId)->branch_id;

        $payload = [
            'employee_id'      => $employeeId,
            'branch_id'        => $branchId,
            'month'            => $month,
            'year'             => $year,
            'basic_salary'     => round($basicSalary, 2),
            'total_allowances' => round($totalAllowances, 2),
            'total_deductions' => round($totalDeductions, 2),
            'gross_salary'     => round($grossSalary, 2),
            'tax_amount'       => round($taxAmount, 2),
            'net_salary'       => round($netSalary, 2),
            'working_days'     => $workingDays,
            'present_days'     => $presentDays,
            'absent_days'      => $absentDays,
            'overtime_hours'   => $overtimeHours,
            'overtime_amount'  => round($overtimeAmount, 2),
            'status'           => 'processed',
        ];

        if ($existing) {
            $existing->update($payload);
            return $existing->fresh();
        }

        return Payroll::create($payload);
    }

    public function processMonthly(int $branchId, int $month, int $year): array
    {
        $employees = Employee::where('branch_id', $branchId)->where('status', 'active')->get();

        $results = ['processed' => 0, 'errors' => []];
        foreach ($employees as $employee) {
            try {
                $this->processSingle($employee->id, $month, $year);
                $results['processed']++;
            } catch (\Throwable $e) {
                $results['errors'][] = "Employee #{$employee->id}: " . $e->getMessage();
            }
        }
        return $results;
    }

    public function approve(int $payrollId, int $approvedBy): Payroll
    {
        $payroll = Payroll::findOrFail($payrollId);

        if ($payroll->status !== 'processed') {
            throw new \RuntimeException('Only processed payrolls can be approved.');
        }

        $payroll->update(['status' => 'approved']);
        return $payroll->fresh();
    }

    public function disburse(int $payrollId): Payroll
    {
        $payroll = Payroll::findOrFail($payrollId);

        if ($payroll->status !== 'approved') {
            throw new \RuntimeException('Payroll must be approved before disbursement.');
        }

        $payroll->update(['status' => 'paid', 'paid_at' => now()]);
        return $payroll->fresh();
    }

    public function calculateTax(float $annualIncome): float
    {
        // Bangladesh Income Tax Slabs FY 2024-25
        $exemption = 350000;
        if ($annualIncome <= $exemption) return 0;

        $slabs = [
            [100000, 0.05],
            [300000, 0.10],
            [400000, 0.15],
            [PHP_INT_MAX, 0.20],
        ];

        $tax       = 0;
        $remaining = $annualIncome - $exemption;

        foreach ($slabs as [$limit, $rate]) {
            if ($remaining <= 0) break;
            $taxable   = min($remaining, $limit);
            $tax       += $taxable * $rate;
            $remaining -= $taxable;
        }

        return $tax;
    }

    public function getSummary(int $branchId, int $month, int $year): array
    {
        return DB::table('payroll')
            ->where('branch_id', $branchId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COUNT(*) AS employee_count, SUM(gross_salary) AS total_gross,
                         SUM(net_salary) AS total_net, SUM(tax_amount) AS total_tax,
                         SUM(total_allowances) AS total_allowances,
                         SUM(total_deductions) AS total_deductions,
                         SUM(overtime_amount) AS total_overtime')
            ->first()?->toArray() ?? [];
    }

    private function getAttendanceSummary(int $employeeId, int $month, int $year): array
    {
        $rows = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $workingDays   = Carbon::create($year, $month)->daysInMonth;
        $presentDays   = 0.0;
        $overtimeHours = 0.0;

        foreach ($rows as $row) {
            if (in_array($row->status, ['present', 'late'])) {
                $presentDays += 1;
            } elseif ($row->status === 'half_day') {
                $presentDays += 0.5;
            }
            $overtimeHours += (float) $row->overtime_hours;
        }

        return [
            'working_days'   => $workingDays,
            'present_days'   => $presentDays,
            'overtime_hours' => $overtimeHours,
        ];
    }
}
