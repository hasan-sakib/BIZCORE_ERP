<?php

declare(strict_types=1);

namespace App\Entities;

class Payroll
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $employeeId,
        public readonly int     $branchId,
        public readonly int     $month,
        public readonly int     $year,
        public readonly float   $basicSalary,
        public readonly float   $grossSalary,
        public readonly float   $netSalary,
        public readonly float   $taxAmount,
        public readonly float   $totalDeductions,
        public readonly float   $totalAllowances,
        public readonly float   $overtimeAmount,
        public readonly int     $presentDays,
        public readonly int     $absentDays,
        public readonly float   $overtimeHours,
        public readonly string  $status,
        public readonly ?string $processedAt,
        public readonly ?string $employeeName,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:               (int)$data['id'],
            employeeId:       (int)$data['employee_id'],
            branchId:         (int)$data['branch_id'],
            month:            (int)$data['month'],
            year:             (int)$data['year'],
            basicSalary:      (float)$data['basic_salary'],
            grossSalary:      (float)$data['gross_salary'],
            netSalary:        (float)$data['net_salary'],
            taxAmount:        (float)($data['tax_amount'] ?? 0),
            totalDeductions:  (float)($data['total_deductions'] ?? 0),
            totalAllowances:  (float)($data['total_allowances'] ?? 0),
            overtimeAmount:   (float)($data['overtime_amount'] ?? 0),
            presentDays:      (int)($data['present_days'] ?? 0),
            absentDays:       (int)($data['absent_days'] ?? 0),
            overtimeHours:    (float)($data['overtime_hours'] ?? 0),
            status:           $data['status'] ?? 'draft',
            processedAt:      $data['processed_at'] ?? null,
            employeeName:     $data['employee_name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'employee_id'      => $this->employeeId,
            'branch_id'        => $this->branchId,
            'month'            => $this->month,
            'year'             => $this->year,
            'period'           => $this->getPeriodLabel(),
            'basic_salary'     => $this->basicSalary,
            'gross_salary'     => $this->grossSalary,
            'net_salary'       => $this->netSalary,
            'tax_amount'       => $this->taxAmount,
            'total_deductions' => $this->totalDeductions,
            'total_allowances' => $this->totalAllowances,
            'overtime_amount'  => $this->overtimeAmount,
            'present_days'     => $this->presentDays,
            'absent_days'      => $this->absentDays,
            'overtime_hours'   => $this->overtimeHours,
            'status'           => $this->status,
            'processed_at'     => $this->processedAt,
            'employee_name'    => $this->employeeName,
        ];
    }

    public function getPeriodLabel(): string
    {
        return date('F Y', (int) mktime(0, 0, 0, $this->month, 1, $this->year));
    }

    public function isProcessed(): bool
    {
        return in_array($this->status, ['processed', 'paid']);
    }
}
