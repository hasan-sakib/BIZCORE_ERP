@extends('layouts.app')
@section('title', 'Customers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Customers</h5>
    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Customer</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Phone</th><th>Email</th><th>Credit Limit</th><th>Balance</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td class="fw-semibold">{{ $customer->name }}</td>
                        <td>{{ $customer->phone ?? '—' }}</td>
                        <td>{{ $customer->email ?? '—' }}</td>
                        <td>৳ {{ number_format($customer->credit_limit ?? 0, 2) }}</td>
                        <td class="{{ ($customer->current_balance ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                            ৳ {{ number_format(abs($customer->current_balance ?? 0), 2) }}
                        </td>
                        <td><span class="badge bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($customer->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($customers->hasPages())
    <div class="card-footer">{{ $customers->links() }}</div>
    @endif
</div>
@endsection
