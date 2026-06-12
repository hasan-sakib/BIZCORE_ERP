<?php
$pageTitle = 'Login';
ob_start();
?>

<div class="text-center mb-4">
    <i class="fas fa-cubes fa-3x text-primary mb-3"></i>
    <h2 class="fw-bold fs-4 mb-1">Sign in to your account</h2>
    <p class="text-muted small mb-0">Enter your credentials to access BizCore ERP</p>
</div>

<?php if ($session->hasFlash('error')): ?>
    <div class="alert alert-danger d-flex align-items-center mb-3 py-2" role="alert">
        <i class="fas fa-exclamation-circle me-2 flex-shrink-0"></i>
        <span><?= sanitize($session->getFlash('error')) ?></span>
    </div>
<?php endif; ?>

<?php if ($session->hasFlash('success')): ?>
    <div class="alert alert-success d-flex align-items-center mb-3 py-2" role="alert">
        <i class="fas fa-check-circle me-2 flex-shrink-0"></i>
        <span><?= sanitize($session->getFlash('success')) ?></span>
    </div>
<?php endif; ?>

<form action="/login" method="POST" novalidate>
    <?= csrf_field() ?>

    <!-- Email -->
    <div class="mb-3">
        <label for="email" class="form-label fw-medium small">Email Address</label>
        <div class="input-group <?= ($errors['email'] ?? false) ? 'is-invalid' : '' ?>">
            <span class="input-group-text"><i class="fas fa-envelope fa-sm"></i></span>
            <input
                type="email"
                name="email"
                id="email"
                class="form-control <?= ($errors['email'] ?? false) ? 'is-invalid' : '' ?>"
                value="<?= sanitize(old('email')) ?>"
                placeholder="you@company.com"
                autocomplete="email"
                autofocus
                required
            >
        </div>
        <?php if ($errors['email'] ?? false): ?>
            <div class="invalid-feedback d-block"><?= sanitize($errors['email']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Password -->
    <div class="mb-3">
        <label for="passwordInput" class="form-label fw-medium small">Password</label>
        <div class="input-group <?= ($errors['password'] ?? false) ? 'is-invalid' : '' ?>">
            <span class="input-group-text"><i class="fas fa-lock fa-sm"></i></span>
            <input
                type="password"
                name="password"
                id="passwordInput"
                class="form-control <?= ($errors['password'] ?? false) ? 'is-invalid' : '' ?>"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
            >
            <span class="input-group-text password-toggle" id="togglePassword" role="button" aria-label="Toggle password visibility">
                <i class="fas fa-eye fa-sm" id="toggleIcon"></i>
            </span>
        </div>
        <?php if ($errors['password'] ?? false): ?>
            <div class="invalid-feedback d-block"><?= sanitize($errors['password']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Remember me + forgot password -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"
                   <?= old('remember') ? 'checked' : '' ?>>
            <label class="form-check-label small text-muted" for="remember">Remember me</label>
        </div>
        <a href="/forgot-password" class="text-primary small text-decoration-none">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fas fa-sign-in-alt me-2"></i>Sign In
    </button>

    <div class="text-center mb-3">
        <small class="text-muted">Don't have an account?
            <a href="/register" class="text-primary text-decoration-none fw-medium">Create one →</a>
        </small>
    </div>
</form>

<!-- Divider + Google OAuth -->
<div class="auth-divider"><span>Or continue with</span></div>

<a href="/auth/google" class="btn btn-google w-100 d-flex align-items-center justify-content-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
    </svg>
    Continue with Google
</a>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('togglePassword');
    var input  = document.getElementById('passwordInput');
    var icon   = document.getElementById('toggleIcon');

    toggle.addEventListener('click', function () {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('fa-eye',      !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
