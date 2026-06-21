@extends('layouts.auth')

@section('title', 'Two-Factor Verification')

@section('content')
    <h5 class="fw-bold mb-1">Two-factor authentication</h5>
    <p class="text-muted small mb-4">Enter the 6-digit code from your authenticator app.</p>

    @include('components.flash-messages')

    <form method="POST" action="{{ route('mfa.verify') }}">
        @csrf
        <div class="mb-4">
            <label class="form-label fw-semibold">Authentication Code</label>
            <input type="text" name="code" class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
                   maxlength="6" placeholder="000000" autofocus autocomplete="one-time-code">
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Verify</button>
    </form>
@endsection
