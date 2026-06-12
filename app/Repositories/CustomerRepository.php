<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * CustomerRepository
 *
 * All SQL queries for the `customers` table.
 * Soft-deletes via deleted_at; business logic stays in the controller/service.
 */
final class CustomerRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Paginated list
    // -------------------------------------------------------------------------

    /**
     * Return a paginated, filtered list of customers.
     *
     * @param  array<string, mixed> $filters  Supported keys: search, status
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, lastPage: int, perPage: int}
     */
    public function paginate(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        [$whereClauses, $params] = $this->buildFilterClauses($filters);
        $where = 'WHERE ' . implode(' AND ', $whereClauses);

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM customers {$where}",
            $params,
        );

        $rows = $this->fetchAll(
            "SELECT * FROM customers {$where} ORDER BY name ASC LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return [
            'items'    => $rows,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $lastPage,
            'perPage'  => $perPage,
        ];
    }

    // -------------------------------------------------------------------------
    // Single record
    // -------------------------------------------------------------------------

    /**
     * Find a non-deleted customer by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id],
        );
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Insert a new customer and return the generated ID.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            <<<SQL
            INSERT INTO customers
                (name, email, phone, address, city, country, tax_number,
                 credit_limit, balance, status, notes, created_at, updated_at)
            VALUES
                (:name, :email, :phone, :address, :city, :country, :tax_number,
                 :credit_limit, 0, :status, :notes, NOW(), NOW())
            SQL,
            [
                ':name'         => $data['name'],
                ':email'        => $data['email']        ?? null,
                ':phone'        => $data['phone']        ?? null,
                ':address'      => $data['address']      ?? null,
                ':city'         => $data['city']         ?? null,
                ':country'      => $data['country']      ?? null,
                ':tax_number'   => $data['tax_number']   ?? null,
                ':credit_limit' => $data['credit_limit'] ?? 0,
                ':status'       => $data['status']       ?? 'active',
                ':notes'        => $data['notes']        ?? null,
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing customer row.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->modify(
            <<<SQL
            UPDATE customers
            SET name         = :name,
                email        = :email,
                phone        = :phone,
                address      = :address,
                city         = :city,
                country      = :country,
                tax_number   = :tax_number,
                credit_limit = :credit_limit,
                status       = :status,
                notes        = :notes,
                updated_at   = NOW()
            WHERE id = :id AND deleted_at IS NULL
            SQL,
            [
                ':id'           => $id,
                ':name'         => $data['name'],
                ':email'        => $data['email']        ?? null,
                ':phone'        => $data['phone']        ?? null,
                ':address'      => $data['address']      ?? null,
                ':city'         => $data['city']         ?? null,
                ':country'      => $data['country']      ?? null,
                ':tax_number'   => $data['tax_number']   ?? null,
                ':credit_limit' => $data['credit_limit'] ?? 0,
                ':status'       => $data['status']       ?? 'active',
                ':notes'        => $data['notes']        ?? null,
            ],
        );
    }

    /**
     * Soft-delete a customer by setting deleted_at.
     */
    public function softDelete(int $id): void
    {
        $this->modify(
            'UPDATE customers SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id],
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build reusable WHERE clauses and bindings for customer list queries.
     *
     * @param  array<string, mixed>       $filters
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function buildFilterClauses(array $filters): array
    {
        $clauses = ['deleted_at IS NULL'];
        $params  = [];

        if (!empty($filters['search'])) {
            $clauses[]        = '(name LIKE :search OR email LIKE :search OR phone LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        return [$clauses, $params];
    }
}
