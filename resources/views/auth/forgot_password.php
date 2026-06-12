<?php
$layout = 'auth';
$pageTitle = 'Forgot Password';
ob_start();
?>

<h5 class="fw-bold mb-1 text-center">Forgot your password?</h5>
<p class="text-muted small text-center mb-4">Enter your email and we'll send you a reset link.</p>

<?php if ($session->hasFlash('success')): ?>
    <div class="alert alert-success d-flex align-items-center mb-3">
        <i class="fas fa-check-circle me-2"></i>
        <?= sanitize($session->getFlash('success')) ?>
    </div>
<?php endif; ?>

<?php if ($session->hasFlash('error')): ?>
    <div class="alert alert-danger d-flex align-items-center mb-3">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= sanitize($session->getFlash('error')) ?>
    </div>
<?php endif; ?>

<form action="/auth/forgot-password" method="POST">
    <?= csrf_field() ?>

    <div class="mb-4">
        <label class="form-label fw-semibold small text-muted">Email Address</label>
        <div class="input-icon">
            <i class="fas fa-envelope"></i>
            <input
                type="email"
                name="email"
                class="form-control <?= $errors['email'] ?? false ? 'is-invalid' : '' ?>"
                value="<?= sanitize(old('email')) ?>"
                placeholder="you@company.com"
                autocomplete="email"
                autofocus
                required
            >
            <?php if ($errors['email'] ?? false): ?>
                <div class="invalid-feedback"><?= sanitize($errors['email']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-auth w-100 text-white mb-3">
        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
    </button>

    <div class="text-center">
        <a href="/auth/login" class="text-muted small text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i>Back to login
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
