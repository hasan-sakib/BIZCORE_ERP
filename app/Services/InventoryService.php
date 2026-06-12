<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class InventoryService
{
    public function __construct(private readonly Database $db) {}

    public function stockIn(
        int     $productId,
        int     $warehouseId,
        float   $quantity,
        float   $unitCost,
        ?int    $variantId = null,
        ?string $reference = null,
        ?string $notes = null,
        int     $createdBy = 0
    ): array {
        return $this->db->transaction(function () use (
            $productId, $warehouseId, $quantity, $unitCost, $variantId, $reference, $notes, $createdBy
        ) {
            $inventory = $this->getInventoryRow($productId, $warehouseId, $variantId);
            $branchId  = $this->getWarehouseBranchId($warehouseId);

            if ($inventory) {
                $existingQty  = (float)$inventory['quantity'];
                $existingCost = (float)$inventory['avg_cost'];
                $newQty       = $existingQty + $quantity;
                $newAvgCost   = $newQty > 0
                    ? ($existingQty * $existingCost + $quantity * $unitCost) / $newQty
                    : $unitCost;

                $this->db->table('inventory')
                    ->where('id', (int)$inventory['id'])
                    ->update([
                        'quantity'          => $newQty,
                        'avg_cost'          => round($newAvgCost, 4),
                        'last_restock_date' => date('Y-m-d'),
                        'updated_at'        => now(),
                    ]);

                $balanceAfter = $newQty;
            } else {
                $this->db->table('inventory')->insert([
                    'product_id'        => $productId,
                    'variant_id'        => $variantId,
                    'warehouse_id'      => $warehouseId,
                    'branch_id'         => $branchId,
                    'quantity'          => $quantity,
                    'reserved_quantity' => 0,
                    'avg_cost'          => $unitCost,
                    'last_restock_date' => date('Y-m-d'),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                $balanceAfter = $quantity;
            }

            $movementId = $this->db->table('stock_movements')->insert([
                'product_id'    => $productId,
                'variant_id'    => $variantId,
                'warehouse_id'  => $warehouseId,
                'branch_id'     => $branchId,
                'movement_type' => 'in',
                'quantity'      => $quantity,
                'unit_cost'     => $unitCost,
                'total_cost'    => $quantity * $unitCost,
                'balance_after' => $balanceAfter,
                'reference'     => $reference,
                'notes'         => $notes,
                'created_by'    => $createdBy,
                'created_at'    => now(),
            ]);

            return ['movement_id' => $movementId, 'balance_after' => $balanceAfter];
        });
    }

    public function stockOut(
        int     $productId,
        int     $warehouseId,
        float   $quantity,
        ?int    $variantId = null,
        ?string $reference = null,
        ?string $notes = null,
        int     $createdBy = 0
    ): array {
        return $this->db->transaction(function () use (
            $productId, $warehouseId, $quantity, $variantId, $reference, $notes, $createdBy
        ) {
            $inventory = $this->getInventoryRow($productId, $warehouseId, $variantId);
            $available = $inventory
                ? (float)$inventory['quantity'] - (float)$inventory['reserved_quantity']
                : 0.0;

            if ($available < $quantity) {
                throw new \RuntimeException(
                    "Insufficient stock. Available: {$available}, Requested: {$quantity}"
                );
            }

            $branchId     = $this->getWarehouseBranchId($warehouseId);
            $unitCost     = $inventory ? (float)$inventory['avg_cost'] : 0.0;
            $newQty       = (float)$inventory['quantity'] - $quantity;
            $balanceAfter = $newQty;

            $this->db->table('inventory')
                ->where('id', (int)$inventory['id'])
                ->update(['quantity' => $newQty, 'updated_at' => now()]);

            $movementId = $this->db->table('stock_movements')->insert([
                'product_id'    => $productId,
                'variant_id'    => $variantId,
                'warehouse_id'  => $warehouseId,
                'branch_id'     => $branchId,
                'movement_type' => 'out',
                'quantity'      => -$quantity,
                'unit_cost'     => $unitCost,
                'total_cost'    => $quantity * $unitCost,
                'balance_after' => $balanceAfter,
                'reference'     => $reference,
                'notes'         => $notes,
                'created_by'    => $createdBy,
                'created_at'    => now(),
            ]);

            return ['movement_id' => $movementId, 'balance_after' => $balanceAfter, 'unit_cost' => $unitCost];
        });
    }

    public function adjustStock(
        int     $productId,
        int     $warehouseId,
        float   $newQuantity,
        string  $reason = 'Manual adjustment',
        int     $createdBy = 0,
        ?int    $variantId = null
    ): array {
        return $this->db->transaction(function () use (
            $productId, $warehouseId, $newQuantity, $reason, $createdBy, $variantId
        ) {
            $inventory  = $this->getInventoryRow($productId, $warehouseId, $variantId);
            $currentQty = $inventory ? (float)$inventory['quantity'] : 0.0;
            $diff       = $newQuantity - $currentQty;
            $branchId   = $this->getWarehouseBranchId($warehouseId);
            $unitCost   = $inventory ? (float)$inventory['avg_cost'] : 0.0;

            if ($inventory) {
                $this->db->table('inventory')
                    ->where('id', (int)$inventory['id'])
                    ->update(['quantity' => $newQuantity, 'updated_at' => now()]);
            } else {
                $this->db->table('inventory')->insert([
                    'product_id'   => $productId, 'variant_id' => $variantId,
                    'warehouse_id' => $warehouseId, 'branch_id' => $branchId,
                    'quantity'     => $newQuantity, 'reserved_quantity' => 0, 'avg_cost' => 0,
                    'created_at'   => now(), 'updated_at' => now(),
                ]);
            }

            $movementId = $this->db->table('stock_movements')->insert([
                'product_id'    => $productId,
                'variant_id'    => $variantId,
                'warehouse_id'  => $warehouseId,
                'branch_id'     => $branchId,
                'movement_type' => 'adjustment',
                'quantity'      => $diff,
                'unit_cost'     => $unitCost,
                'total_cost'    => abs($diff) * $unitCost,
                'balance_after' => $newQuantity,
                'notes'         => $reason,
                'created_by'    => $createdBy,
                'created_at'    => now(),
            ]);

            return [
                'movement_id'  => $movementId,
                'previous_qty' => $currentQty,
                'new_qty'      => $newQuantity,
                'difference'   => $diff,
            ];
        });
    }

    public function transferStock(
        int     $productId,
        int     $fromWarehouseId,
        int     $toWarehouseId,
        float   $quantity,
        ?int    $variantId = null,
        ?string $notes = null,
        int     $createdBy = 0
    ): array {
        return $this->db->transaction(function () use (
            $productId, $fromWarehouseId, $toWarehouseId, $quantity, $variantId, $notes, $createdBy
        ) {
            // Stock out from source
            $outResult = $this->stockOut(
                productId:   $productId,
                warehouseId: $fromWarehouseId,
                quantity:    $quantity,
                variantId:   $variantId,
                reference:   "TRANSFER-TO-WH{$toWarehouseId}",
                notes:       $notes,
                createdBy:   $createdBy
            );

            // Stock in to destination at the same avg cost
            $inResult = $this->stockIn(
                productId:   $productId,
                warehouseId: $toWarehouseId,
                quantity:    $quantity,
                unitCost:    $outResult['unit_cost'],
                variantId:   $variantId,
                reference:   "TRANSFER-FROM-WH{$fromWarehouseId}",
                notes:       $notes,
                createdBy:   $createdBy
            );

            return [
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id'   => $toWarehouseId,
                'quantity'          => $quantity,
                'unit_cost'         => $outResult['unit_cost'],
                'out_movement_id'   => $outResult['movement_id'],
                'in_movement_id'    => $inResult['movement_id'],
            ];
        });
    }

    public function getStockLevel(int $productId, int $warehouseId, ?int $variantId = null): float
    {
        $row = $this->getInventoryRow($productId, $warehouseId, $variantId);
        return $row ? (float)$row['quantity'] : 0.0;
    }

    public function getLowStockProducts(int $branchId): array
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.name, p.sku, p.reorder_point,
                    COALESCE(SUM(i.quantity), 0) AS total_stock
             FROM products p
             LEFT JOIN inventory i ON i.product_id = p.id
             LEFT JOIN warehouses w ON w.id = i.warehouse_id AND w.branch_id = ?
             WHERE p.is_active = 1 AND p.deleted_at IS NULL
             GROUP BY p.id, p.name, p.sku, p.reorder_point
             HAVING total_stock <= p.reorder_point
             ORDER BY total_stock ASC",
            [$branchId]
        );
    }

    public function getInventoryValue(int $branchId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT c.name AS category, SUM(i.quantity * i.avg_cost) AS value
             FROM inventory i
             JOIN products p ON p.id = i.product_id
             JOIN categories c ON c.id = p.category_id
             WHERE i.branch_id = ?
             GROUP BY c.id, c.name",
            [$branchId]
        );

        $total = array_sum(array_column($rows, 'value'));
        return ['total_value' => $total, 'by_category' => $rows];
    }

    private function getInventoryRow(int $productId, int $warehouseId, ?int $variantId): ?array
    {
        $sql      = "SELECT * FROM inventory WHERE product_id = ? AND warehouse_id = ?";
        $bindings = [$productId, $warehouseId];

        if ($variantId !== null) {
            $sql      .= " AND variant_id = ?";
            $bindings[] = $variantId;
        } else {
            $sql .= " AND variant_id IS NULL";
        }

        return $this->db->fetchOne($sql, $bindings);
    }

    private function getWarehouseBranchId(int $warehouseId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT branch_id FROM warehouses WHERE id = ?",
            [$warehouseId]
        );
    }
}
