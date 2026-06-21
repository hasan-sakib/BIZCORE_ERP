<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\BaseController;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends BaseController
{
    public function __construct(private readonly SalesService $salesService) {}

    public function index(Request $request): View
    {
        $payments  = Payment::with('payer')
            ->when($request->get('customer_id'), fn ($q, $id) => $q->where('payer_type', Customer::class)->where('payer_id', $id))
            ->latest()
            ->paginate(20);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        return view('sales.payments.index', compact('payments', 'customers'));
    }

    public function create(Request $request): View
    {
        $customerId = $request->integer('customer_id');
        $invoiceId  = $request->integer('invoice_id');
        $customer   = $customerId ? Customer::find($customerId) : null;
        $invoice    = $invoiceId ? \App\Models\Invoice::find($invoiceId) : null;
        $customers  = Customer::where('status', 'active')->orderBy('name')->get();
        return view('sales.payments.create', compact('customer', 'invoice', 'customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id'    => ['required', 'integer', 'exists:customers,id'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'reference'      => ['nullable', 'string', 'max:100'],
            'note'           => ['nullable', 'string'],
        ]);

        $this->salesService->receivePayment($data, Auth::id());
        $this->success('Payment recorded and allocated to outstanding invoices.');
        return redirect()->route('payments.index');
    }

    public function show(int $id): View
    {
        $payment = Payment::with(['payer', 'allocations.invoice'])->findOrFail($id);
        return view('sales.payments.show', compact('payment'));
    }

    public function reports(Request $request): View
    {
        $payments = Payment::with('payer')
            ->when($request->get('from'), fn ($q, $d) => $q->whereDate('payment_date', '>=', $d))
            ->when($request->get('to'),   fn ($q, $d) => $q->whereDate('payment_date', '<=', $d))
            ->latest()
            ->paginate(30);
        return view('sales.payments.reports', compact('payments'));
    }
}
