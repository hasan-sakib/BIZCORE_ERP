<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class PayrollService
{
    public function __construct(private readonly Database $db) {}

    public function processSingle(int $employeeId, int $month, int $year): array
    {
        $existing = $this->db->table('payroll')
            ->where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing && $existing['status'] !== 'draft') {
            throw new \RuntimeException("Payroll already processed for this period.");
        }

        $structure = $this->db->table('salary_structures')
            ->where('employee_id', $employeeId)
            ->where('is_active', 1)
            ->orderBy('effective_date', 'DESC')
            ->first();

        if (!$structure) {
            throw new \RuntimeException("No active salary structure for employee #{$employeeId}.");
        }

        $components = $this->db->fetchAll(
            "SELECT * FROM salary_components WHERE salary_structure_id = ?",
            [(int)$structure['id']]
        );

        $attendance = $this->getAttendanceSummary($employeeId, $month, $year);
        $workingDays = $attendance['working_days'];
        $presentDays = $attendance['present_days'];
        $absentDays  = $workingDays - $presentDays;

        $basicSalary = $presentDays > 0 && $workingDays > 0
            ? (float)$structure['basic_salary'] * ($presentDays / $workingDays)
            : 0;

        $totalAllowances = 0;
        $totalDeductions = 0;

        foreach ($components as $comp) {
            if ($comp['is_percentage']) {
                $amount = $basicSalary * ((float)$comp['percentage'] / 100);
            } else {
                $amount = (float)$comp['amount'];
            }

            if ($comp['component_type'] === 'allowance') {
                $totalAllowances += $amount;
            } else {
                $totalDeductions += $amount;
            }
        }

        $overtimeHours  = (float)($attendance['overtime_hours'] ?? 0);
        $hourlyRate     = (float)$structure['basic_salary'] / 208;
        $overtimeAmount = $overtimeHours * $hourlyRate * 2;

        $grossSalary = $basicSalary + $totalAllowances + $overtimeAmount;
        $taxAmount   = $this->calculateTax($grossSalary * 12) / 12;
        $netSalary   = $grossSalary - $totalDeductions - $taxAmount;

        $payrollData = [
            'employee_id'      => $employeeId,
            'branch_id'        => $this->getEmployeeBranchId($employeeId),
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
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        if ($existing) {
            $this->db->table('payroll')->where('id', (int)$existing['id'])->update($payrollData);
            $id = (int)$existing['id'];
        } else {
            $id = $this->db->table('payroll')->insert($payrollData);
        }

        return array_merge(['id' => $id], $payrollData);
    }

    public function processMonthly(int $branchId, int $month, int $year): array
    {
        $employees = $this->db->fetchAll(
            "SELECT id FROM employees WHERE branch_id = ? AND status = 'active' AND deleted_at IS NULL",
            [$branchId]
        );

        $results = ['processed' => 0, 'errors' => []];
        foreach ($employees as $emp) {
            try {
                $this->processSingle((int)$emp['id'], $month, $year);
                $results['processed']++;
            } catch (\Throwable $e) {
                $results['errors'][] = "Employee #{$emp['id']}: " . $e->getMessage();
            }
        }
        return $results;
    }

    public function calculateTax(float $annualIncome): float
    {
        // Bangladesh Income Tax Slabs FY 2024-25
        $tax = 0;
        $slabs = [
            [350000, 0],
            [100000, 0.05],
            [300000, 0.10],
            [400000, 0.15],
            [PHP_INT_MAX, 0.20],
        ];

        $remaining = max(0, $annualIncome - 350000);

        foreach (array_slice($slabs, 1) as $slab) {
            if ($remaining <= 0) break;
            $taxable = min($remaining, $slab[0]);
            $tax += $taxable * $slab[1];
            $remaining -= $taxable;
        }

        return $tax;
    }

    private function getAttendanceSummary(int $employeeId, int $month, int $year): array
    {
        $rows = $this->db->fetchAll(
            "SELECT status, SUM(overtime_hours) AS total_overtime
             FROM attendance
             WHERE employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
             GROUP BY status",
            [$employeeId, $month, $year]
        );

        $workingDays   = (int)cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $presentDays   = 0;
        $overtimeHours = 0;

        foreach ($rows as $row) {
            if (in_array($row['status'], ['present', 'late', 'half_day'])) {
                $presentDays += $row['status'] === 'half_day' ? 0.5 : 1;
            }
            $overtimeHours += (float)$row['total_overtime'];
        }

        return [
            'working_days'  => $workingDays,
            'present_days'  => $presentDays,
            'overtime_hours' => $overtimeHours,
        ];
    }

    private function getEmployeeBranchId(int $employeeId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT branch_id FROM employees WHERE id = ?",
            [$employeeId]
        );
    }

    public function getSummary(int $branchId, int $month, int $year): array
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) AS employee_count,
                SUM(gross_salary) AS total_gross,
                SUM(net_salary) AS total_net,
                SUM(tax_amount) AS total_tax,
                SUM(total_allowances) AS total_allowances,
                SUM(total_deductions) AS total_deductions,
                SUM(overtime_amount) AS total_overtime
             FROM payroll
             WHERE branch_id = ? AND month = ? AND year = ? AND status != 'cancelled'",
            [$branchId, $month, $year]
        ) ?? [];
    }
}
