<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * AttendanceRepository
 *
 * All SQL related to the `attendance` table.
 */
final class AttendanceRepository extends BaseRepository
{
    /**
     * Return a paginated attendance list with optional filters.
     *
     * Supported filter keys: employee_id, date_from, date_to, status.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, lastPage: int}
     */
    public function paginateRecords(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = parent::limitOffset($page, $perPage);

        $clauses = ['1=1'];
        $params  = [];

        if (!empty($filters['employee_id'])) {
            $clauses[]              = 'a.employee_id = :employee_id';
            $params[':employee_id'] = (int) $filters['employee_id'];
        }

        if (!empty($filters['date_from'])) {
            $clauses[]             = 'a.date >= :date_from';
            $params[':date_from']  = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[]           = 'a.date <= :date_to';
            $params[':date_to']  = $filters['date_to'];
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'a.status = :status';
            $params[':status'] = $filters['status'];
        }

        $where = 'WHERE ' . implode(' AND ', $clauses);

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM attendance a {$where}",
            $params,
        );

        $items = $this->fetchAll(
            <<<SQL
            SELECT
                a.*,
                e.first_name,
                e.last_name,
                e.employee_number
            FROM attendance a
            INNER JOIN employees e ON e.id = a.employee_id
            {$where}
            ORDER BY a.date DESC, a.id DESC
            LIMIT :limit OFFSET :offset
            SQL,
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $lastPage,
        ];
    }

    /**
     * Find a single attendance record by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            <<<SQL
            SELECT
                a.*,
                e.first_name,
                e.last_name,
                e.employee_number
            FROM attendance a
            INNER JOIN employees e ON e.id = a.employee_id
            WHERE a.id = :id
            LIMIT 1
            SQL,
            [':id' => $id],
        );
    }

    /**
     * Find today's attendance record for a given employee (if any).
     *
     * @return array<string, mixed>|null
     */
    public function findTodayForEmployee(int $employeeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM attendance WHERE employee_id = :employee_id AND date = CURDATE() LIMIT 1',
            [':employee_id' => $employeeId],
        );
    }

    /**
     * Insert a new attendance record and return the generated ID.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->modify(
            <<<SQL
            INSERT INTO attendance
                (employee_id, date, check_in, check_out, status, notes, created_at, updated_at)
            VALUES
                (:employee_id, :date, :check_in, :check_out, :status, :notes, NOW(), NOW())
            SQL,
            [
                ':employee_id' => (int) $data['employee_id'],
                ':date'        => $data['date'],
                ':check_in'    => !empty($data['check_in']) ? $data['check_in'] : null,
                ':check_out'   => !empty($data['check_out']) ? $data['check_out'] : null,
                ':status'      => $data['status'] ?? 'present',
                ':notes'       => $data['notes'] ?? null,
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing attendance record.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->modify(
            <<<SQL
            UPDATE attendance
            SET
                employee_id = :employee_id,
                date        = :date,
                check_in    = :check_in,
                check_out   = :check_out,
                status      = :status,
                notes       = :notes,
                updated_at  = NOW()
            WHERE id = :id
            SQL,
            [
                ':employee_id' => (int) $data['employee_id'],
                ':date'        => $data['date'],
                ':check_in'    => !empty($data['check_in']) ? $data['check_in'] : null,
                ':check_out'   => !empty($data['check_out']) ? $data['check_out'] : null,
                ':status'      => $data['status'] ?? 'present',
                ':notes'       => $data['notes'] ?? null,
                ':id'          => $id,
            ],
        );
    }

    /**
     * Hard-delete an attendance record.
     */
    public function delete(int $id): void
    {
        $this->modify(
            'DELETE FROM attendance WHERE id = :id',
            [':id' => $id],
        );
    }
}
