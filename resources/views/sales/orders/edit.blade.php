@extends('layouts.app')
@section('title', 'Edit Sales Order')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('sales-orders.show', $order) }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Order #{{ $order->order_number }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:600px">
    <div class="card-body">
        <form method="POST" action="{{ route('sales-orders.update', $order) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Customer</label>
                <input type="text" class="form-control" value="{{ $order->customer?->name }}" readonly>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Order Date</label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date', $order->order_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Delivery Date</label>
                    <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', $order->delivery_date?->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    @foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $order->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $order->notes) }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('sales-orders.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
