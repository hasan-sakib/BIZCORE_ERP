@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <h5 class="fw-bold mb-1">Reset your password</h5>
    <p class="text-muted small mb-4">Enter your email and we'll send a reset link.</p>

    @include('components.flash-messages')

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label class="form-label fw-semibold">Email address</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Send Reset Link</button>
    </form>
    <p class="text-center small mt-3 text-muted">
        <a href="{{ route('login') }}" class="text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i>Back to sign in</a>
    </p>
@endsection
