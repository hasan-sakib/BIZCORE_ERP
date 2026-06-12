<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use App\Core\Database;
use App\Http\Request;
use App\Services\InventoryService;

class InventoryApiController extends BaseApiController
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly Database $db
    ) {}

    public function index(Request $request): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));
        $branchId = $this->getBranchId($request);
        $warehouseId = (int)($request->query('warehouse_id') ?? 0);

        $where    = ['p.is_active = 1', 'p.deleted_at IS NULL'];
        $bindings = [];

        if ($search = $request->query('search')) {
            $where[]    = "(p.name LIKE ? OR p.sku LIKE ?)";
            $bindings[] = '%' . $search . '%';
            $bindings[] = '%' . $search . '%';
        }
        if ($lowStock = $request->query('low_stock')) {
            $where[] = 'COALESCE(SUM(i.quantity), 0) <= p.reorder_point';
        }

        $warehouseJoinCond = $warehouseId
            ? "ON i.product_id = p.id AND i.warehouse_id = {$warehouseId}"
            : "ON i.product_id = p.id JOIN warehouses w ON w.id = i.warehouse_id AND w.branch_id = {$branchId}";

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->fetchAll(
            "SELECT p.id, p.name, p.sku, p.barcode, p.reorder_point,
                    c.name AS category_name,
                    COALESCE(SUM(i.quantity), 0) AS total_qty,
                    COALESCE(SUM(i.reserved_quantity), 0) AS reserved_qty,
                    COALESCE(AVG(i.avg_cost), 0) AS avg_cost,
                    COALESCE(SUM(i.quantity * i.avg_cost), 0) AS stock_value
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN inventory i {$warehouseJoinCond}
             {$whereClause}
             GROUP BY p.id, p.name, p.sku, p.barcode, p.reorder_point, c.name
             ORDER BY p.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $total = count($rows);

        $this->paginated([
            'data'       => array_map(fn($r) => [
                ...$r,
                'available_qty' => (float)$r['total_qty'] - (float)$r['reserved_qty'],
                'is_low_stock'  => (float)$r['total_qty'] <= (float)$r['reorder_point'],
            ], $rows),
            'pagination' => paginate($total, $page, $perPage),
        ]);
    }

    public function stockIn(Request $request): void
    {
        $data = $request->all();

        $required = ['product_id', 'warehouse_id', 'quantity', 'unit_cost'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        try {
            $movement = $this->inventoryService->stockIn(
                productId:   (int)$data['product_id'],
                variantId:   (int)($data['variant_id'] ?? 0) ?: null,
                warehouseId: (int)$data['warehouse_id'],
                quantity:    (float)$data['quantity'],
                unitCost:    (float)$data['unit_cost'],
                reference:   $data['reference'] ?? null,
                notes:       $data['notes'] ?? null,
                createdBy:   $this->currentUser($request)?->id ?? 0
            );
            $this->success($movement, 'Stock in recorded.', 201);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    public function stockOut(Request $request): void
    {
        $data = $request->all();

        $required = ['product_id', 'warehouse_id', 'quantity'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        try {
            $movement = $this->inventoryService->stockOut(
                productId:   (int)$data['product_id'],
                variantId:   (int)($data['variant_id'] ?? 0) ?: null,
                warehouseId: (int)$data['warehouse_id'],
                quantity:    (float)$data['quantity'],
                reference:   $data['reference'] ?? null,
                notes:       $data['notes'] ?? null,
                createdBy:   $this->currentUser($request)?->id ?? 0
            );
            $this->success($movement, 'Stock out recorded.', 201);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    public function adjust(Request $request): void
    {
        $data = $request->all();

        $required = ['product_id', 'warehouse_id', 'new_quantity'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        try {
            $movement = $this->inventoryService->adjustStock(
                productId:   (int)$data['product_id'],
                variantId:   (int)($data['variant_id'] ?? 0) ?: null,
                warehouseId: (int)$data['warehouse_id'],
                newQuantity: (float)$data['new_quantity'],
                reason:      $data['reason'] ?? 'Manual adjustment',
                createdBy:   $this->currentUser($request)?->id ?? 0
            );
            $this->success($movement, 'Stock adjusted.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    public function transfer(Request $request): void
    {
        $data = $request->all();

        $required = ['product_id', 'from_warehouse_id', 'to_warehouse_id', 'quantity'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        if ((int)$data['from_warehouse_id'] === (int)$data['to_warehouse_id']) {
            $this->error('Source and destination warehouses must be different.', 422);
        }

        try {
            $result = $this->inventoryService->transferStock(
                productId:       (int)$data['product_id'],
                fromWarehouseId: (int)$data['from_warehouse_id'],
                toWarehouseId:   (int)$data['to_warehouse_id'],
                quantity:        (float)$data['quantity'],
                notes:           $data['notes'] ?? null,
                createdBy:       $this->currentUser($request)?->id ?? 0
            );
            $this->success($result, 'Stock transferred.', 201);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    public function movements(Request $request): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));

        $where    = ['sm.deleted_at IS NULL'];
        $bindings = [];

        if ($productId = $request->query('product_id')) {
            $where[]    = 'sm.product_id = ?';
            $bindings[] = (int)$productId;
        }
        if ($warehouseId = $request->query('warehouse_id')) {
            $where[]    = 'sm.warehouse_id = ?';
            $bindings[] = (int)$warehouseId;
        }
        if ($type = $request->query('type')) {
            $where[]    = 'sm.type = ?';
            $bindings[] = $type;
        }
        if ($from = $request->query('from')) {
            $where[]    = 'sm.created_at >= ?';
            $bindings[] = $from . ' 00:00:00';
        }
        if ($to = $request->query('to')) {
            $where[]    = 'sm.created_at <= ?';
            $bindings[] = $to . ' 23:59:59';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM stock_movements sm {$whereClause}",
            $bindings
        );

        $rows = $this->db->fetchAll(
            "SELECT sm.*, p.name AS product_name, p.sku,
                    w.name AS warehouse_name
             FROM stock_movements sm
             JOIN products p ON p.id = sm.product_id
             JOIN warehouses w ON w.id = sm.warehouse_id
             {$whereClause}
             ORDER BY sm.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $this->paginated([
            'data'       => $rows,
            'pagination' => paginate($total, $page, $perPage),
        ]);
    }

    public function warehouses(Request $request): void
    {
        $branchId = $this->getBranchId($request);
        $rows = $this->db->fetchAll(
            "SELECT w.*, COUNT(DISTINCT i.product_id) AS product_count,
                    COALESCE(SUM(i.quantity * i.avg_cost), 0) AS stock_value
             FROM warehouses w
             LEFT JOIN inventory i ON i.warehouse_id = w.id
             WHERE w.branch_id = ? AND w.is_active = 1 AND w.deleted_at IS NULL
             GROUP BY w.id
             ORDER BY w.name ASC",
            [$branchId]
        );
        $this->success($rows);
    }
}
