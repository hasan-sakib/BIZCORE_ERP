@extends('layouts.app')
@section('title', 'Edit Purchase Order')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('purchase-orders.show', $order) }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit PO #{{ $order->po_number }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('purchase-orders.update', $order) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Supplier</label>
                <input type="text" class="form-control" value="{{ $order->supplier?->name }}" readonly>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Order Date</label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date', $order->order_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Expected Date</label>
                    <input type="date" name="expected_date" class="form-control" value="{{ old('expected_date', $order->expected_date?->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    @foreach(['draft','submitted','approved','received','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $order->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $order->notes) }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
