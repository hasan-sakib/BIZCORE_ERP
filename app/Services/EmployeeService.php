<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Entities\Employee;
use App\Repositories\BaseRepository;

class EmployeeService
{
    public function __construct(private readonly Database $db) {}

    public function create(array $data): Employee
    {
        $branchId = (int)$data['branch_id'];
        $data['employee_number'] = $this->generateEmployeeNumber($branchId);

        if (!empty($data['bank_details']) && is_array($data['bank_details'])) {
            $data['bank_details'] = encrypt((string) json_encode($data['bank_details']));
        }

        if (!empty($data['address']) && is_array($data['address'])) {
            $data['address'] = (string) json_encode($data['address']);
        }

        if (!empty($data['emergency_contact']) && is_array($data['emergency_contact'])) {
            $data['emergency_contact'] = (string) json_encode($data['emergency_contact']);
        }

        $data['documents'] = (string) json_encode([]);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = $this->db->table('employees')->insert($data);

        $employee = $this->findById($id);
        if ($employee === null) {
            throw new \RuntimeException("Failed to retrieve employee record after creation.");
        }
        return $employee;
    }

    public function update(int $id, array $data): Employee
    {
        if (!empty($data['bank_details']) && is_array($data['bank_details'])) {
            $data['bank_details'] = encrypt((string) json_encode($data['bank_details']));
        }
        if (!empty($data['address']) && is_array($data['address'])) {
            $data['address'] = (string) json_encode($data['address']);
        }
        if (!empty($data['emergency_contact']) && is_array($data['emergency_contact'])) {
            $data['emergency_contact'] = (string) json_encode($data['emergency_contact']);
        }
        $data['updated_at'] = now();

        $this->db->table('employees')->where('id', $id)->update($data);
        
        $employee = $this->findById($id);
        if ($employee === null) {
            throw new \RuntimeException("Failed to retrieve employee record after update.");
        }
        return $employee;
    }

    public function findById(int $id): ?Employee
    {
        $row = $this->db->fetchOne(
            "SELECT e.*, d.name AS department_name, des.name AS designation_name, b.name AS branch_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN designations des ON des.id = e.designation_id
             LEFT JOIN branches b ON b.id = e.branch_id
             WHERE e.id = ? AND e.deleted_at IS NULL",
            [$id]
        );
        return $row ? Employee::fromArray($row) : null;
    }

    public function paginate(int $branchId, array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = ['e.branch_id = ?', 'e.deleted_at IS NULL'];
        $bindings = [$branchId];

        if (!empty($filters['status'])) {
            $where[] = 'e.status = ?';
            $bindings[] = $filters['status'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'e.department_id = ?';
            $bindings[] = (int)$filters['department_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ? OR e.email LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $bindings = array_merge($bindings, [$term, $term, $term, $term]);
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM employees e {$whereClause}",
            $bindings
        );

        $offset = ($page - 1) * $perPage;
        $rows = $this->db->fetchAll(
            "SELECT e.*, d.name AS department_name, des.name AS designation_name, b.name AS branch_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN designations des ON des.id = e.designation_id
             LEFT JOIN branches b ON b.id = e.branch_id
             {$whereClause}
             ORDER BY e.employee_number ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return [
            'data'       => array_map(fn($r) => Employee::fromArray($r), $rows),
            'pagination' => paginate($total, $page, $perPage),
        ];
    }

    public function delete(int $id): void
    {
        $this->db->table('employees')
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'status' => 'inactive']);
    }

    public function transfer(int $employeeId, array $transferData): void
    {
        $employee = $this->findById($employeeId);
        if (!$employee) {
            throw new \RuntimeException("Employee not found.");
        }

        $this->db->transaction(function () use ($employee, $employeeId, $transferData) {
            $toDepartmentId = $transferData['to_department_id'] ?? $employee->departmentId;
            $this->db->table('employee_transfers')->insert([
                'employee_id'        => $employeeId,
                'from_branch_id'     => $employee->branchId,
                'to_branch_id'       => $transferData['to_branch_id'],
                'from_department_id' => $employee->departmentId,
                'to_department_id'   => $toDepartmentId,
                'transfer_date'      => $transferData['transfer_date'],
                'reason'             => $transferData['reason'] ?? null,
                'approved_by'        => $transferData['approved_by'] ?? 0,
                'status'             => 'approved',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            $this->db->table('employees')->where('id', $employeeId)->update([
                'branch_id'     => $transferData['to_branch_id'],
                'department_id' => $toDepartmentId,
                'designation_id' => $transferData['to_designation_id'] ?? $employee->designationId,
                'updated_at'    => now(),
            ]);
        });
    }

    public function generateEmployeeNumber(int $branchId): string
    {
        $branch = $this->db->table('branches')->where('id', $branchId)->first();
        $prefix = strtoupper($branch['code'] ?? 'EMP');
        $year   = date('Y');

        $count = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM employees WHERE branch_id = ? AND YEAR(join_date) = ?",
            [$branchId, $year]
        );

        return "{$prefix}-{$year}-" . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function getStats(int $branchId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM employees WHERE branch_id = ? AND deleted_at IS NULL GROUP BY status",
            [$branchId]
        );
        $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'terminated' => 0, 'on_leave' => 0];
        foreach ($rows as $row) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }
        return $stats;
    }
}
