<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Services\InvoiceService;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesApiController extends BaseApiController
{
    public function __construct(
        private readonly SalesService   $salesService,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function orders(Request $request): JsonResponse
    {
        $orders = SalesOrder::with('customer')
            ->when($request->get('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);
        return $this->paginate($orders);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'           => ['required', 'integer', 'exists:customers,id'],
            'warehouse_id'          => ['required', 'integer', 'exists:warehouses,id'],
            'order_date'            => ['required', 'date'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
        ]);
        return $this->created($this->salesService->createOrder($data, $this->currentUser()?->id));
    }

    public function invoices(Request $request): JsonResponse
    {
        return $this->paginate($this->invoiceService->paginate($request->all()));
    }

    public function showInvoice(int $id): JsonResponse
    {
        return $this->success(Invoice::with(['customer', 'items.product'])->findOrFail($id));
    }

    public function createInvoice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sales_order_id' => ['required', 'integer', 'exists:sales_orders,id'],
            'invoice_date'   => ['required', 'date'],
            'due_date'       => ['required', 'date'],
        ]);
        return $this->created($this->salesService->createInvoice($data, $this->currentUser()?->id));
    }

    public function receivePayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'    => ['required', 'integer', 'exists:customers,id'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'string'],
        ]);
        return $this->created($this->salesService->receivePayment($data, $this->currentUser()?->id));
    }

    public function quotations(Request $request): JsonResponse
    {
        $quotations = Quotation::with('customer')
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);
        return $this->paginate($quotations);
    }
}
