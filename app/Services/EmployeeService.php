<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeTransfer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeService
{
    public function create(array $data): Employee
    {
        $branchId = (int) $data['branch_id'];
        $data['employee_number'] = $this->generateEmployeeNumber($branchId);

        $employee = DB::transaction(function () use ($data) {
            return Employee::create($data);
        });

        Log::info('Employee created.', ['employee_id' => $employee->id]);

        return $employee->load(['department', 'designation', 'branch']);
    }

    public function update(int $id, array $data): Employee
    {
        $employee = $this->findOrFail($id);
        $employee->update($data);

        Log::info('Employee updated.', ['employee_id' => $id]);

        return $employee->fresh(['department', 'designation', 'branch']);
    }

    public function delete(int $id): void
    {
        $employee = $this->findOrFail($id);
        $employee->update(['status' => 'inactive']);
        $employee->delete();

        Log::info('Employee deleted.', ['employee_id' => $id]);
    }

    public function transfer(int $employeeId, array $data): EmployeeTransfer
    {
        $employee = $this->findOrFail($employeeId);

        return DB::transaction(function () use ($employee, $employeeId, $data) {
            $transfer = EmployeeTransfer::create([
                'employee_id'        => $employeeId,
                'from_branch_id'     => $employee->branch_id,
                'to_branch_id'       => $data['to_branch_id'],
                'from_department_id' => $employee->department_id,
                'to_department_id'   => $data['to_department_id'] ?? $employee->department_id,
                'transfer_date'      => $data['transfer_date'],
                'reason'             => $data['reason'] ?? null,
                'approved_by'        => $data['approved_by'] ?? null,
                'status'             => 'approved',
            ]);

            $employee->update([
                'branch_id'      => $data['to_branch_id'],
                'department_id'  => $data['to_department_id'] ?? $employee->department_id,
                'designation_id' => $data['to_designation_id'] ?? $employee->designation_id,
            ]);

            return $transfer;
        });
    }

    public function paginate(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Employee::with(['department', 'designation'])
            ->where('branch_id', $branchId)
            ->orderBy('employee_number');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(fn($q) => $q
                ->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('employee_number', 'like', $term)
                ->orWhere('email', 'like', $term)
            );
        }

        return $query->paginate($perPage);
    }

    public function getStats(int $branchId): array
    {
        $rows = Employee::where('branch_id', $branchId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total'      => array_sum($rows),
            'active'     => $rows['active'] ?? 0,
            'inactive'   => $rows['inactive'] ?? 0,
            'terminated' => $rows['terminated'] ?? 0,
            'on_leave'   => $rows['on_leave'] ?? 0,
        ];
    }

    public function generateEmployeeNumber(int $branchId): string
    {
        $branch = Branch::find($branchId);
        $prefix = strtoupper($branch?->code ?? 'EMP');
        $year   = date('Y');
        $count  = Employee::where('branch_id', $branchId)
            ->whereYear('join_date', $year)
            ->count();

        return "{$prefix}-{$year}-" . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    private function findOrFail(int $id): Employee
    {
        return Employee::findOrFail($id);
    }
}
