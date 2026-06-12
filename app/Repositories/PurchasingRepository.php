<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * PurchasingRepository
 *
 * All SQL for purchase orders and goods receipt notes (GRN).
 * Transactions are used for multi-table writes.
 */
final class PurchasingRepository extends BaseRepository
{
    // =========================================================================
    // Purchase Orders — read
    // =========================================================================

    /**
     * Return a paginated, filtered list of purchase orders joined with the
     * supplier name.
     *
     * Supported filter keys: supplier_id, status, date_from, date_to, search
     *
     * @param  array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, lastPage: int, perPage: int}
     */
    public function paginateOrders(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        [$clauses, $params] = $this->buildOrderFilters($filters);
        $where = 'WHERE ' . implode(' AND ', $clauses);

        $total = $this->count(
            "SELECT COUNT(*) AS total
             FROM purchase_orders po
             JOIN suppliers s ON s.id = po.supplier_id
             {$where}",
            $params,
        );

        $rows = $this->fetchAll(
            "SELECT po.*,
                    s.name AS supplier_name
             FROM purchase_orders po
             JOIN suppliers s ON s.id = po.supplier_id
             {$where}
             ORDER BY po.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return [
            'items'    => $rows,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'perPage'  => $perPage,
        ];
    }

    /**
     * Return a single purchase order with its line items and supplier details.
     *
     * @return array<string, mixed>|null
     */
    public function findOrder(int $id): ?array
    {
        $order = $this->fetchOne(
            "SELECT po.*,
                    s.name  AS supplier_name,
                    s.email AS supplier_email,
                    s.phone AS supplier_phone
             FROM purchase_orders po
             JOIN suppliers s ON s.id = po.supplier_id
             WHERE po.id = :id AND po.deleted_at IS NULL
             LIMIT 1",
            [':id' => $id],
        );

        if ($order === null) {
            return null;
        }

        $order['items'] = $this->fetchAll(
            "SELECT poi.*,
                    p.name AS product_name,
                    p.sku  AS product_sku
             FROM purchase_order_items poi
             JOIN products p ON p.id = poi.product_id
             WHERE poi.po_id = :po_id
             ORDER BY poi.id ASC",
            [':po_id' => $id],
        );

        return $order;
    }

    // =========================================================================
    // Purchase Orders — write
    // =========================================================================

    /**
     * Insert a new purchase order together with its line items inside a single
     * transaction.  Returns the new order's primary key.
     *
     * @param  array<string, mixed>              $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createOrder(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $this->modify(
                <<<SQL
                INSERT INTO purchase_orders
                    (po_number, supplier_id, order_date, expected_date, notes,
                     discount_amount, vat_amount, subtotal, total_amount,
                     status, created_by, branch_id, created_at, updated_at)
                VALUES
                    (:po_number, :supplier_id, :order_date, :expected_date, :notes,
                     :discount_amount, :vat_amount, :subtotal, :total_amount,
                     :status, :created_by, :branch_id, NOW(), NOW())
                SQL,
                [
                    ':po_number'       => $data['po_number'],
                    ':supplier_id'     => (int) $data['supplier_id'],
                    ':order_date'      => $data['order_date'],
                    ':expected_date'   => $data['expected_date'] ?: null,
                    ':notes'           => $data['notes'] ?? null,
                    ':discount_amount' => (float) ($data['discount_amount'] ?? 0),
                    ':vat_amount'      => (float) ($data['vat_amount'] ?? 0),
                    ':subtotal'        => (float) ($data['subtotal'] ?? 0),
                    ':total_amount'    => (float) ($data['total_amount'] ?? 0),
                    ':status'          => $data['status'] ?? 'draft',
                    ':created_by'      => (int) $data['created_by'],
                    ':branch_id'       => (int) ($data['branch_id'] ?? 1),
                ],
            );

            $poId = $this->lastInsertId();

            foreach ($items as $item) {
                $this->insertOrderItem($poId, $item);
            }

            return $poId;
        });
    }

    /**
     * Update an existing draft/sent purchase order and replace its line items.
     *
     * @param  array<string, mixed>              $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function updateOrder(int $id, array $data, array $items): void
    {
        $this->transaction(function () use ($id, $data, $items): void {
            $this->modify(
                <<<SQL
                UPDATE purchase_orders
                SET supplier_id     = :supplier_id,
                    order_date      = :order_date,
                    expected_date   = :expected_date,
                    notes           = :notes,
                    discount_amount = :discount_amount,
                    vat_amount      = :vat_amount,
                    subtotal        = :subtotal,
                    total_amount    = :total_amount,
                    updated_at      = NOW()
                WHERE id = :id AND deleted_at IS NULL
                SQL,
                [
                    ':supplier_id'     => (int) $data['supplier_id'],
                    ':order_date'      => $data['order_date'],
                    ':expected_date'   => $data['expected_date'] ?: null,
                    ':notes'           => $data['notes'] ?? null,
                    ':discount_amount' => (float) ($data['discount_amount'] ?? 0),
                    ':vat_amount'      => (float) ($data['vat_amount'] ?? 0),
                    ':subtotal'        => (float) ($data['subtotal'] ?? 0),
                    ':total_amount'    => (float) ($data['total_amount'] ?? 0),
                    ':id'              => $id,
                ],
            );

            // Replace line items.
            $this->modify(
                'DELETE FROM purchase_order_items WHERE po_id = :po_id',
                [':po_id' => $id],
            );

            foreach ($items as $item) {
                $this->insertOrderItem($id, $item);
            }
        });
    }

    /**
     * Flip the status column on a purchase order.
     */
    public function updateOrderStatus(int $id, string $status): void
    {
        $this->modify(
            'UPDATE purchase_orders SET status = :status, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':status' => $status, ':id' => $id],
        );
    }

    /**
     * Soft-delete a purchase order.
     */
    public function softDeleteOrder(int $id): void
    {
        $this->modify(
            'UPDATE purchase_orders SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id],
        );
    }

    /**
     * Generate the next sequential purchase order reference number.
     * Format: PO-YYYYNNNN  e.g. PO-20240001
     */
    public function generateOrderRef(): string
    {
        $year = date('Y');

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM purchase_orders
             WHERE po_number LIKE :prefix",
            [':prefix' => "PO-{$year}%"],
        );

        $seq = (int) ($row['total'] ?? 0) + 1;

        return sprintf('PO-%s%04d', $year, $seq);
    }

