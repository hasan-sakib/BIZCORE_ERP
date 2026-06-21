@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Suppliers</h5>
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Supplier</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Phone</th><th>Email</th><th>Balance</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td class="fw-semibold">{{ $supplier->name }}</td>
                        <td>{{ $supplier->phone ?? '—' }}</td>
                        <td>{{ $supplier->email ?? '—' }}</td>
                        <td>৳ {{ number_format($supplier->current_balance ?? 0, 2) }}</td>
                        <td><span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($supplier->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($suppliers->hasPages())
    <div class="card-footer">{{ $suppliers->links() }}</div>
    @endif
</div>
@endsection
