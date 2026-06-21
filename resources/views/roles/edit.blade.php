@extends('layouts.app')
@section('title', 'Edit Role')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('roles.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Role — {{ $role->name }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $role->name) }}" @if($role->is_system) readonly @endif required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $role->description) }}</textarea>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Permissions</label>
                <div class="alert alert-info small py-2">One permission per line, or use <code>*</code> for super admin access.</div>
                <textarea name="permissions" class="form-control font-monospace @error('permissions') is-invalid @enderror" rows="8">{{ old('permissions', is_array($role->permissions) ? implode("\n", $role->permissions) : '') }}</textarea>
                @error('permissions')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
