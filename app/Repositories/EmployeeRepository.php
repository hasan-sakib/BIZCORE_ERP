<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * EmployeeRepository
 *
 * All SQL related to the `employees` table.
 */
final class EmployeeRepository extends BaseRepository
{
    /**
     * Return a paginated list of employees with optional filters.
     *
     * Supported filter keys: search, department_id, status.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, lastPage: int}
     */
    public function paginateRecords(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = parent::limitOffset($page, $perPage);

        $clauses = ['e.deleted_at IS NULL'];
        $params  = [];

        if (!empty($filters['search'])) {
            $clauses[]         = '(e.first_name LIKE :search OR e.last_name LIKE :search OR e.email LIKE :search OR e.employee_number LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['department_id'])) {
            $clauses[]              = 'e.department_id = :department_id';
            $params[':department_id'] = (int) $filters['department_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'e.status = :status';
            $params[':status'] = $filters['status'];
        }

        $where = 'WHERE ' . implode(' AND ', $clauses);

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM employees e {$where}",
            $params,
        );

        $items = $this->fetchAll(
            <<<SQL
            SELECT
                e.*,
                d.name  AS department_name,
                des.name AS designation_name
            FROM employees e
            LEFT JOIN departments  d   ON d.id   = e.department_id
            LEFT JOIN designations des ON des.id = e.designation_id
            {$where}
            ORDER BY e.first_name ASC, e.last_name ASC
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
     * Find a single employee by ID, joined with department and designation names.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            <<<SQL
            SELECT
                e.*,
                d.name   AS department_name,
                des.name AS designation_name
            FROM employees e
            LEFT JOIN departments  d   ON d.id   = e.department_id
            LEFT JOIN designations des ON des.id = e.designation_id
            WHERE e.id = :id AND e.deleted_at IS NULL
            LIMIT 1
            SQL,
            [':id' => $id],
        );
    }

    /**
     * Find an employee record by the linked user_id (for check-in/check-out).
     *
     * @return array<string, mixed>|null
     */
    public function findByUserId(int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM employees WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1',
            [':user_id' => $userId],
        );
    }

    /**
     * Insert a new employee and return the generated ID.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->modify(
            <<<SQL
            INSERT INTO employees
                (employee_number, user_id, branch_id, department_id, designation_id,
                 first_name, last_name, email, phone,
                 date_of_birth, gender, join_date,
                 status, address, emergency_contact,
                 created_at, updated_at)
            VALUES
                (:employee_number, :user_id, :branch_id, :department_id, :designation_id,
                 :first_name, :last_name, :email, :phone,
                 :date_of_birth, :gender, :join_date,
                 :status, :address, :emergency_contact,
                 NOW(), NOW())
            SQL,
            [
                ':employee_number'  => $data['employee_number'],
                ':user_id'          => !empty($data['user_id']) ? (int) $data['user_id'] : null,
                ':branch_id'        => (int) ($data['branch_id'] ?? 1),
                ':department_id'    => (int) $data['department_id'],
                ':designation_id'   => (int) $data['designation_id'],
                ':first_name'       => $data['first_name'],
                ':last_name'        => $data['last_name'],
                ':email'            => $data['email'] ?? '',
                ':phone'            => $data['phone'] ?? null,
                ':date_of_birth'    => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                ':gender'           => !empty($data['gender']) ? $data['gender'] : null,
                ':join_date'        => $data['join_date'],
                ':status'           => $data['status'] ?? 'active',
                ':address'          => !empty($data['address'])
                                         ? (is_array($data['address'])
                                             ? json_encode($data['address'])
                                             : $data['address'])
                                         : null,
                ':emergency_contact'=> !empty($data['emergency_contact'])
                                         ? (is_array($data['emergency_contact'])
                                             ? json_encode($data['emergency_contact'])
                                             : $data['emergency_contact'])
                                         : null,
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing employee row.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->modify(
            <<<SQL
            UPDATE employees
            SET
                department_id  = :department_id,
                designation_id = :designation_id,
                first_name     = :first_name,
                last_name      = :last_name,
                email          = :email,
                phone          = :phone,
                date_of_birth  = :date_of_birth,
                gender         = :gender,
                join_date      = :join_date,
                status         = :status,
                address        = :address,
                updated_at     = NOW()
            WHERE id = :id AND deleted_at IS NULL
            SQL,
            [
                ':department_id'  => (int) $data['department_id'],
                ':designation_id' => (int) $data['designation_id'],
                ':first_name'     => $data['first_name'],
                ':last_name'      => $data['last_name'],
                ':email'          => $data['email'] ?? '',
                ':phone'          => $data['phone'] ?? null,
                ':date_of_birth'  => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                ':gender'         => !empty($data['gender']) ? $data['gender'] : null,
                ':join_date'      => $data['join_date'],
                ':status'         => $data['status'] ?? 'active',
                ':address'        => !empty($data['address'])
                                        ? (is_array($data['address'])
                                            ? json_encode($data['address'])
                                            : $data['address'])
                                        : null,
                ':id'             => $id,
            ],
        );
    }

    /**
     * Soft-delete an employee.
     */
    public function softDelete(int $id): void
    {
        $this->modify(
            'UPDATE employees SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id],
        );
    }

    /**
     * Generate a unique employee number in the format EMP-XXXX.
     */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT id, first_name, last_name, employee_number FROM employees WHERE deleted_at IS NULL ORDER BY first_name ASC',
        );
    }

    public function generateEmployeeNumber(): string
    {
        $row   = $this->fetchOne('SELECT COUNT(*) AS total FROM employees');
        $count = isset($row['total']) ? (int) $row['total'] + 1 : 1;
        return 'EMP-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