    // =========================================================================
    // Goods Receipts — read
    // =========================================================================

    /**
     * Return a paginated, filtered list of GRNs.
     *
     * Supported filter keys: supplier_id, status, date_from, date_to, search
     *
     * @param  array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, lastPage: int, perPage: int}
     */
    public function paginateGRN(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        [$clauses, $params] = $this->buildGRNFilters($filters);
        $where = 'WHERE ' . implode(' AND ', $clauses);

        $total = $this->count(
            "SELECT COUNT(*) AS total
             FROM goods_receipts gr
             JOIN suppliers  s ON s.id = gr.supplier_id
             JOIN warehouses w ON w.id = gr.warehouse_id
             {$where}",
            $params,
        );

        $rows = $this->fetchAll(
            "SELECT gr.*,
                    s.name AS supplier_name,
                    w.name AS warehouse_name
             FROM goods_receipts gr
             JOIN suppliers  s ON s.id = gr.supplier_id
             JOIN warehouses w ON w.id = gr.warehouse_id
             {$where}
             ORDER BY gr.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return [
            'items'    => $rows,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'perPage'  => $perPage,
        ];
    }

    /**
     * Return a single GRN with its line items.
     *
     * @return array<string, mixed>|null
     */
    public function findGRN(int $id): ?array
    {
        $grn = $this->fetchOne(
            "SELECT gr.*,
                    s.name AS supplier_name,
                    w.name AS warehouse_name
             FROM goods_receipts gr
             JOIN suppliers  s ON s.id = gr.supplier_id
             JOIN warehouses w ON w.id = gr.warehouse_id
             WHERE gr.id = :id
             LIMIT 1",
            [':id' => $id],
        );

        if ($grn === null) {
            return null;
        }

        $grn['items'] = $this->fetchAll(
            "SELECT gri.*,
                    p.name AS product_name,
                    p.sku  AS product_sku
             FROM goods_receipt_items gri
             JOIN products p ON p.id = gri.product_id
             WHERE gri.grn_id = :grn_id
             ORDER BY gri.id ASC",
            [':grn_id' => $id],
        );

        return $grn;
    }

    // =========================================================================
    // Goods Receipts — write
    // =========================================================================

    /**
     * Create a GRN and update stock_levels for each item inside one transaction.
     *
     * @param  array<string, mixed>              $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createGRN(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $this->modify(
                <<<SQL
                INSERT INTO goods_receipts
                    (grn_number, po_id, supplier_id, warehouse_id, receipt_date,
                     notes, subtotal, vat_amount, total_amount,
                     status, created_by, branch_id, created_at, updated_at)
                VALUES
                    (:grn_number, :po_id, :supplier_id, :warehouse_id, :receipt_date,
                     :notes, :subtotal, :vat_amount, :total_amount,
                     :status, :created_by, :branch_id, NOW(), NOW())
                SQL,
                [
                    ':grn_number'   => $data['grn_number'],
                    ':po_id'        => !empty($data['po_id']) ? (int) $data['po_id'] : null,
                    ':supplier_id'  => (int) $data['supplier_id'],
                    ':warehouse_id' => (int) $data['warehouse_id'],
                    ':receipt_date' => $data['receipt_date'],
                    ':notes'        => $data['notes'] ?? null,
                    ':subtotal'     => (float) ($data['subtotal'] ?? 0),
                    ':vat_amount'   => (float) ($data['vat_amount'] ?? 0),
                    ':total_amount' => (float) ($data['total_amount'] ?? 0),
                    ':status'       => $data['status'] ?? 'draft',
                    ':created_by'   => (int) $data['created_by'],
                    ':branch_id'    => (int) ($data['branch_id'] ?? 1),
                ],
            );

            $grnId = $this->lastInsertId();

            foreach ($items as $item) {
                $qty      = (float) ($item['quantity'] ?? 0);
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $total    = round($qty * $unitCost, 2);

                $this->modify(
                    <<<SQL
                    INSERT INTO goods_receipt_items
                        (grn_id, po_item_id, product_id, quantity, unit_cost, total, created_at)
                    VALUES
                        (:grn_id, :po_item_id, :product_id, :quantity, :unit_cost, :total, NOW())
                    SQL,
                    [
                        ':grn_id'     => $grnId,
                        ':po_item_id' => !empty($item['po_item_id']) ? (int) $item['po_item_id'] : null,
                        ':product_id' => (int) $item['product_id'],
                        ':quantity'   => $qty,
                        ':unit_cost'  => $unitCost,
                        ':total'      => $total,
                    ],
                );

                // Upsert stock_levels for the received warehouse.
                $this->modify(
                    <<<SQL
                    INSERT INTO stock_levels (product_id, warehouse_id, quantity, updated_at)
                    VALUES (:product_id, :warehouse_id, :qty, NOW())
                    ON DUPLICATE KEY UPDATE
                        quantity   = quantity + VALUES(quantity),
                        updated_at = NOW()
                    SQL,
                    [
                        ':product_id'   => (int) $item['product_id'],
                        ':warehouse_id' => (int) $data['warehouse_id'],
                        ':qty'          => $qty,
                    ],
                );

                // Update received_qty on the PO item if linked.
                if (!empty($item['po_item_id'])) {
                    $this->modify(
                        'UPDATE purchase_order_items SET received_qty = received_qty + :qty WHERE id = :id',
                        [':qty' => $qty, ':id' => (int) $item['po_item_id']],
                    );
                }
            }

            return $grnId;
        });
    }

    /**
     * Generate the next sequential GRN reference number.
     * Format: GRN-YYYYNNNN  e.g. GRN-20240001
     */
    public function generateGRNRef(): string
    {
        $year = date('Y');

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM goods_receipts
             WHERE grn_number LIKE :prefix",
            [':prefix' => "GRN-{$year}%"],
        );

        $seq = (int) ($row['total'] ?? 0) + 1;

        return sprintf('GRN-%s%04d', $year, $seq);
    }

