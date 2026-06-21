@extends('layouts.app')
@section('title', 'Receive Goods')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('purchase-orders.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Receive Goods</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ route('goods-receipt.store') }}">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ request('purchase_order_id') }}">
            <div class="mb-3">
                <label class="form-label fw-semibold">Purchase Order <span class="text-danger">*</span></label>
                <select name="purchase_order_id" class="form-select" required>
                    <option value="">Select PO</option>
                    @foreach($purchaseOrders as $po)
                    <option value="{{ $po->id }}" @selected(request('purchase_order_id') == $po->id)>{{ $po->po_number }} — {{ $po->supplier?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Receive Date <span class="text-danger">*</span></label>
                <input type="date" name="receive_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Warehouse <span class="text-danger">*</span></label>
                <select name="warehouse_id" class="form-select" required>
                    @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" @selected($wh->is_default)>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Reference No.</label>
                <input type="text" name="reference" class="form-control" placeholder="Delivery challan / bill number">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Confirm Receipt</button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
