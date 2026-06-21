<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockAdjustmentController extends BaseController
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $adjustments = \App\Models\StockAdjustment::with(['warehouse', 'items'])->latest()->paginate(20);
        $warehouses  = Warehouse::orderBy('name')->get();
        return view('inventory.adjustments.index', compact('adjustments', 'warehouses'));
    }

    public function create(): View
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('inventory.adjustments.create', compact('products', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id'              => ['required', 'integer', 'exists:warehouses,id'],
            'reason'                    => ['required', 'string'],
            'date'                      => ['required', 'date'],
            'note'                      => ['nullable', 'string', 'max:500'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'integer', 'exists:products,id'],
            'items.*.adjusted_quantity' => ['required', 'numeric'],
        ]);

        foreach ($data['items'] as $item) {
            $this->inventoryService->adjustStock(
                productId:        $item['product_id'],
                warehouseId:      $data['warehouse_id'],
                adjustedQuantity: $item['adjusted_quantity'],
                reason:           $data['reason'],
                note:             $data['note'] ?? null,
                userId:           Auth::id(),
            );
        }

        $this->success('Stock adjustment recorded.');
        return redirect()->route('stock-adjustments.index');
    }

    public function show(int $id): View
    {
        $adjustment = \App\Models\StockAdjustment::with(['warehouse', 'items.product'])->findOrFail($id);
        return view('inventory.adjustments.show', compact('adjustment'));
    }

    public function reports(Request $request): View
    {
        $lowStock = $this->inventoryService->getLowStockProducts();
        return view('inventory.reports.index', compact('lowStock'));
    }
}
