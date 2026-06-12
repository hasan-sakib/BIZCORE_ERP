<?php
$layout    = 'auth';
$pageTitle = 'Complete Registration';
ob_start();
?>

<h5 class="fw-bold mb-2 text-center">Complete your profile</h5>
<p class="text-center text-muted small mb-4">Almost there! Select your role and branch to finish signing up with Google.</p>

<?php if ($session->hasFlash('error')): ?>
    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= sanitize($session->getFlash('error')) ?>
    </div>
<?php endif; ?>

<form action="/auth/complete-profile" method="POST" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Full Name</label>
        <input type="text" class="form-control bg-light" value="<?= sanitize($oauthData['name'] ?? '') ?>" readonly>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Email Address</label>
        <input type="email" class="form-control bg-light" value="<?= sanitize($oauthData['email'] ?? '') ?>" readonly>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Role</label>
        <select name="role_id" class="form-select <?= !empty($errors['role_id']) ? 'is-invalid' : '' ?>">
            <option value="">— Select a role —</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id'] ?>"><?= sanitize($role['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['role_id'])): ?>
            <div class="invalid-feedback"><?= sanitize($errors['role_id']) ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold small text-muted">Branch</label>
        <select name="branch_id" class="form-select <?= !empty($errors['branch_id']) ? 'is-invalid' : '' ?>">
            <option value="">— Select a branch —</option>
            <?php foreach ($branches as $branch): ?>
                <option value="<?= (int) $branch['id'] ?>"><?= sanitize($branch['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['branch_id'])): ?>
            <div class="invalid-feedback"><?= sanitize($errors['branch_id']) ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-auth w-100 text-white mb-3">
        <i class="fas fa-check me-2"></i>Complete Registration
    </button>

    <div class="text-center">
        <small class="text-muted"><a href="/login" class="text-primary text-decoration-none">← Back to Sign In</a></small>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
