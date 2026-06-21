@extends('layouts.app')
@section('title', 'Users')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Users</h5>
    @can('users.create')
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Add User
    </a>
    @endcan
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($user->avatar)
                                    <img src="{{ asset('storage/'.$user->avatar) }}" class="rounded-circle" width="32" height="32" style="object-fit:cover">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:12px">
                                        {{ strtoupper(substr($user->name,0,1)) }}
                                    </div>
                                @endif
                                {{ $user->name }}
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role?->name ?? '—' }}</td>
                        <td>{{ $user->branch?->name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $user->status->value === 'active' ? 'success' : ($user->status->value === 'locked' ? 'danger' : 'secondary') }}-subtle text-{{ $user->status->value === 'active' ? 'success' : ($user->status->value === 'locked' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($user->status->value) }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="text-end">
                            <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('users.edit')
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer">{{ $users->links() }}</div>
    @endif
</div>
@endsection
