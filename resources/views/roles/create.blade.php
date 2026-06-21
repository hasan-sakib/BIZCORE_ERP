@extends('layouts.app')
@section('title', 'Add Role')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('roles.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Add Role</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Permissions</label>
                <div class="alert alert-info small py-2">Define comma-separated permission strings, e.g. <code>products.view, products.create</code>. Use <code>*</code> for super admin.</div>
                <textarea name="permissions" class="form-control font-monospace @error('permissions') is-invalid @enderror" rows="6" placeholder="products.view&#10;products.create&#10;inventory.view">{{ old('permissions') }}</textarea>
                @error('permissions')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
