@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Expenses</h5>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Log Expense</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Title</th><th>Category</th><th>Amount</th><th>Date</th><th>Submitted By</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td class="fw-semibold">{{ $expense->title }}</td>
                        <td>
                            @if($expense->category)
                            <span class="badge" style="background:{{ $expense->category->color ?? '#6c757d' }}">{{ $expense->category->name }}</span>
                            @else —
                            @endif
                        </td>
                        <td>৳ {{ number_format($expense->amount ?? 0, 2) }}</td>
                        <td>{{ $expense->expense_date?->format('d M Y') }}</td>
                        <td>{{ $expense->user?->name ?? '—' }}</td>
                        <td>
                            @php $statusColors = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','paid'=>'info']; @endphp
                            <span class="badge bg-{{ $statusColors[$expense->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$expense->status] ?? 'secondary' }}">{{ ucfirst($expense->status) }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('expenses.show', $expense) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No expenses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($expenses->hasPages())
    <div class="card-footer">{{ $expenses->links() }}</div>
    @endif
</div>
@endsection
