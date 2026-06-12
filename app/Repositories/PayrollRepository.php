<?php

declare(strict_types=1);

namespace App\Repositories;

final class PayrollRepository extends BaseRepository
{
    public function allSalaryStructures(): array
    {
        return $this->fetchAll(
            'SELECT ss.*, e.first_name, e.last_name, e.employee_number
             FROM salary_structures ss
             JOIN employees e ON e.id = ss.employee_id
             WHERE ss.is_active = 1
             ORDER BY e.first_name ASC',
        );
    }

    public function findSalaryStructure(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT ss.*, e.first_name, e.last_name FROM salary_structures ss
             JOIN employees e ON e.id = ss.employee_id
             WHERE ss.id = :id LIMIT 1',
            [':id' => $id],
        );
    }

    public function createSalaryStructure(array $data): int
    {
        $this->modify(
            'INSERT INTO salary_structures (employee_id, basic_salary, gross_salary, net_salary, effective_date, is_active, created_by)
             VALUES (:employee_id, :basic_salary, :gross_salary, :net_salary, :effective_date, 1, :created_by)',
            [
                ':employee_id'  => $data['employee_id'],
                ':basic_salary' => $data['basic_salary'],
                ':gross_salary' => $data['gross_salary'] ?? $data['basic_salary'],
                ':net_salary'   => $data['net_salary'] ?? $data['basic_salary'],
                ':effective_date'=> $data['effective_date'],
                ':created_by'   => $data['created_by'] ?? null,
            ],
        );
        return (int) $this->pdo->lastInsertId();
    }

    public function paginatePayroll(array $filters, int $page, int $perPage): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['month'])) {
            $where[]          = 'p.month = :month';
            $params[':month'] = $filters['month'];
        }
        if (!empty($filters['year'])) {
            $where[]         = 'p.year = :year';
            $params[':year'] = $filters['year'];
        }
        if (!empty($filters['status'])) {
            $where[]            = 'p.status = :status';
            $params[':status']  = $filters['status'];
        }

        $w      = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $totalRow = $this->fetchOne(
            "SELECT COUNT(*) c FROM payroll p WHERE {$w}",
            $params,
        );
        $total = $totalRow ? (int) $totalRow['c'] : 0;

        $rows = $this->fetchAll(
            "SELECT p.*, e.first_name, e.last_name, e.employee_number
             FROM payroll p
             JOIN employees e ON e.id = p.employee_id
             WHERE {$w}
             ORDER BY p.year DESC, p.month DESC, e.first_name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params,
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public function allComponents(): array
    {
        return $this->fetchAll('SELECT * FROM salary_components ORDER BY type, name');
    }
}