    // =========================================================================
    // Supplier helpers (used by create/edit forms)
    // =========================================================================

    /**
     * Return id + name for all active suppliers (for select dropdowns).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allActiveSuppliers(): array
    {
        return $this->fetchAll(
            "SELECT id, name FROM suppliers WHERE deleted_at IS NULL AND status = 'active' ORDER BY name ASC",
        );
    }

    /**
     * Return id + name for all warehouses (for select dropdowns).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allWarehouses(): array
    {
        return $this->fetchAll(
            "SELECT id, name FROM warehouses WHERE deleted_at IS NULL ORDER BY name ASC",
        );
    }

    /**
     * Return id, name, sku for all active products (for line-item dropdowns).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allActiveProducts(): array
    {
        return $this->fetchAll(
            "SELECT id, name, sku, purchase_price AS default_cost
             FROM products
             WHERE deleted_at IS NULL AND status = 'active'
             ORDER BY name ASC",
        );
    }

    /**
     * Return paginated approved POs (for GRN po_id dropdown).
     *
     * @return array<int, array<string, mixed>>
     */
    public function approvedOrders(): array
    {
        return $this->fetchAll(
            "SELECT po.id, po.po_number, s.name AS supplier_name
             FROM purchase_orders po
             JOIN suppliers s ON s.id = po.supplier_id
             WHERE po.status IN ('approved','partial') AND po.deleted_at IS NULL
             ORDER BY po.created_at DESC",
        );
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Insert a single PO line item.
     *
     * @param array<string, mixed> $item
     */
    private function insertOrderItem(int $poId, array $item): void
    {
        $qty   = (float) ($item['quantity'] ?? 0);
        $price = (float) ($item['unit_price'] ?? 0);
        $total = round($qty * $price, 2);

        $this->modify(
            <<<SQL
            INSERT INTO purchase_order_items
                (po_id, product_id, quantity, unit_price, vat_rate, vat_amount, discount, total)
            VALUES
                (:po_id, :product_id, :quantity, :unit_price, :vat_rate, :vat_amount, :discount, :total)
            SQL,
            [
                ':po_id'      => $poId,
                ':product_id' => (int) $item['product_id'],
                ':quantity'   => $qty,
                ':unit_price' => $price,
                ':vat_rate'   => (float) ($item['vat_rate']   ?? 0),
                ':vat_amount' => (float) ($item['vat_amount'] ?? 0),
                ':discount'   => (float) ($item['discount']   ?? 0),
                ':total'      => $total,
            ],
        );
    }

    /**
     * Build WHERE clauses + bindings for purchase order list queries.
     *
     * @param  array<string, mixed>                     $filters
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function buildOrderFilters(array $filters): array
    {
        $clauses = ['po.deleted_at IS NULL'];
        $params  = [];

        if (!empty($filters['supplier_id'])) {
            $clauses[]             = 'po.supplier_id = :supplier_id';
            $params[':supplier_id'] = (int) $filters['supplier_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'po.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $clauses[]           = 'po.order_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[]         = 'po.order_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $clauses[]        = 'po.po_number LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [$clauses, $params];
    }

    /**
     * Build WHERE clauses + bindings for GRN list queries.
     *
     * @param  array<string, mixed>                     $filters
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function buildGRNFilters(array $filters): array
    {
        $clauses = ['1=1'];
        $params  = [];

        if (!empty($filters['supplier_id'])) {
            $clauses[]             = 'gr.supplier_id = :supplier_id';
            $params[':supplier_id'] = (int) $filters['supplier_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'gr.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $clauses[]           = 'gr.receipt_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[]         = 'gr.receipt_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $clauses[]        = 'gr.grn_number LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [$clauses, $params];
    }
}
