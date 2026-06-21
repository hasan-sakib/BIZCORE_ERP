<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\BaseController;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SalesOrderController extends BaseController
{
    public function __construct(private readonly SalesService $salesService) {}

    public function index(Request $request): View
    {
        $orders    = \App\Models\SalesOrder::with('customer')
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        return view('sales.orders.index', compact('orders', 'customers'));
    }

    public function create(): View
    {
        $customers  = Customer::where('status', 'active')->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('sales.orders.create', compact('customers', 'products', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id'            => ['required', 'integer', 'exists:customers,id'],
            'warehouse_id'           => ['required', 'integer', 'exists:warehouses,id'],
            'order_date'             => ['required', 'date'],
            'delivery_date'          => ['nullable', 'date', 'after_or_equal:order_date'],
            'discount_amount'        => ['nullable', 'numeric', 'min:0'],
            'note'                   => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.vat_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $order = $this->salesService->createOrder($data, Auth::id());
        $this->success('Sales order created successfully.');
        return redirect()->route('sales-orders.show', $order->id);
    }

    public function show(int $id): View
    {
        $order = \App\Models\SalesOrder::with(['customer', 'items.product', 'invoices'])->findOrFail($id);
        return view('sales.orders.show', compact('order'));
    }

    public function edit(int $id): View
    {
        $order      = \App\Models\SalesOrder::with('items')->findOrFail($id);
        $customers  = Customer::where('status', 'active')->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('sales.orders.edit', compact('order', 'customers', 'products', 'warehouses'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'delivery_date'   => ['nullable', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string'],
            'status'          => ['required', 'string'],
        ]);

        \App\Models\SalesOrder::findOrFail($id)->update($data);
        $this->success('Sales order updated.');
        return redirect()->route('sales-orders.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        \App\Models\SalesOrder::findOrFail($id)->delete();
        $this->success('Sales order deleted.');
        return redirect()->route('sales-orders.index');
    }
}
