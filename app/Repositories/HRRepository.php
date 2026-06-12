<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * HRRepository
 *
 * Handles all SQL for departments and designations.
 */
final class HRRepository extends BaseRepository
{
    // =========================================================================
    // Departments
    // =========================================================================

    /**
     * Return all non-deleted departments, optionally filtered by a search term.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allDepartments(string $search = ''): array
    {
        $params = [];
        $where  = 'WHERE d.deleted_at IS NULL';

        if ($search !== '') {
            $where           .= ' AND (d.name LIKE :search OR d.code LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        return $this->fetchAll(
            <<<SQL
            SELECT d.*
            FROM departments d
            {$where}
            ORDER BY d.name ASC
            SQL,
            $params,
        );
    }

    /**
     * Find a single department by ID (not soft-deleted).
     *
     * @return array<string, mixed>|null
     */
    public function findDepartment(int $id): ?array
    {
        return $this->fetchOne(
            <<<SQL
            SELECT d.*
            FROM departments d
            WHERE d.id = :id AND d.deleted_at IS NULL
            LIMIT 1
            SQL,
            [':id' => $id],
        );
    }

    /**
     * Insert a new department and return its generated ID.
     *
     * @param array<string, mixed> $data
     */
    public function createDepartment(array $data): int
    {
        $this->modify(
            <<<SQL
            INSERT INTO departments
                (name, code, description, branch_id, status, created_at, updated_at)
            VALUES
                (:name, :code, :description, :branch_id, :status, NOW(), NOW())
            SQL,
            [
                ':name'        => $data['name'],
                ':code'        => $data['code'] ?? null,
                ':description' => $data['description'] ?? null,
                ':branch_id'   => !empty($data['branch_id']) ? (int) $data['branch_id'] : null,
                ':status'      => $data['status'] ?? 'active',
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing department row.
     *
     * @param array<string, mixed> $data
     */
    public function updateDepartment(int $id, array $data): void
    {
        $this->modify(
            <<<SQL
            UPDATE departments
            SET
                name        = :name,
                code        = :code,
                description = :description,
                status      = :status,
                updated_at  = NOW()
            WHERE id = :id AND deleted_at IS NULL
            SQL,
            [
                ':name'        => $data['name'],
                ':code'        => $data['code'] ?? null,
                ':description' => $data['description'] ?? null,
                ':status'      => $data['status'] ?? 'active',
                ':id'          => $id,
            ],
        );
    }

    /**
     * Soft-delete a department.
     */
    public function softDeleteDepartment(int $id): void
    {
        $this->modify(
            'UPDATE departments SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id],
        );
    }

    // =========================================================================
    // Designations
    // =========================================================================

    /**
     * Return all non-deleted designations, optionally filtered by search and/or department.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allDesignations(string $search = '', int $deptId = 0): array
    {
        $clauses = ['des.deleted_at IS NULL'];
        $params  = [];

        if ($search !== '') {
            $clauses[]         = 'des.name LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        if ($deptId > 0) {
            $clauses[]          = 'des.department_id = :dept_id';
            $params[':dept_id'] = $deptId;
        }

        $where = 'WHERE ' . implode(' AND ', $clauses);

        return $this->fetchAll(
            <<<SQL
            SELECT des.*, d.name AS department_name
            FROM designations des
            LEFT JOIN departments d ON d.id = des.department_id
            {$where}
            ORDER BY des.name ASC
            SQL,
            $params,
        );
    }

    /**
     * Find a single designation by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findDesignation(int $id): ?array
    {
        return $this->fetchOne(
            <<<SQL
            SELECT des.*, d.name AS department_name
            FROM designations des
            LEFT JOIN departments d ON d.id = des.department_id
            WHERE des.id = :id AND des.deleted_at IS NULL
            LIMIT 1
            SQL,
            [':id' => $id],
        );
    }

    /**
     * Insert a new designation and return its generated ID.
     *
     * @param array<string, mixed> $data
     */
    public function createDesignation(array $data): int
    {
        $this->modify(
            <<<SQL
            INSERT INTO designations
                (name, department_id, description, status, created_at, updated_at)
            VALUES
                (:name, :department_id, :description, :status, NOW(), NOW())
            SQL,
            [
                ':name'          => $data['name'],
                ':department_id' => (int) $data['department_id'],
                ':description'   => $data['description'] ?? null,
                ':status'        => $data['status'] ?? 'active',
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing designation row.
     *
     * @param array<string, mixed> $data
     */
    public function updateDesignation(int $id, array $data): void
    {
        $this->modify(
            <<<SQL
            UPDATE designations
            SET
                name          = :name,
                department_id = :department_id,
                description   = :description,
                status        = :status,
                updated_at    = NOW()
            WHERE id = :id AND deleted_at IS NULL
            SQL,
            [
                ':name'          => $data['name'],
                ':department_id' => (int) $data['department_id'],
                ':description'   => $data['description'] ?? null,
                ':status'        => $data['status'] ?? 'active',
                ':id'            => $id,
            ],
        );
    }

    /**
     * Soft-delete a designation.
     */
    public function softDeleteDesignation(int $id): void
    {
        $this->modify(
            'UPDATE designations SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id],
        );
    }
}
