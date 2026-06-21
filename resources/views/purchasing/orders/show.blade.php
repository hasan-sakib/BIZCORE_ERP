@extends('layouts.app')
@section('title', 'Purchase Order')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('purchase-orders.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">PO #{{ $order->po_number }}</h5>
    <div class="ms-auto d-flex gap-2">
        @if($order->status === 'approved')
        <a href="{{ route('goods-receipt.create', ['purchase_order_id' => $order->id]) }}" class="btn btn-sm btn-success">Receive Goods</a>
        @endif
        <a href="{{ route('purchase-orders.pdf', $order) }}" class="btn btn-sm btn-outline-dark" target="_blank"><i class="fa-solid fa-file-pdf"></i></a>
    </div>
</div>
@include('components.flash-messages')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Order Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Supplier</dt><dd class="col-sm-7">{{ $order->supplier?->name }}</dd>
                    <dt class="col-sm-5">Date</dt><dd class="col-sm-7">{{ $order->order_date?->format('d M Y') }}</dd>
                    <dt class="col-sm-5">Expected</dt><dd class="col-sm-7">{{ $order->expected_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-sm-5">Status</dt><dd class="col-sm-7"><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($order->status) }}</span></dd>
                    <dt class="col-sm-5">Total</dt><dd class="col-sm-7 fw-bold">৳ {{ number_format($order->total_amount ?? 0, 2) }}</dd>
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
                        <thead class="table-light"><tr><th>Product</th><th>Qty</th><th>Unit Cost</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product?->name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>৳ {{ number_format($item->unit_cost ?? 0, 2) }}</td>
                                <td class="text-end">৳ {{ number_format($item->total_cost ?? ($item->quantity * $item->unit_cost), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr><td colspan="3" class="text-end fw-bold">Total:</td><td class="text-end fw-bold">৳ {{ number_format($order->total_amount ?? 0, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
