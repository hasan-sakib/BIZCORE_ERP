<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * ProductRepository
 *
 * All SQL queries for the products table and related joins.
 * No business logic — pure data access only.
 */
final class ProductRepository extends BaseRepository
{
    /**
     * Return a paginated list of products with optional filters.
     *
     * Supported filters:
     *   - search      : partial match on name or SKU
     *   - category_id : exact match
     *   - status      : exact match (active|inactive|discontinued)
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, lastPage: int}
     */
    public function paginate(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = parent::limitOffset($page, $perPage);

        $where  = ['p.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]            = '(p.name LIKE :search OR p.sku LIKE :search)';
            $params[':search']  = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $where[]               = 'p.category_id = :category_id';
            $params[':category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $where[]          = 'p.status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSQL = implode(' AND ', $where);

        $total = $this->count(
            "SELECT COUNT(*) AS total
             FROM products p
             WHERE {$whereSQL}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT p.*,
                    c.name AS category_name,
                    b.name AS brand_name,
                    u.name AS unit_name,
                    u.abbreviation AS unit_symbol
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id AND c.deleted_at IS NULL
             LEFT  JOIN brands     b ON b.id = p.brand_id
             LEFT  JOIN units      u ON u.id = p.unit_id
             WHERE {$whereSQL}
             ORDER BY p.name ASC
             LIMIT {$limit} OFFSET {$offset}",
            $params,
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
     * Find a single product by primary key, including joined names.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT p.*,
                    c.name   AS category_name,
                    b.name   AS brand_name,
                    u.name   AS unit_name,
                    u.abbreviation AS unit_symbol
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id AND c.deleted_at IS NULL
             LEFT  JOIN brands     b ON b.id = p.brand_id
             LEFT  JOIN units      u ON u.id = p.unit_id
             WHERE p.id = :id AND p.deleted_at IS NULL
             LIMIT 1",
            [':id' => $id],
        );
    }

    /**
     * Insert a new product row and return the generated ID.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            "INSERT INTO products
                (sku, name, description, category_id, brand_id, unit_id,
                 type, cost_price, selling_price, tax_rate,
                 min_stock, max_stock, status, created_at, updated_at)
             VALUES
                (:sku, :name, :description, :category_id, :brand_id, :unit_id,
                 :type, :cost_price, :selling_price, :tax_rate,
                 :min_stock, :max_stock, :status, NOW(), NOW())",
            [
                ':sku'           => $data['sku'],
                ':name'          => $data['name'],
                ':description'   => $data['description']  ?? null,
                ':category_id'   => (int) $data['category_id'],
                ':brand_id'      => isset($data['brand_id']) && $data['brand_id'] !== ''
                                        ? (int) $data['brand_id'] : null,
                ':unit_id'       => isset($data['unit_id']) && $data['unit_id'] !== ''
                                        ? (int) $data['unit_id'] : null,
                ':type'          => $data['type']          ?? 'standard',
                ':cost_price'    => (float) ($data['cost_price']    ?? 0),
                ':selling_price' => (float) ($data['selling_price'] ?? 0),
                ':tax_rate'      => (float) ($data['tax_rate']      ?? 0),
                ':min_stock'     => (int)   ($data['min_stock']     ?? 0),
                ':max_stock'     => (int)   ($data['max_stock']     ?? 0),
                ':status'        => $data['status'] ?? 'active',
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing product row.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->execute(
            "UPDATE products
             SET sku           = :sku,
                 name          = :name,
                 description   = :description,
                 category_id   = :category_id,
                 brand_id      = :brand_id,
                 unit_id       = :unit_id,
                 type          = :type,
                 cost_price    = :cost_price,
                 selling_price = :selling_price,
                 tax_rate      = :tax_rate,
                 min_stock     = :min_stock,
                 max_stock     = :max_stock,
                 status        = :status,
                 updated_at    = NOW()
             WHERE id = :id AND deleted_at IS NULL",
            [
                ':sku'           => $data['sku'],
                ':name'          => $data['name'],
                ':description'   => $data['description']  ?? null,
                ':category_id'   => (int) $data['category_id'],
                ':brand_id'      => isset($data['brand_id']) && $data['brand_id'] !== ''
                                        ? (int) $data['brand_id'] : null,
                ':unit_id'       => isset($data['unit_id']) && $data['unit_id'] !== ''
                                        ? (int) $data['unit_id'] : null,
                ':type'          => $data['type']          ?? 'standard',
                ':cost_price'    => (float) ($data['cost_price']    ?? 0),
                ':selling_price' => (float) ($data['selling_price'] ?? 0),
                ':tax_rate'      => (float) ($data['tax_rate']      ?? 0),
                ':min_stock'     => (int)   ($data['min_stock']     ?? 0),
                ':max_stock'     => (int)   ($data['max_stock']     ?? 0),
                ':status'        => $data['status'] ?? 'active',
                ':id'            => $id,
            ],
        );
    }

    /**
     * Soft-delete a product by setting deleted_at.
     */
    public function softDelete(int $id): void
    {
        $this->execute(
            "UPDATE products
             SET deleted_at = NOW(), updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id],
        );
    }

    /**
     * Generate a unique SKU in the form PREFIX-NNNNN.
     *
     * Counts existing (including soft-deleted) products with the same prefix
     * and zero-pads the next sequence number to 5 digits.
     */
    public function generateSku(string $prefix = 'PRD'): string
    {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM products WHERE sku LIKE :pattern",
            [':pattern' => $prefix . '-%'],
        );

        $next = ((int) ($row['total'] ?? 0)) + 1;

        return $prefix . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Check whether a SKU is already taken by another product.
     *
     * Pass $excludeId to allow the current product to keep its own SKU on update.
     */
    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM products
                 WHERE sku = :sku AND id != :exclude AND deleted_at IS NULL",
                [':sku' => $sku, ':exclude' => $excludeId],
            );
        } else {
            $row = $this->fetchOne(
                "SELECT COUNT(*) AS total
                 FROM products
                 WHERE sku = :sku AND deleted_at IS NULL",
                [':sku' => $sku],
            );
        }

        return ((int) ($row['total'] ?? 0)) > 0;
    }
}
