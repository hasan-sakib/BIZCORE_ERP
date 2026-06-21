<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryApiController extends BaseApiController
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function stockLevels(Request $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $levels      = \App\Models\StockLevel::with(['product', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->paginate(50);
        return $this->paginate($levels);
    }

    public function stockLevel(int $productId, Request $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $level       = $this->inventoryService->getStockLevel($productId, $warehouseId);
        return $this->success($level);
    }

    public function movements(Request $request): JsonResponse
    {
        $movements = \App\Models\StockMovement::with(['product', 'warehouse'])
            ->when($request->get('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->get('warehouse_id'), fn ($q, $id) => $q->where('warehouse_id', $id))
            ->latest()
            ->paginate(30);
        return $this->paginate($movements);
    }

    public function stockIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity'     => ['required', 'numeric', 'min:0.001'],
            'unit_cost'    => ['required', 'numeric', 'min:0'],
            'reference'    => ['nullable', 'string'],
        ]);

        $level = $this->inventoryService->stockIn(
            productId:   $data['product_id'],
            warehouseId: $data['warehouse_id'],
            quantity:    $data['quantity'],
            unitCost:    $data['unit_cost'],
            reference:   $data['reference'] ?? null,
            userId:      $this->currentUser()?->id,
        );

        return $this->created($level);
    }

    public function stockOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity'     => ['required', 'numeric', 'min:0.001'],
            'reason'       => ['required', 'string'],
            'reference'    => ['nullable', 'string'],
        ]);

        $this->inventoryService->stockOut(
            productId:   $data['product_id'],
            warehouseId: $data['warehouse_id'],
            quantity:    $data['quantity'],
            reason:      $data['reason'],
            reference:   $data['reference'] ?? null,
            userId:      $this->currentUser()?->id,
        );

        return $this->success(['message' => 'Stock out recorded.']);
    }

    public function lowStock(): JsonResponse
    {
        return $this->success($this->inventoryService->getLowStockProducts());
    }
}
