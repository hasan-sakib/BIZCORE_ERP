@extends('layouts.app')
@section('title', 'Sales Orders')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Sales Orders</h5>
    <a href="{{ route('sales-orders.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>New Order</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Order #</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td><a href="{{ route('sales-orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->order_number }}</a></td>
                        <td>{{ $order->customer?->name ?? '—' }}</td>
                        <td>{{ $order->order_date?->format('d M Y') }}</td>
                        <td>৳ {{ number_format($order->total_amount ?? 0, 2) }}</td>
                        <td>
                            @php $colors = ['pending'=>'warning','confirmed'=>'info','processing'=>'primary','shipped'=>'secondary','delivered'=>'success','cancelled'=>'danger']; @endphp
                            <span class="badge bg-{{ $colors[$order->status] ?? 'secondary' }}-subtle text-{{ $colors[$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('sales-orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('sales-orders.edit', $order) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orders->hasPages())
    <div class="card-footer">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
