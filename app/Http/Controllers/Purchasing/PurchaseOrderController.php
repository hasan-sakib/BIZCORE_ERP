<?php

declare(strict_types=1);

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PdfService;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PurchaseOrderController extends BaseController
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
        private readonly PdfService      $pdfService,
    ) {}

    public function index(Request $request): View
    {
        $orders    = $this->purchaseService->paginate($request->all());
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        return view('purchasing.orders.index', compact('orders', 'suppliers'));
    }

    public function create(): View
    {
        $suppliers  = Supplier::where('status', 'active')->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('purchasing.orders.create', compact('suppliers', 'products', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id'             => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id'            => ['required', 'integer', 'exists:warehouses,id'],
            'order_date'              => ['required', 'date'],
            'expected_delivery'       => ['nullable', 'date', 'after_or_equal:order_date'],
            'discount_amount'         => ['nullable', 'numeric', 'min:0'],
            'note'                    => ['nullable', 'string'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'        => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.vat_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $order = $this->purchaseService->createOrder($data, Auth::id());
        $this->success('Purchase order created.');
        return redirect()->route('purchase-orders.show', $order->id);
    }

    public function show(int $id): View
    {
        $order = \App\Models\PurchaseOrder::with(['supplier', 'items.product', 'goodsReceipts'])->findOrFail($id);
        return view('purchasing.orders.show', compact('order'));
    }

    public function edit(int $id): View
    {
        $order      = \App\Models\PurchaseOrder::with('items')->findOrFail($id);
        $suppliers  = Supplier::where('status', 'active')->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('purchasing.orders.edit', compact('order', 'suppliers', 'products', 'warehouses'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'expected_delivery' => ['nullable', 'date'],
            'discount_amount'   => ['nullable', 'numeric', 'min:0'],
            'note'              => ['nullable', 'string'],
            'status'            => ['required', 'string'],
        ]);

        \App\Models\PurchaseOrder::findOrFail($id)->update($data);
        $this->success('Purchase order updated.');
        return redirect()->route('purchase-orders.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        \App\Models\PurchaseOrder::findOrFail($id)->delete();
        $this->success('Purchase order deleted.');
        return redirect()->route('purchase-orders.index');
    }

    public function approve(int $id): RedirectResponse
    {
        $this->purchaseService->approve($id, Auth::id());
        $this->success('Purchase order approved.');
        return back();
    }

    public function pdf(int $id): Response
    {
        $order = \App\Models\PurchaseOrder::with(['supplier', 'items.product'])->findOrFail($id);
        return $this->pdfService->download('purchasing.orders.pdf', compact('order'), "po-{$order->id}.pdf");
    }

    public function reports(Request $request): View
    {
        $orders = $this->purchaseService->paginate(array_merge($request->all(), ['per_page' => 50]));
        return view('purchasing.reports.index', compact('orders'));
    }
}
