@extends('layouts.app')
@section('title', 'Edit Invoice')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('invoices.show', $invoice) }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Invoice #{{ $invoice->invoice_number }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('invoices.update', $invoice) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Customer</label>
                <input type="text" class="form-control" value="{{ $invoice->customer?->name }}" readonly>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Invoice Date</label>
                    <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    @foreach(['draft','sent','partial','paid','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $invoice->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $invoice->notes) }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
