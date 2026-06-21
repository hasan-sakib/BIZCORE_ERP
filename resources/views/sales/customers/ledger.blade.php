@extends('layouts.app')
@section('title', 'Customer Ledger')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('customers.show', $customer) }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Ledger — {{ $customer->name }}</h5>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr>
                </thead>
                <tbody>
                    @forelse($ledger ?? [] as $entry)
                    <tr>
                        <td>{{ $entry['date'] }}</td>
                        <td>{{ $entry['description'] }}</td>
                        <td>{{ $entry['debit'] ? '৳ '.number_format($entry['debit'],2) : '' }}</td>
                        <td>{{ $entry['credit'] ? '৳ '.number_format($entry['credit'],2) : '' }}</td>
                        <td class="{{ ($entry['balance'] ?? 0) >= 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                            ৳ {{ number_format(abs($entry['balance'] ?? 0), 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
