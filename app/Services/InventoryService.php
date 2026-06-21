<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function stockIn(
        int     $productId,
        int     $warehouseId,
        float   $quantity,
        float   $unitCost,
        ?int    $variantId = null,
        ?string $reference = null,
        ?string $notes = null,
        int     $createdBy = 0,
    ): array {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $unitCost, $variantId, $reference, $notes, $createdBy) {
            $inventory = $this->getInventoryRow($productId, $warehouseId, $variantId);
            $branchId  = Warehouse::findOrFail($warehouseId)->branch_id;

            if ($inventory) {
                $existingQty  = (float) $inventory->quantity;
                $existingCost = (float) $inventory->avg_cost;
                $newQty       = $existingQty + $quantity;
                $newAvgCost   = $newQty > 0
                    ? ($existingQty * $existingCost + $quantity * $unitCost) / $newQty
                    : $unitCost;

                $inventory->update([
                    'quantity'          => $newQty,
                    'avg_cost'          => round($newAvgCost, 4),
                    'last_restock_date' => now()->toDateString(),
                ]);
                $balanceAfter = $newQty;
            } else {
                Inventory::create([
                    'product_id'        => $productId,
                    'variant_id'        => $variantId,
                    'warehouse_id'      => $warehouseId,
                    'branch_id'         => $branchId,
                    'quantity'          => $quantity,
                    'reserved_quantity' => 0,
                    'avg_cost'          => $unitCost,
                    'last_restock_date' => now()->toDateString(),
                ]);
                $balanceAfter = $quantity;
            }

            $movement = StockMovement::create([
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
            ]);

            return ['movement_id' => $movement->id, 'balance_after' => $balanceAfter];
        });
    }

    public function stockOut(
        int     $productId,
        int     $warehouseId,
        float   $quantity,
        ?int    $variantId = null,
        ?string $reference = null,
        ?string $notes = null,
        int     $createdBy = 0,
    ): array {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $variantId, $reference, $notes, $createdBy) {
            $inventory = $this->getInventoryRow($productId, $warehouseId, $variantId);
            $available = $inventory
                ? (float) $inventory->quantity - (float) $inventory->reserved_quantity
                : 0.0;

            if ($available < $quantity) {
                throw new \RuntimeException(
                    "Insufficient stock. Available: {$available}, Requested: {$quantity}"
                );
            }

            $branchId     = Warehouse::findOrFail($warehouseId)->branch_id;
            $unitCost     = (float) ($inventory?->avg_cost ?? 0);
            $newQty       = (float) $inventory->quantity - $quantity;
            $balanceAfter = $newQty;

            $inventory->update(['quantity' => $newQty]);

            $movement = StockMovement::create([
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
            ]);

            return ['movement_id' => $movement->id, 'balance_after' => $balanceAfter, 'unit_cost' => $unitCost];
        });
    }

    public function adjustStock(
        int     $productId,
        int     $warehouseId,
        float   $newQuantity,
        string  $reason = 'Manual adjustment',
        int     $createdBy = 0,
        ?int    $variantId = null,
    ): array {
        return DB::transaction(function () use ($productId, $warehouseId, $newQuantity, $reason, $createdBy, $variantId) {
            $inventory  = $this->getInventoryRow($productId, $warehouseId, $variantId);
            $currentQty = $inventory ? (float) $inventory->quantity : 0.0;
            $diff       = $newQuantity - $currentQty;
            $branchId   = Warehouse::findOrFail($warehouseId)->branch_id;
            $unitCost   = (float) ($inventory?->avg_cost ?? 0);

            if ($inventory) {
                $inventory->update(['quantity' => $newQuantity]);
            } else {
                Inventory::create([
                    'product_id'        => $productId,
                    'variant_id'        => $variantId,
                    'warehouse_id'      => $warehouseId,
                    'branch_id'         => $branchId,
                    'quantity'          => $newQuantity,
                    'reserved_quantity' => 0,
                    'avg_cost'          => 0,
                ]);
            }

            $movement = StockMovement::create([
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
            ]);

            return [
                'movement_id'  => $movement->id,
                'previous_qty' => $currentQty,
                'new_qty'      => $newQuantity,
                'difference'   => $diff,
            ];
        });
    }

    public function getStockLevel(int $productId, int $warehouseId, ?int $variantId = null): float
    {
        $row = $this->getInventoryRow($productId, $warehouseId, $variantId);
        return $row ? (float) $row->quantity : 0.0;
    }

    public function getLowStockProducts(int $branchId): array
    {
        return DB::select(
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
        $rows = DB::select(
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

    private function getInventoryRow(int $productId, int $warehouseId, ?int $variantId): ?Inventory
    {
        $query = Inventory::where('product_id', $productId)->where('warehouse_id', $warehouseId);

        return $variantId !== null
            ? $query->where('variant_id', $variantId)->first()
            : $query->whereNull('variant_id')->first();
    }
}
