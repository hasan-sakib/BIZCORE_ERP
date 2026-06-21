@extends('layouts.app')
@section('title', 'Invoice')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('invoices.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Invoice #{{ $invoice->invoice_number }}</h5>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
        @if(!in_array($invoice->status, ['paid','cancelled']))
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endif
    </div>
</div>
@include('components.flash-messages')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Invoice Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Customer</dt><dd class="col-sm-7">{{ $invoice->customer?->name }}</dd>
                    <dt class="col-sm-5">Date</dt><dd class="col-sm-7">{{ $invoice->invoice_date?->format('d M Y') }}</dd>
                    <dt class="col-sm-5">Due Date</dt><dd class="col-sm-7 {{ $invoice->due_date?->isPast() && $invoice->status !== 'paid' ? 'text-danger fw-semibold' : '' }}">{{ $invoice->due_date?->format('d M Y') }}</dd>
                    <dt class="col-sm-5">Status</dt><dd class="col-sm-7">
                        @php $statusColors = ['draft'=>'secondary','sent'=>'primary','partial'=>'warning','paid'=>'success','cancelled'=>'danger']; @endphp
                        <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$invoice->status] ?? 'secondary' }}">{{ ucfirst($invoice->status) }}</span>
                    </dd>
                    <dt class="col-sm-5">Total</dt><dd class="col-sm-7 fw-bold">৳ {{ number_format($invoice->total_amount ?? 0, 2) }}</dd>
                    <dt class="col-sm-5">Paid</dt><dd class="col-sm-7 text-success">৳ {{ number_format($invoice->paid_amount ?? 0, 2) }}</dd>
                    <dt class="col-sm-5">Balance</dt><dd class="col-sm-7 text-danger fw-bold">৳ {{ number_format(($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0), 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Line Items</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Product</th><th>Qty</th><th>Price</th><th>VAT</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->product?->name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>৳ {{ number_format($item->unit_price ?? 0, 2) }}</td>
                                <td>{{ $item->vat_percent ?? 0 }}%</td>
                                <td class="text-end">৳ {{ number_format($item->total_price ?? ($item->quantity * $item->unit_price), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            @if($invoice->vat_amount)
                            <tr><td colspan="4" class="text-end">VAT:</td><td class="text-end">৳ {{ number_format($invoice->vat_amount, 2) }}</td></tr>
                            @endif
                            @if($invoice->discount_amount)
                            <tr><td colspan="4" class="text-end">Discount:</td><td class="text-end">-৳ {{ number_format($invoice->discount_amount, 2) }}</td></tr>
                            @endif
                            <tr><td colspan="4" class="text-end fw-bold">Grand Total:</td><td class="text-end fw-bold">৳ {{ number_format($invoice->total_amount ?? 0, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
