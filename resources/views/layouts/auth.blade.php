<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BizCore ERP') — BizCore ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { width: 100%; max-width: 440px; }
        .auth-card .card { border: none; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.10); }
        .auth-card .card-body { padding: 2.5rem; }
        .auth-logo { font-size: 1.6rem; font-weight: 700; color: #2563eb; letter-spacing: -0.5px; }
        .auth-logo span { color: #374151; }
        .btn-primary { background: #2563eb; border-color: #2563eb; }
        .btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 .2rem rgba(37,99,235,.15); }
    </style>
    @stack('styles')
</head>
<body>
<div class="auth-card">
    <div class="text-center mb-4">
        <div class="auth-logo"><i class="fa-solid fa-building-columns me-2"></i>BizCore<span> ERP</span></div>
    </div>
    <div class="card">
        <div class="card-body">
            @yield('content')
        </div>
    </div>
    <p class="text-center text-muted small mt-3">&copy; {{ date('Y') }} BizCore ERP. All rights reserved.</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
