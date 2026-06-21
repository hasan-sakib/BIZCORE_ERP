<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\BaseController;
use App\Models\Customer;
use App\Services\InvoiceService;
use App\Services\PdfService;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends BaseController
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly SalesService   $salesService,
        private readonly PdfService     $pdfService,
    ) {}

    public function index(Request $request): View
    {
        $invoices  = $this->invoiceService->paginate($request->all());
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        return view('sales.invoices.index', compact('invoices', 'customers'));
    }

    public function create(Request $request): View
    {
        $orderId   = $request->integer('order_id');
        $order     = $orderId ? \App\Models\SalesOrder::with('items.product')->find($orderId) : null;
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        return view('sales.invoices.create', compact('order', 'customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sales_order_id'    => ['required', 'integer', 'exists:sales_orders,id'],
            'invoice_date'      => ['required', 'date'],
            'due_date'          => ['required', 'date', 'after_or_equal:invoice_date'],
            'discount_amount'   => ['nullable', 'numeric', 'min:0'],
            'note'              => ['nullable', 'string'],
        ]);

        $invoice = $this->salesService->createInvoice($data, Auth::id());
        $this->success('Invoice created successfully.');
        return redirect()->route('invoices.show', $invoice->id);
    }

    public function show(int $id): View
    {
        $invoice = \App\Models\Invoice::with(['customer', 'items.product', 'payments'])->findOrFail($id);
        return view('sales.invoices.show', compact('invoice'));
    }

    public function edit(int $id): View
    {
        $invoice = \App\Models\Invoice::findOrFail($id);
        return view('sales.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'due_date'        => ['required', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string'],
        ]);

        \App\Models\Invoice::findOrFail($id)->update($data);
        $this->success('Invoice updated.');
        return redirect()->route('invoices.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->invoiceService->cancel($id, Auth::id());
        $this->success('Invoice cancelled.');
        return redirect()->route('invoices.index');
    }

    public function pdf(int $id): Response
    {
        $invoice = \App\Models\Invoice::with(['customer', 'items.product', 'salesOrder'])->findOrFail($id);
        return $this->pdfService->download('sales.invoices.pdf', compact('invoice'), "invoice-{$invoice->invoice_number}.pdf");
    }
}
