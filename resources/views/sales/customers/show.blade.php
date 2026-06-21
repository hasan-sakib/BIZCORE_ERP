@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('customers.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">{{ $customer->name }}</h5>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('customers.ledger', $customer) }}" class="btn btn-sm btn-outline-secondary">Ledger</a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
</div>
@include('components.flash-messages')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Contact</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Phone</dt><dd class="col-sm-7">{{ $customer->phone ?? '—' }}</dd>
                    <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $customer->email ?? '—' }}</dd>
                    <dt class="col-sm-5">Address</dt><dd class="col-sm-7">{{ $customer->address ?? '—' }}</dd>
                    <dt class="col-sm-5">Tax ID</dt><dd class="col-sm-7">{{ $customer->tax_id ?? '—' }}</dd>
                    <dt class="col-sm-5">Status</dt><dd class="col-sm-7">
                        <span class="badge bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($customer->status) }}</span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="fw-bold text-primary mb-0">৳ {{ number_format($customer->credit_limit ?? 0, 2) }}</h4>
                        <p class="text-muted small mb-0">Credit Limit</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="fw-bold text-{{ ($customer->current_balance ?? 0) > 0 ? 'danger' : 'success' }} mb-0">৳ {{ number_format(abs($customer->current_balance ?? 0), 2) }}</h4>
                        <p class="text-muted small mb-0">Outstanding Balance</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="fw-bold mb-0">{{ $customer->payment_terms ?? 30 }}</h4>
                        <p class="text-muted small mb-0">Payment Terms (days)</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Recent Orders</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Order #</th><th>Date</th><th>Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($orders ?? [] as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->created_at?->format('d M Y') }}</td>
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
