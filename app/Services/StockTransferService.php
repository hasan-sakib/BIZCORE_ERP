<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function create(
        int     $fromWarehouseId,
        int     $toWarehouseId,
        array   $items,
        ?string $notes = null,
        int     $createdBy = 0,
    ): StockTransfer {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException('Source and destination warehouses must be different.');
        }

        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $items, $notes, $createdBy) {
            $transfer = StockTransfer::create([
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id'   => $toWarehouseId,
                'status'            => 'pending',
                'notes'             => $notes,
                'created_by'        => $createdBy,
            ]);

            foreach ($items as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id'        => $item['product_id'],
                    'variant_id'        => $item['variant_id'] ?? null,
                    'quantity'          => $item['quantity'],
                ]);
            }

            return $transfer->load('items');
        });
    }

    public function approve(int $id, int $approvedBy): StockTransfer
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);

        if ($transfer->status !== 'pending') {
            throw new \RuntimeException('Only pending transfers can be approved.');
        }

        DB::transaction(function () use ($transfer, $approvedBy) {
            foreach ($transfer->items as $item) {
                $result = $this->inventoryService->stockOut(
                    productId:   $item->product_id,
                    warehouseId: $transfer->from_warehouse_id,
                    quantity:    (float) $item->quantity,
                    variantId:   $item->variant_id,
                    reference:   "TRANSFER-{$transfer->id}",
                    createdBy:   $approvedBy
                );

                $this->inventoryService->stockIn(
                    productId:   $item->product_id,
                    warehouseId: $transfer->to_warehouse_id,
                    quantity:    (float) $item->quantity,
                    unitCost:    $result['unit_cost'],
                    variantId:   $item->variant_id,
                    reference:   "TRANSFER-{$transfer->id}",
                    createdBy:   $approvedBy
                );
            }

            $transfer->update(['status' => 'completed', 'approved_by' => $approvedBy]);
        });

        return $transfer->fresh();
    }

    public function cancel(int $id): StockTransfer
    {
        $transfer = StockTransfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            throw new \RuntimeException('Only pending transfers can be cancelled.');
        }

        $transfer->update(['status' => 'cancelled']);
        return $transfer->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse'])
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->where(fn($q) => $q
                ->where('from_warehouse_id', $filters['warehouse_id'])
                ->orWhere('to_warehouse_id', $filters['warehouse_id'])
            );
        }

        return $query->paginate($perPage);
    }
}
