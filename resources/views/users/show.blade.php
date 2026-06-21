@extends('layouts.app')
@section('title', $user->name)
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('users.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">{{ $user->name }}</h5>
    @can('users.edit')
    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary ms-auto">Edit</a>
    @endcan
</div>
@include('components.flash-messages')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-4">
                @if($user->avatar)
                <img src="{{ asset('storage/'.$user->avatar) }}" class="rounded-circle mb-3" width="80" height="80" style="object-fit:cover">
                @else
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width:80px;height:80px;font-size:28px">
                    {{ strtoupper(substr($user->name,0,1)) }}
                </div>
                @endif
                <h6 class="fw-semibold mb-1">{{ $user->name }}</h6>
                <p class="text-muted small mb-2">{{ $user->email }}</p>
                <span class="badge bg-{{ $user->status->value === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $user->status->value === 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($user->status->value) }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Account Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8">{{ $user->role?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Branch</dt>
                    <dd class="col-sm-8">{{ $user->branch?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8">{{ $user->phone ?? '—' }}</dd>
                    <dt class="col-sm-4">Last Login</dt>
                    <dd class="col-sm-8">{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Never' }}</dd>
                    <dt class="col-sm-4">Email Verified</dt>
                    <dd class="col-sm-8">{{ $user->email_verified_at ? 'Yes' : 'No' }}</dd>
                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">{{ $user->created_at->format('d M Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
