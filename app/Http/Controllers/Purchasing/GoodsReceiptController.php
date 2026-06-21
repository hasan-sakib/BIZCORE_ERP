<?php

declare(strict_types=1);

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\BaseController;
use App\Models\GoodsReceipt;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GoodsReceiptController extends BaseController
{
    public function __construct(private readonly PurchaseService $purchaseService) {}

    public function index(Request $request): View
    {
        $receipts = GoodsReceipt::with('purchaseOrder.supplier')->latest()->paginate(20);
        return view('purchasing.grn.index', compact('receipts'));
    }

    public function create(Request $request): View
    {
        $orderId = $request->integer('order_id');
        $order   = $orderId ? \App\Models\PurchaseOrder::with('items.product')->findOrFail($orderId) : null;
        $orders  = \App\Models\PurchaseOrder::where('status', 'approved')->with('supplier')->get();
        return view('purchasing.grn.create', compact('order', 'orders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_id'      => ['required', 'integer', 'exists:purchase_orders,id'],
            'received_date'          => ['required', 'date'],
            'reference'              => ['nullable', 'string', 'max:100'],
            'note'                   => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost'      => ['required', 'numeric', 'min:0'],
        ]);

        $grn = $this->purchaseService->createGoodsReceipt($data, Auth::id());
        $this->success('Goods receipt recorded and inventory updated.');
        return redirect()->route('goods-receipts.show', $grn->id);
    }

    public function show(int $id): View
    {
        $receipt = GoodsReceipt::with(['purchaseOrder.supplier', 'items.product'])->findOrFail($id);
        return view('purchasing.grn.show', compact('receipt'));
    }
}
