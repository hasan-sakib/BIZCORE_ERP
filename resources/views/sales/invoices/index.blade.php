@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Invoices</h5>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>New Invoice</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Invoice #</th><th>Customer</th><th>Date</th><th>Due Date</th><th>Total</th><th>Paid</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold text-decoration-none">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ $invoice->customer?->name ?? '—' }}</td>
                        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td class="{{ $invoice->due_date?->isPast() && !in_array($invoice->status, ['paid','cancelled']) ? 'text-danger fw-semibold' : '' }}">
                            {{ $invoice->due_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td>৳ {{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                        <td>৳ {{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                        <td>
                            @php $statusColors = ['draft'=>'secondary','sent'=>'primary','partial'=>'warning','paid'=>'success','cancelled'=>'danger']; @endphp
                            <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$invoice->status] ?? 'secondary' }}">{{ ucfirst($invoice->status) }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-dark" target="_blank"><i class="fa-solid fa-file-pdf"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection
