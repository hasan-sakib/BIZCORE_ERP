<?php
$layout    = 'auth';
$pageTitle = 'Create Account';
ob_start();
?>

<h5 class="fw-bold mb-4 text-center">Create your account</h5>

<?php if ($session->hasFlash('error')): ?>
    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= sanitize($session->getFlash('error')) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors['form'] ?? [])): ?>
    <div class="alert alert-danger mb-3" role="alert">
        <?php foreach ((array)$errors['form'] as $msg): ?>
            <div><i class="fas fa-exclamation-circle me-1"></i><?= sanitize($msg) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<a href="/auth/google" class="btn btn-outline-secondary w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
    </svg>
    Sign up with Google
</a>

<div class="d-flex align-items-center mb-3">
    <hr class="flex-grow-1"><span class="mx-2 text-muted small">or</span><hr class="flex-grow-1">
</div>

<form action="/register" method="POST" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Full Name</label>
        <div class="input-icon">
            <i class="fas fa-user"></i>
            <input
                type="text"
                name="name"
                class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                value="<?= sanitize(old('name')) ?>"
                placeholder="Your full name"
                autocomplete="name"
                required
            >
            <?php if (!empty($errors['name'])): ?>
                <div class="invalid-feedback"><?= sanitize($errors['name']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Email Address</label>
        <div class="input-icon">
            <i class="fas fa-envelope"></i>
            <input
                type="email"
                name="email"
                class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                value="<?= sanitize(old('email')) ?>"
                placeholder="you@company.com"
                autocomplete="email"
                required
            >
            <?php if (!empty($errors['email'])): ?>
                <div class="invalid-feedback"><?= sanitize($errors['email']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Role</label>
        <select name="role_id" class="form-select <?= !empty($errors['role_id']) ? 'is-invalid' : '' ?>">
            <option value="">— Select a role —</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id'] ?>" <?= (string) old('role_id') === (string) $role['id'] ? 'selected' : '' ?>>
                    <?= sanitize($role['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['role_id'])): ?>
            <div class="invalid-feedback"><?= sanitize($errors['role_id']) ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Branch</label>
        <select name="branch_id" class="form-select <?= !empty($errors['branch_id']) ? 'is-invalid' : '' ?>">
            <option value="">— Select a branch —</option>
            <?php foreach ($branches as $branch): ?>
                <option value="<?= (int) $branch['id'] ?>" <?= (string) old('branch_id') === (string) $branch['id'] ? 'selected' : '' ?>>
                    <?= sanitize($branch['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['branch_id'])): ?>
            <div class="invalid-feedback"><?= sanitize($errors['branch_id']) ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Password</label>
        <div class="input-icon">
            <i class="fas fa-lock"></i>
            <input
                type="password"
                name="password"
                class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                placeholder="Min. 8 characters"
                autocomplete="new-password"
                id="passwordInput"
                required
            >
            <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y pe-3 border-0 bg-transparent text-muted" onclick="togglePassword('passwordInput','eyeIcon')">
                <i class="fas fa-eye" id="eyeIcon"></i>
            </button>
            <?php if (!empty($errors['password'])): ?>
                <div class="invalid-feedback"><?= sanitize($errors['password']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold small text-muted">Confirm Password</label>
        <div class="input-icon">
            <i class="fas fa-lock"></i>
            <input
                type="password"
                name="confirm_password"
                class="form-control <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                placeholder="Repeat your password"
                autocomplete="new-password"
                id="confirmPasswordInput"
                required
            >
            <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y pe-3 border-0 bg-transparent text-muted" onclick="togglePassword('confirmPasswordInput','eyeIcon2')">
                <i class="fas fa-eye" id="eyeIcon2"></i>
            </button>
            <?php if (!empty($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?= sanitize($errors['confirm_password']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-auth w-100 text-white mb-3">
        <i class="fas fa-user-plus me-2"></i>Create Account
    </button>

    <div class="text-center">
        <small class="text-muted">Already have an account? <a href="/login" class="text-primary text-decoration-none">Sign in →</a></small>
    </div>
</form>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
