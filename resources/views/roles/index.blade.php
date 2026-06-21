@extends('layouts.app')
@section('title', 'Roles')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Roles & Permissions</h5>
    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Add Role
    </a>
</div>
@include('components.flash-messages')
<div class="row g-3">
    @forelse($roles as $role)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-semibold mb-0">{{ $role->name }}</h6>
                    @if($role->is_system)
                    <span class="badge bg-secondary-subtle text-secondary">System</span>
                    @endif
                </div>
                @if($role->description)
                <p class="text-muted small mb-2">{{ $role->description }}</p>
                @endif
                <div class="text-muted small mb-3">
                    <i class="fa-solid fa-users me-1"></i>{{ $role->users_count ?? 0 }} users
                </div>
                @if($role->permissions && count($role->permissions) <= 6)
                <div class="d-flex flex-wrap gap-1">
                    @foreach(array_slice($role->permissions, 0, 6) as $perm)
                    <span class="badge bg-primary-subtle text-primary" style="font-size:.7rem">{{ $perm }}</span>
                    @endforeach
                    @if(count($role->permissions) > 6)
                    <span class="badge bg-light text-muted">+{{ count($role->permissions) - 6 }} more</span>
                    @endif
                </div>
                @elseif($role->permissions === ['*'])
                <span class="badge bg-warning-subtle text-warning">All Permissions</span>
                @endif
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                @unless($role->is_system)
                <form method="POST" action="{{ route('roles.destroy', $role) }}" class="ms-auto" onsubmit="return confirm('Delete this role?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
                @endunless
            </div>
        </div>
    </div>
    @empty
    <div class="col-12"><p class="text-muted">No roles found.</p></div>
    @endforelse
</div>
@endsection
