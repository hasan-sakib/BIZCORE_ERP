@extends('layouts.app')
@section('title', 'Edit Branch')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('branches.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Branch — {{ $branch->name }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ route('branches.update', $branch) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $branch->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $branch->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $branch->address) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $branch->email) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $branch->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $branch->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="is_head" value="1" id="is_head" class="form-check-input" @checked(old('is_head', $branch->is_head))>
                        <label for="is_head" class="form-check-label">Head Office</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
