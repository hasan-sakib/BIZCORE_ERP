<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function createOrder(array $data): PurchaseOrder
    {
        $data['po_number'] ??= $this->generateNumber('PO', 'purchase_orders', 'po_number');
        $items = $data['items'] ?? [];
        unset($data['items']);

        return DB::transaction(function () use ($data, $items) {
            $subtotal = 0.0;
            $vatTotal = 0.0;
            foreach ($items as $item) {
                $line = (float) $item['quantity'] * (float) $item['unit_price'];
                $subtotal += $line;
                $vatTotal += $line * ((float) ($item['vat_rate'] ?? 0) / 100);
            }

            $data['subtotal']     = round($subtotal, 2);
            $data['vat_amount']   = round($vatTotal, 2);
            $data['total_amount'] = round($subtotal + $vatTotal - (float) ($data['discount_amount'] ?? 0), 2);
            $data['status']       ??= 'draft';

            $po = PurchaseOrder::create($data);

            foreach ($items as $item) {
                $line    = (float) $item['quantity'] * (float) $item['unit_price'];
                $lineVat = $line * ((float) ($item['vat_rate'] ?? 0) / 100);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'unit_price'        => $item['unit_price'],
                    'vat_rate'          => $item['vat_rate'] ?? 0,
                    'vat_amount'        => round($lineVat, 2),
                    'total'             => round($line + $lineVat, 2),
                ]);
            }

            return $po->load('items');
        });
    }

    public function approve(int $id, int $approvedBy): PurchaseOrder
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'submitted') {
            throw new \RuntimeException('Only submitted orders can be approved.');
        }

        $po->update(['status' => 'approved', 'approved_by' => $approvedBy]);
        return $po->fresh();
    }

    public function createGoodsReceipt(int $purchaseOrderId, array $items, array $data, int $createdBy = 0): GoodsReceipt
    {
        $po = PurchaseOrder::with('items')->findOrFail($purchaseOrderId);

        if (!in_array($po->status, ['approved', 'partial'])) {
            throw new \RuntimeException('Order must be approved before receiving goods.');
        }

        return DB::transaction(function () use ($po, $items, $data, $createdBy) {
            $grn = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'supplier_id'       => $po->supplier_id,
                'branch_id'         => $po->branch_id,
                'warehouse_id'      => $data['warehouse_id'],
                'grn_number'        => $this->generateNumber('GRN', 'goods_receipts', 'grn_number'),
                'received_date'     => $data['received_date'] ?? now()->toDateString(),
                'notes'             => $data['notes'] ?? null,
                'created_by'        => $createdBy,
                'status'            => 'completed',
            ]);

            foreach ($items as $item) {
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $grn->id,
                    'product_id'       => $item['product_id'],
                    'po_item_id'       => $item['po_item_id'] ?? null,
                    'quantity'         => $item['quantity'],
                    'unit_cost'        => $item['unit_cost'],
                    'total_cost'       => (float) $item['quantity'] * (float) $item['unit_cost'],
                ]);

                $this->inventoryService->stockIn(
                    productId:   (int) $item['product_id'],
                    warehouseId: (int) $data['warehouse_id'],
                    quantity:    (float) $item['quantity'],
                    unitCost:    (float) $item['unit_cost'],
                    reference:   $grn->grn_number,
                    notes:       "GRN {$grn->grn_number}",
                    createdBy:   $createdBy,
                );
            }

            // Mark PO as received/partial
            $receivedAll = $this->checkAllItemsReceived($po);
            $po->update(['status' => $receivedAll ? 'received' : 'partial']);

            return $grn->load('items');
        });
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PurchaseOrder::with(['supplier', 'branch'])->orderByDesc('order_date');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        return $query->paginate($perPage);
    }

    private function checkAllItemsReceived(PurchaseOrder $po): bool
    {
        foreach ($po->items as $item) {
            $received = GoodsReceiptItem::whereHas(
                'goodsReceipt',
                fn($q) => $q->where('purchase_order_id', $po->id)
            )->where('product_id', $item->product_id)->sum('quantity');

            if ($received < $item->quantity) {
                return false;
            }
        }
        return true;
    }

    private function generateNumber(string $prefix, string $table, string $column): string
    {
        $year  = date('Y');
        $count = DB::table($table)->where($column, 'like', "{$prefix}-{$year}-%")->count();
        return "{$prefix}-{$year}-" . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
