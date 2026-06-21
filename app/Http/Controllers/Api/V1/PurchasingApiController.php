<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Models\GoodsReceipt;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchasingApiController extends BaseApiController
{
    public function __construct(private readonly PurchaseService $purchaseService) {}

    public function orders(Request $request): JsonResponse
    {
        return $this->paginate($this->purchaseService->paginate($request->all()));
    }

    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id'        => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id'       => ['required', 'integer', 'exists:warehouses,id'],
            'order_date'         => ['required', 'date'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        return $this->created($this->purchaseService->createOrder($data, $this->currentUser()?->id));
    }

    public function approveOrder(int $id): JsonResponse
    {
        return $this->success($this->purchaseService->approve($id, $this->currentUser()?->id));
    }

    public function goodsReceipts(Request $request): JsonResponse
    {
        $grns = GoodsReceipt::with('purchaseOrder.supplier')
            ->when($request->get('po_id'), fn ($q, $id) => $q->where('purchase_order_id', $id))
            ->latest()
            ->paginate(20);
        return $this->paginate($grns);
    }

    public function createGoodsReceipt(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purchase_order_id'  => ['required', 'integer', 'exists:purchase_orders,id'],
            'received_date'      => ['required', 'date'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost'  => ['required', 'numeric', 'min:0'],
        ]);
        return $this->created($this->purchaseService->createGoodsReceipt($data, $this->currentUser()?->id));
    }
}
