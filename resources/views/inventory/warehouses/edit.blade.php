@extends('layouts.app')
@section('title', 'Edit Warehouse')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('warehouses.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Warehouse — {{ $warehouse->name }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('warehouses.update', $warehouse) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $warehouse->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location', $warehouse->location) }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $warehouse->description) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Capacity</label>
                    <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $warehouse->capacity) }}" min="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $warehouse->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $warehouse->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mb-4 form-check">
                <input type="checkbox" name="is_default" value="1" id="is_default" class="form-check-input" @checked(old('is_default', $warehouse->is_default))>
                <label for="is_default" class="form-check-label">Set as default warehouse</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
