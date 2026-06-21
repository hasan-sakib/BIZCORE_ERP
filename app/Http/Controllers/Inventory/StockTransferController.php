<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockTransferController extends BaseController
{
    public function __construct(private readonly StockTransferService $stockTransferService) {}

    public function index(Request $request): View
    {
        $transfers  = $this->stockTransferService->paginate($request->all());
        $warehouses = Warehouse::orderBy('name')->get();
        return view('inventory.transfers.index', compact('transfers', 'warehouses'));
    }

    public function create(): View
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('inventory.transfers.create', compact('products', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_warehouse_id'  => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id'    => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'transfer_date'      => ['required', 'date'],
            'note'               => ['nullable', 'string', 'max:500'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
        ]);

        $transfer = $this->stockTransferService->create($data, Auth::id());
        $this->success('Stock transfer created and awaiting approval.');
        return redirect()->route('stock-transfers.show', $transfer->id);
    }

    public function show(int $id): View
    {
        $transfer = \App\Models\StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product'])->findOrFail($id);
        return view('inventory.transfers.show', compact('transfer'));
    }

    public function approve(int $id): RedirectResponse
    {
        $this->stockTransferService->approve($id, Auth::id());
        $this->success('Transfer approved and stock updated.');
        return back();
    }

    public function cancel(int $id): RedirectResponse
    {
        $this->stockTransferService->cancel($id, Auth::id());
        $this->success('Transfer cancelled.');
        return back();
    }
}
