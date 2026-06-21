<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function checkIn(int $employeeId, string $date, ?string $checkInTime = null): Attendance
    {
        $existing = Attendance::where('employee_id', $employeeId)->where('date', $date)->first();
        if ($existing) {
            throw new \RuntimeException('Attendance already recorded for this date.');
        }

        return Attendance::create([
            'employee_id'    => $employeeId,
            'date'           => $date,
            'check_in_time'  => $checkInTime ?? now()->toTimeString(),
            'status'         => 'present',
        ]);
    }

    public function checkOut(int $attendanceId, ?string $checkOutTime = null): Attendance
    {
        $record = Attendance::findOrFail($attendanceId);
        $record->update(['check_out_time' => $checkOutTime ?? now()->toTimeString()]);

        return $record->fresh();
    }

    public function create(array $data): Attendance
    {
        $existing = Attendance::where('employee_id', $data['employee_id'])
            ->where('date', $data['date'])
            ->first();

        if ($existing) {
            throw new \RuntimeException('Attendance already recorded for this date.');
        }

        return Attendance::create($data);
    }

    public function update(int $id, array $data): Attendance
    {
        $record = Attendance::findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): void
    {
        Attendance::findOrFail($id)->delete();
    }

    public function paginate(int $branchId, array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        $query = Attendance::with('employee')
            ->whereHas('employee', fn($q) => $q->where('branch_id', $branchId));

        if (!empty($filters['date'])) {
            $query->where('date', $filters['date']);
        }
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $query->whereMonth('date', $filters['month'])->whereYear('date', $filters['year']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('date')->paginate($perPage);
    }

    public function monthlySummary(int $employeeId, int $month, int $year): array
    {
        $rows = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $workingDays   = Carbon::create($year, $month)->daysInMonth;
        $presentDays   = 0;
        $overtimeHours = 0;

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
            'absent_days'    => $workingDays - $presentDays,
            'overtime_hours' => $overtimeHours,
            'records'        => $rows,
        ];
    }

    public function branchReport(int $branchId, int $month, int $year): array
    {
        $employees = Employee::where('branch_id', $branchId)->where('status', 'active')->get();
        $report    = [];

        foreach ($employees as $employee) {
            $summary  = $this->monthlySummary($employee->id, $month, $year);
            $report[] = [
                'employee'    => $employee,
                'present'     => $summary['present_days'],
                'absent'      => $summary['absent_days'],
                'overtime'    => $summary['overtime_hours'],
            ];
        }

        return $report;
    }
}
