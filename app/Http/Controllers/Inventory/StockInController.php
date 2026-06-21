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

class StockInController extends BaseController
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $orders     = \App\Models\StockInOrder::with(['warehouse', 'items'])->latest()->paginate(20);
        $warehouses = Warehouse::orderBy('name')->get();
        return view('inventory.stock-in.index', compact('orders', 'warehouses'));
    }

    public function create(): View
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('inventory.stock-in.create', compact('products', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id'       => ['required', 'integer', 'exists:warehouses,id'],
            'reference'          => ['nullable', 'string', 'max:100'],
            'date'               => ['required', 'date'],
            'note'               => ['nullable', 'string', 'max:500'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost'  => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($data['items'] as $item) {
            $this->inventoryService->stockIn(
                productId:   $item['product_id'],
                warehouseId: $data['warehouse_id'],
                quantity:    $item['quantity'],
                unitCost:    $item['unit_cost'],
                reference:   $data['reference'] ?? null,
                userId:      Auth::id(),
            );
        }

        $this->success('Stock received successfully.');
        return redirect()->route('stock-in.index');
    }

    public function show(int $id): View
    {
        $order = \App\Models\StockInOrder::with(['warehouse', 'items.product'])->findOrFail($id);
        return view('inventory.stock-in.show', compact('order'));
    }
}
