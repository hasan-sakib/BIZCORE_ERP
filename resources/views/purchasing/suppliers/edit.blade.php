@extends('layouts.app')
@section('title', 'Edit Supplier')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('suppliers.show', $supplier) }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit — {{ $supplier->name }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $supplier->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Tax ID</label>
                    <input type="text" name="tax_id" class="form-control" value="{{ old('tax_id', $supplier->tax_id) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Payment Terms (days)</label>
                    <input type="number" name="payment_terms" class="form-control" value="{{ old('payment_terms', $supplier->payment_terms) }}" min="0">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="active" @selected(old('status', $supplier->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
