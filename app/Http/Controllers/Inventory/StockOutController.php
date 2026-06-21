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

class StockOutController extends BaseController
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $orders     = \App\Models\StockOutOrder::with(['warehouse', 'items'])->latest()->paginate(20);
        $warehouses = Warehouse::orderBy('name')->get();
        return view('inventory.stock-out.index', compact('orders', 'warehouses'));
    }

    public function create(): View
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('inventory.stock-out.create', compact('products', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id'       => ['required', 'integer', 'exists:warehouses,id'],
            'reason'             => ['required', 'string'],
            'reference'          => ['nullable', 'string', 'max:100'],
            'date'               => ['required', 'date'],
            'note'               => ['nullable', 'string', 'max:500'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
        ]);

        foreach ($data['items'] as $item) {
            $this->inventoryService->stockOut(
                productId:   $item['product_id'],
                warehouseId: $data['warehouse_id'],
                quantity:    $item['quantity'],
                reason:      $data['reason'],
                reference:   $data['reference'] ?? null,
                userId:      Auth::id(),
            );
        }

        $this->success('Stock out recorded successfully.');
        return redirect()->route('stock-out.index');
    }

    public function show(int $id): View
    {
        $order = \App\Models\StockOutOrder::with(['warehouse', 'items.product'])->findOrFail($id);
        return view('inventory.stock-out.show', compact('order'));
    }
}
