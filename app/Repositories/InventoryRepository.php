<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * InventoryRepository
 *
 * All SQL for warehouses, stock-in, stock-out, transfers, and adjustments.
 * No business logic here — pure data access only.
 */
final class InventoryRepository extends BaseRepository
{
    // =========================================================================
    // Warehouses
    // =========================================================================

    /**
     * Return all warehouses, optionally filtered by a search term.
     *
     * @return list<array<string, mixed>>
     */
    public function allWarehouses(string $search = ''): array
    {
        $where  = ['w.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            $where[]          = '(w.name LIKE :search OR w.code LIKE :search OR w.location LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $whereSQL = implode(' AND ', $where);

        return $this->fetchAll(
            "SELECT w.*, u.name AS manager_name
             FROM warehouses w
             LEFT JOIN users u ON u.id = w.manager_id
             WHERE {$whereSQL}
             ORDER BY w.is_default DESC, w.name ASC",
            $params,
        );
    }

    /**
     * Find a single warehouse by ID (not soft-deleted).
     *
     * @return array<string, mixed>|null
     */
    public function findWarehouse(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT w.*, u.name AS manager_name
             FROM warehouses w
             LEFT JOIN users u ON u.id = w.manager_id
             WHERE w.id = :id AND w.deleted_at IS NULL",
            [':id' => $id],
        );
    }

    /**
     * Insert a new warehouse row.
     */
    public function createWarehouse(array $data): int
    {
        $this->modify(
            "INSERT INTO warehouses (name, code, location, manager_id, capacity, status, is_default, created_at, updated_at)
             VALUES (:name, :code, :location, :manager_id, :capacity, :status, :is_default, NOW(), NOW())",
            [
                ':name'       => $data['name'],
                ':code'       => $data['code'],
                ':location'   => $data['location'] ?? null,
                ':manager_id' => !empty($data['manager_id']) ? (int) $data['manager_id'] : null,
                ':capacity'   => !empty($data['capacity']) ? (float) $data['capacity'] : null,
                ':status'     => $data['status'] ?? 'active',
                ':is_default' => isset($data['is_default']) && $data['is_default'] ? 1 : 0,
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing warehouse row.
     */
    public function updateWarehouse(int $id, array $data): void
    {
        $this->modify(
            "UPDATE warehouses
             SET name = :name, code = :code, location = :location,
                 manager_id = :manager_id, capacity = :capacity,
                 status = :status, is_default = :is_default, updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL",
            [
                ':id'         => $id,
                ':name'       => $data['name'],
                ':code'       => $data['code'],
                ':location'   => $data['location'] ?? null,
                ':manager_id' => !empty($data['manager_id']) ? (int) $data['manager_id'] : null,
                ':capacity'   => !empty($data['capacity']) ? (float) $data['capacity'] : null,
                ':status'     => $data['status'] ?? 'active',
                ':is_default' => isset($data['is_default']) && $data['is_default'] ? 1 : 0,
            ],
        );
    }

    /**
     * Soft-delete a warehouse.
     */
    public function softDeleteWarehouse(int $id): void
    {
        $this->modify(
            "UPDATE warehouses SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id],
        );
    }

    /**
     * Return stock levels for all products in a warehouse.
     *
     * @return list<array<string, mixed>>
     */
    public function getWarehouseStock(int $warehouseId): array
    {
        return $this->fetchAll(
            "SELECT sl.*, p.name AS product_name, p.sku,
                    (sl.quantity - sl.reserved_quantity) AS available_quantity
             FROM stock_levels sl
             INNER JOIN products p ON p.id = sl.product_id AND p.deleted_at IS NULL
             WHERE sl.warehouse_id = :warehouse_id
             ORDER BY p.name ASC",
            [':warehouse_id' => $warehouseId],
        );
    }

    // =========================================================================
    // Stock In
    // =========================================================================

    /**
     * Paginate stock-in orders with optional filters.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, lastPage: int}
     */
    public function paginateStockIn(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = parent::limitOffset($page, $perPage);

        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $where[]               = 'si.warehouse_id = :warehouse_id';
            $params[':warehouse_id'] = (int) $filters['warehouse_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[]            = 'si.date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[]          = 'si.date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $whereSQL = implode(' AND ', $where);

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM stock_in_orders si WHERE {$whereSQL}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT si.*, w.name AS warehouse_name, s.name AS supplier_name
             FROM stock_in_orders si
             LEFT JOIN warehouses w ON w.id = si.warehouse_id
             LEFT JOIN suppliers  s ON s.id = si.supplier_id
             WHERE {$whereSQL}
             ORDER BY si.created_at DESC
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
     * Find a single stock-in order together with its line items.
     *
     * @return array<string, mixed>|null
     */
    public function findStockIn(int $id): ?array
    {
        $order = $this->fetchOne(
            "SELECT si.*, w.name AS warehouse_name, s.name AS supplier_name,
                    u.name AS created_by_name
             FROM stock_in_orders si
             LEFT JOIN warehouses w ON w.id = si.warehouse_id
             LEFT JOIN suppliers  s ON s.id = si.supplier_id
             LEFT JOIN users      u ON u.id = si.created_by
             WHERE si.id = :id",
            [':id' => $id],
        );

        if ($order === null) {
            return null;
        }

        $order['items'] = $this->fetchAll(
            "SELECT sii.*, p.name AS product_name, p.sku
             FROM stock_in_items sii
             INNER JOIN products p ON p.id = sii.product_id
             WHERE sii.stock_in_id = :id
             ORDER BY sii.id ASC",
            [':id' => $id],
        );

        return $order;
    }

    /**
     * Create a stock-in order with its items inside a transaction.
     * On success, updates or inserts stock_levels for each item.
     *
     * @param  array<string, mixed>         $data
     * @param  list<array<string, mixed>>   $items
     */
    public function createStockIn(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += (float) $item['quantity'] * (float) ($item['unit_cost'] ?? 0);
            }

            $this->modify(
                "INSERT INTO stock_in_orders
                    (reference_no, warehouse_id, supplier_id, date, notes, total_amount, status, created_by, created_at, updated_at)
                 VALUES
                    (:reference_no, :warehouse_id, :supplier_id, :date, :notes, :total_amount, 'confirmed', :created_by, NOW(), NOW())",
                [
                    ':reference_no'  => $data['reference_no'],
                    ':warehouse_id'  => (int) $data['warehouse_id'],
                    ':supplier_id'   => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                    ':date'          => $data['date'],
                    ':notes'         => $data['notes'] ?? null,
                    ':total_amount'  => round($totalAmount, 2),
                    ':created_by'    => (int) $data['created_by'],
                ],
            );

            $stockInId = $this->lastInsertId();

            foreach ($items as $item) {
                $qty      = (float) $item['quantity'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $total    = round($qty * $unitCost, 2);

                $this->modify(
                    "INSERT INTO stock_in_items (stock_in_id, product_id, quantity, unit_cost, total)
                     VALUES (:stock_in_id, :product_id, :quantity, :unit_cost, :total)",
                    [
                        ':stock_in_id' => $stockInId,
                        ':product_id'  => (int) $item['product_id'],
                        ':quantity'    => $qty,
                        ':unit_cost'   => $unitCost,
                        ':total'       => $total,
                    ],
                );

                // Upsert stock level
                $this->modify(
                    "INSERT INTO stock_levels (product_id, warehouse_id, quantity, reserved_quantity, last_updated)
                     VALUES (:product_id, :warehouse_id, :qty, 0, NOW())
                     ON DUPLICATE KEY UPDATE
                         quantity = quantity + :qty,
                         last_updated = NOW()",
                    [
                        ':product_id'  => (int) $item['product_id'],
                        ':warehouse_id' => (int) $data['warehouse_id'],
                        ':qty'         => $qty,
                    ],
                );
            }

            return $stockInId;
        });
    }

    /**
     * Generate the next reference number in the format SI-YYYYNNN.
     */
    public function generateStockInRef(): string
    {
        $year = date('Y');
        $row  = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM stock_in_orders WHERE YEAR(created_at) = :year",
            [':year' => $year],
        );
        $seq = (int) ($row['cnt'] ?? 0) + 1;

        return sprintf('SI-%s%03d', $year, $seq);
    }

    // =========================================================================
    // Stock Out
    // =========================================================================

    /**
     * Paginate stock-out orders with optional filters.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, lastPage: int}
     */
    public function paginateStockOut(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = parent::limitOffset($page, $perPage);

        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $where[]                = 'so.warehouse_id = :warehouse_id';
            $params[':warehouse_id'] = (int) $filters['warehouse_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[]            = 'so.date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[]          = 'so.date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $whereSQL = implode(' AND ', $where);

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM stock_out_orders so WHERE {$whereSQL}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT so.*, w.name AS warehouse_name
             FROM stock_out_orders so
             LEFT JOIN warehouses w ON w.id = so.warehouse_id
             WHERE {$whereSQL}
             ORDER BY so.created_at DESC
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
     * Find a single stock-out order together with its line items.
     *
     * @return array<string, mixed>|null
     */
    public function findStockOut(int $id): ?array
    {
        $order = $this->fetchOne(
            "SELECT so.*, w.name AS warehouse_name, u.name AS created_by_name
             FROM stock_out_orders so
             LEFT JOIN warehouses w ON w.id = so.warehouse_id
             LEFT JOIN users      u ON u.id = so.created_by
             WHERE so.id = :id",
            [':id' => $id],
        );

        if ($order === null) {
            return null;
        }

        $order['items'] = $this->fetchAll(
            "SELECT soi.*, p.name AS product_name, p.sku
             FROM stock_out_items soi
             INNER JOIN products p ON p.id = soi.product_id
             WHERE soi.stock_out_id = :id
             ORDER BY soi.id ASC",
            [':id' => $id],
        );

        return $order;
    }

    /**
     * Create a stock-out order with its items inside a transaction.
     *
     * @param  array<string, mixed>         $data
     * @param  list<array<string, mixed>>   $items
     */
    public function createStockOut(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += (float) $item['quantity'] * (float) ($item['unit_cost'] ?? 0);
            }

            $this->modify(
                "INSERT INTO stock_out_orders
                    (reference_no, warehouse_id, reason, date, notes, total_amount, status, created_by, created_at, updated_at)
                 VALUES
                    (:reference_no, :warehouse_id, :reason, :date, :notes, :total_amount, 'confirmed', :created_by, NOW(), NOW())",
                [
                    ':reference_no' => $data['reference_no'],
                    ':warehouse_id' => (int) $data['warehouse_id'],
                    ':reason'       => $data['reason'] ?? '',
                    ':date'         => $data['date'],
                    ':notes'        => $data['notes'] ?? null,
                    ':total_amount' => round($totalAmount, 2),
                    ':created_by'   => (int) $data['created_by'],
                ],
            );

            $stockOutId = $this->lastInsertId();

            foreach ($items as $item) {
                $qty      = (float) $item['quantity'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);

                $this->modify(
                    "INSERT INTO stock_out_items (stock_out_id, product_id, quantity, unit_cost)
                     VALUES (:stock_out_id, :product_id, :quantity, :unit_cost)",
                    [
                        ':stock_out_id' => $stockOutId,
                        ':product_id'   => (int) $item['product_id'],
                        ':quantity'     => $qty,
                        ':unit_cost'    => $unitCost,
                    ],
                );

                // Decrement stock level
                $this->modify(
                    "UPDATE stock_levels
                     SET quantity = GREATEST(0, quantity - :qty), last_updated = NOW()
                     WHERE product_id = :product_id AND warehouse_id = :warehouse_id",
                    [
                        ':qty'          => $qty,
                        ':product_id'   => (int) $item['product_id'],
                        ':warehouse_id' => (int) $data['warehouse_id'],
                    ],
                );
            }

            return $stockOutId;
        });
    }

    /**
     * Generate the next stock-out reference number (SO-YYYYNNN).
     */
    public function generateStockOutRef(): string
    {
        $year = date('Y');
        $row  = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM stock_out_orders WHERE YEAR(created_at) = :year",
            [':year' => $year],
        );
        $seq = (int) ($row['cnt'] ?? 0) + 1;

        return sprintf('SO-%s%03d', $year, $seq);
    }

    // =========================================================================
    // Transfers
    // =========================================================================

    /**
     * Paginate stock transfers with optional filters.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, lastPage: int}
     */
    public function paginateTransfers(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = parent::limitOffset($page, $perPage);

        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]          = 'st.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['warehouse_id'])) {
            $where[]                = '(st.from_warehouse_id = :warehouse_id OR st.to_warehouse_id = :warehouse_id)';
            $params[':warehouse_id'] = (int) $filters['warehouse_id'];
        }

