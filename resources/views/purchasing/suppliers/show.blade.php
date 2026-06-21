@extends('layouts.app')
@section('title', $supplier->name)
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('suppliers.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">{{ $supplier->name }}</h5>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn btn-sm btn-outline-secondary">Ledger</a>
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
</div>
@include('components.flash-messages')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Contact</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Phone</dt><dd class="col-sm-7">{{ $supplier->phone ?? '—' }}</dd>
                    <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $supplier->email ?? '—' }}</dd>
                    <dt class="col-sm-5">Address</dt><dd class="col-sm-7">{{ $supplier->address ?? '—' }}</dd>
                    <dt class="col-sm-5">Tax ID</dt><dd class="col-sm-7">{{ $supplier->tax_id ?? '—' }}</dd>
                    <dt class="col-sm-5">Balance</dt><dd class="col-sm-7">৳ {{ number_format($supplier->current_balance ?? 0, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Recent Purchase Orders</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>PO #</th><th>Date</th><th>Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($orders ?? [] as $order)
                            <tr>
                                <td>{{ $order->po_number }}</td>
                                <td>{{ $order->order_date?->format('d M Y') }}</td>
                                <td>৳ {{ number_format($order->total_amount ?? 0, 2) }}</td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($order->status) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
