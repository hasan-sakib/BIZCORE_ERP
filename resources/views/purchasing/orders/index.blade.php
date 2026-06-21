@extends('layouts.app')
@section('title', 'Purchase Orders')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Purchase Orders</h5>
    <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>New PO</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>PO #</th><th>Supplier</th><th>Date</th><th>Expected</th><th>Total</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td><a href="{{ route('purchase-orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->po_number }}</a></td>
                        <td>{{ $order->supplier?->name ?? '—' }}</td>
                        <td>{{ $order->order_date?->format('d M Y') }}</td>
                        <td>{{ $order->expected_date?->format('d M Y') ?? '—' }}</td>
                        <td>৳ {{ number_format($order->total_amount ?? 0, 2) }}</td>
                        <td>
                            @php $statusColors = ['draft'=>'secondary','submitted'=>'warning','approved'=>'info','received'=>'success','cancelled'=>'danger']; @endphp
                            <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @if($order->status === 'submitted')
                            <form method="POST" action="{{ route('purchase-orders.approve', $order) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No purchase orders found.</td></tr>
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