        $whereSQL = implode(' AND ', $where);

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM stock_transfers st WHERE {$whereSQL}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT st.*, fw.name AS from_warehouse_name, tw.name AS to_warehouse_name
             FROM stock_transfers st
             LEFT JOIN warehouses fw ON fw.id = st.from_warehouse_id
             LEFT JOIN warehouses tw ON tw.id = st.to_warehouse_id
             WHERE {$whereSQL}
             ORDER BY st.created_at DESC
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
     * Find a single transfer together with its items.
     *
     * @return array<string, mixed>|null
     */
    public function findTransfer(int $id): ?array
    {
        $transfer = $this->fetchOne(
            "SELECT st.*, fw.name AS from_warehouse_name, tw.name AS to_warehouse_name,
                    u.name AS created_by_name
             FROM stock_transfers st
             LEFT JOIN warehouses fw ON fw.id = st.from_warehouse_id
             LEFT JOIN warehouses tw ON tw.id = st.to_warehouse_id
             LEFT JOIN users      u  ON u.id  = st.created_by
             WHERE st.id = :id",
            [':id' => $id],
        );

        if ($transfer === null) {
            return null;
        }

        $transfer['items'] = $this->fetchAll(
            "SELECT sti.*, p.name AS product_name, p.sku
             FROM stock_transfer_items sti
             INNER JOIN products p ON p.id = sti.product_id
             WHERE sti.transfer_id = :id
             ORDER BY sti.id ASC",
            [':id' => $id],
        );

        return $transfer;
    }

