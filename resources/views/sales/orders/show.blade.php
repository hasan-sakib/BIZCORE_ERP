@extends('layouts.app')
@section('title', 'Sales Order')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('sales-orders.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Order #{{ $order->order_number }}</h5>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('sales-orders.edit', $order) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
</div>
@include('components.flash-messages')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Order Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Customer</dt><dd class="col-sm-7">{{ $order->customer?->name }}</dd>
                    <dt class="col-sm-5">Date</dt><dd class="col-sm-7">{{ $order->order_date?->format('d M Y') }}</dd>
                    <dt class="col-sm-5">Delivery</dt><dd class="col-sm-7">{{ $order->delivery_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-sm-5">Status</dt><dd class="col-sm-7"><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($order->status) }}</span></dd>
                    <dt class="col-sm-5">Payment</dt><dd class="col-sm-7">{{ ucfirst(str_replace('_',' ',$order->payment_method ?? '')) }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Items</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>VAT</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product?->name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>৳ {{ number_format($item->unit_price ?? 0, 2) }}</td>
                                <td>{{ $item->vat_percent ?? 0 }}%</td>
                                <td>৳ {{ number_format($item->total_price ?? ($item->quantity * $item->unit_price), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            @if($order->discount_amount)
                            <tr><td colspan="4" class="text-end">Discount:</td><td>-৳ {{ number_format($order->discount_amount, 2) }}</td></tr>
                            @endif
                            <tr><td colspan="4" class="text-end fw-bold">Grand Total:</td><td class="fw-bold">৳ {{ number_format($order->total_amount ?? 0, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
