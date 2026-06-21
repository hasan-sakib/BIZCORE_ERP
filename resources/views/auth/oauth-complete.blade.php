@extends('layouts.auth')

@section('title', 'Complete Your Profile')

@section('content')
    <h5 class="fw-bold mb-1">Complete your profile</h5>
    <p class="text-muted small mb-4">Choose your role and branch to finish signing up with Google.</p>

    @include('components.flash-messages')

    <form method="POST" action="{{ route('oauth.complete.submit') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-semibold">Name</label>
            <input type="text" class="form-control" value="{{ $oauthData['name'] }}" readonly disabled>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" class="form-control" value="{{ $oauthData['email'] }}" readonly disabled>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="role_id">Role</label>
            <select name="role_id" id="role_id"
                    class="form-select @error('role_id') is-invalid @enderror" required>
                <option value="">— Select a role —</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
            @error('role_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold" for="branch_id">Branch</label>
            <select name="branch_id" id="branch_id"
                    class="form-select @error('branch_id') is-invalid @enderror" required>
                <option value="">— Select a branch —</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            Create Account
        </button>
    </form>

    <p class="text-center small mt-3 text-muted">
        <a href="{{ route('login') }}" class="text-decoration-none">Back to sign in</a>
    </p>
@endsection