    /**
     * Create a stock transfer with items inside a transaction.
     *
     * @param  array<string, mixed>         $data
     * @param  list<array<string, mixed>>   $items
     */
    public function createTransfer(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $this->modify(
                "INSERT INTO stock_transfers
                    (reference_no, from_warehouse_id, to_warehouse_id, date, notes, status, created_by, created_at, updated_at)
                 VALUES
                    (:reference_no, :from_warehouse_id, :to_warehouse_id, :date, :notes, 'draft', :created_by, NOW(), NOW())",
                [
                    ':reference_no'      => $data['reference_no'],
                    ':from_warehouse_id' => (int) $data['from_warehouse_id'],
                    ':to_warehouse_id'   => (int) $data['to_warehouse_id'],
                    ':date'              => $data['date'],
                    ':notes'             => $data['notes'] ?? null,
                    ':created_by'        => (int) $data['created_by'],
                ],
            );

            $transferId = $this->lastInsertId();

            foreach ($items as $item) {
                $this->modify(
                    "INSERT INTO stock_transfer_items (transfer_id, product_id, quantity)
                     VALUES (:transfer_id, :product_id, :quantity)",
                    [
                        ':transfer_id' => $transferId,
                        ':product_id'  => (int) $item['product_id'],
                        ':quantity'    => (float) $item['quantity'],
                    ],
                );
            }

            return $transferId;
        });
    }

    /**
     * Update the status of a transfer. When status becomes 'received',
     * deduct from source warehouse and add to destination warehouse.
     */
    public function updateTransferStatus(int $id, string $status): void
    {
        $this->transaction(function () use ($id, $status): void {
            $transfer = $this->fetchOne(
                "SELECT * FROM stock_transfers WHERE id = :id",
                [':id' => $id],
            );

            if ($transfer === null) {
                return;
            }

            $this->modify(
                "UPDATE stock_transfers SET status = :status, updated_at = NOW() WHERE id = :id",
                [':status' => $status, ':id' => $id],
            );

            if ($status === 'received') {
                $items = $this->fetchAll(
                    "SELECT * FROM stock_transfer_items WHERE transfer_id = :id",
                    [':id' => $id],
                );

                foreach ($items as $item) {
                    // Deduct from source
                    $this->modify(
                        "UPDATE stock_levels
                         SET quantity = GREATEST(0, quantity - :qty), last_updated = NOW()
                         WHERE product_id = :product_id AND warehouse_id = :warehouse_id",
                        [
                            ':qty'          => (float) $item['quantity'],
                            ':product_id'   => (int) $item['product_id'],
                            ':warehouse_id' => (int) $transfer['from_warehouse_id'],
                        ],
                    );

                    // Add to destination
                    $this->modify(
                        "INSERT INTO stock_levels (product_id, warehouse_id, quantity, reserved_quantity, last_updated)
                         VALUES (:product_id, :warehouse_id, :qty, 0, NOW())
                         ON DUPLICATE KEY UPDATE
                             quantity = quantity + :qty,
                             last_updated = NOW()",
                        [
                            ':product_id'   => (int) $item['product_id'],
                            ':warehouse_id' => (int) $transfer['to_warehouse_id'],
                            ':qty'          => (float) $item['quantity'],
                        ],
                    );
                }
            }
        });
    }

    /**
     * Generate the next transfer reference number (TR-YYYYNNN).
     */
    public function generateTransferRef(): string
    {
        $year = date('Y');
        $row  = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM stock_transfers WHERE YEAR(created_at) = :year",
            [':year' => $year],
        );
        $seq = (int) ($row['cnt'] ?? 0) + 1;

        return sprintf('TR-%s%03d', $year, $seq);
    }

    // =========================================================================
    // Adjustments
    // =========================================================================

    /**
     * Paginate stock adjustments with optional filters.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, lastPage: int}
     */
    public function paginateAdjustments(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = parent::limitOffset($page, $perPage);

        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $where[]                = 'sa.warehouse_id = :warehouse_id';
            $params[':warehouse_id'] = (int) $filters['warehouse_id'];
        }

        if (!empty($filters['status'])) {
            $where[]          = 'sa.status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSQL = implode(' AND ', $where);

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM stock_adjustments sa WHERE {$whereSQL}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT sa.*, w.name AS warehouse_name
             FROM stock_adjustments sa
             LEFT JOIN warehouses w ON w.id = sa.warehouse_id
             WHERE {$whereSQL}
             ORDER BY sa.created_at DESC
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
     * Find a single adjustment with its items.
     *
     * @return array<string, mixed>|null
     */
    public function findAdjustment(int $id): ?array
    {
        $adj = $this->fetchOne(
            "SELECT sa.*, w.name AS warehouse_name,
                    u.name AS created_by_name, a.name AS approved_by_name
             FROM stock_adjustments sa
             LEFT JOIN warehouses w ON w.id  = sa.warehouse_id
             LEFT JOIN users      u ON u.id  = sa.created_by
             LEFT JOIN users      a ON a.id  = sa.approved_by
             WHERE sa.id = :id",
            [':id' => $id],
        );

        if ($adj === null) {
            return null;
        }

        $adj['items'] = $this->fetchAll(
            "SELECT sai.*, p.name AS product_name, p.sku
             FROM stock_adjustment_items sai
             INNER JOIN products p ON p.id = sai.product_id
             WHERE sai.adjustment_id = :id
             ORDER BY sai.id ASC",
            [':id' => $id],
        );

        return $adj;
    }

    /**
     * Create a stock adjustment with its items inside a transaction.
     *
     * @param  array<string, mixed>         $data
     * @param  list<array<string, mixed>>   $items
     */
    public function createAdjustment(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $this->modify(
                "INSERT INTO stock_adjustments
                    (reference_no, warehouse_id, reason, date, notes, status, created_by, created_at, updated_at)
                 VALUES
                    (:reference_no, :warehouse_id, :reason, :date, :notes, 'pending', :created_by, NOW(), NOW())",
                [
                    ':reference_no' => $data['reference_no'],
                    ':warehouse_id' => (int) $data['warehouse_id'],
                    ':reason'       => $data['reason'] ?? '',
                    ':date'         => $data['date'],
                    ':notes'        => $data['notes'] ?? null,
                    ':created_by'   => (int) $data['created_by'],
                ],
            );

            $adjId = $this->lastInsertId();

            foreach ($items as $item) {
                $this->modify(
                    "INSERT INTO stock_adjustment_items (adjustment_id, product_id, type, quantity, reason)
                     VALUES (:adjustment_id, :product_id, :type, :quantity, :reason)",
                    [
                        ':adjustment_id' => $adjId,
                        ':product_id'    => (int) $item['product_id'],
                        ':type'          => $item['type'],  // 'add' | 'remove'
                        ':quantity'      => (float) $item['quantity'],
                        ':reason'        => $item['reason'] ?? null,
                    ],
                );
            }

            return $adjId;
        });
    }

    /**
     * Approve an adjustment: update status and apply quantities to stock_levels.
     */
    public function approveAdjustment(int $id, int $approvedBy): void
    {
        $this->transaction(function () use ($id, $approvedBy): void {
            $adj = $this->fetchOne(
                "SELECT * FROM stock_adjustments WHERE id = :id AND status = 'pending'",
                [':id' => $id],
            );

            if ($adj === null) {
                return;
            }

            $this->modify(
                "UPDATE stock_adjustments
                 SET status = 'approved', approved_by = :approved_by, updated_at = NOW()
                 WHERE id = :id",
                [':approved_by' => $approvedBy, ':id' => $id],
            );

            $items = $this->fetchAll(
                "SELECT * FROM stock_adjustment_items WHERE adjustment_id = :id",
                [':id' => $id],
            );

            foreach ($items as $item) {
                $qty = (float) $item['quantity'];

                if ($item['type'] === 'add') {
                    $this->modify(
                        "INSERT INTO stock_levels (product_id, warehouse_id, quantity, reserved_quantity, last_updated)
                         VALUES (:product_id, :warehouse_id, :qty, 0, NOW())
                         ON DUPLICATE KEY UPDATE
                             quantity = quantity + :qty,
                             last_updated = NOW()",
                        [
                            ':product_id'   => (int) $item['product_id'],
                            ':warehouse_id' => (int) $adj['warehouse_id'],
                            ':qty'          => $qty,
                        ],
                    );
                } else {
                    $this->modify(
                        "UPDATE stock_levels
                         SET quantity = GREATEST(0, quantity - :qty), last_updated = NOW()
                         WHERE product_id = :product_id AND warehouse_id = :warehouse_id",
                        [
                            ':qty'          => $qty,
                            ':product_id'   => (int) $item['product_id'],
                            ':warehouse_id' => (int) $adj['warehouse_id'],
                        ],
                    );
                }
            }
        });
    }

    /**
     * Generate the next adjustment reference number (ADJ-YYYYNNN).
     */
    public function generateAdjustmentRef(): string
    {
        $year = date('Y');
        $row  = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM stock_adjustments WHERE YEAR(created_at) = :year",
            [':year' => $year],
        );
        $seq = (int) ($row['cnt'] ?? 0) + 1;

        return sprintf('ADJ-%s%03d', $year, $seq);
    }
}
