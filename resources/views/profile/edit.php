<?php
$pageTitle = 'Edit Profile';
ob_start();
$errors    = $errors ?? [];
$old       = $old ?? [];
$avatarUrl = $user->avatarUrl();
?>
<style>
.avatar-wrapper { position: relative; width: 90px; height: 90px; }
.avatar-wrapper img, .avatar-wrapper .avatar-initials {
    width: 90px; height: 90px; border-radius: 50%; object-fit: cover;
}
.avatar-initials {
    display: flex; align-items: center; justify-content: center;
    background: var(--bs-primary); color: #fff; font-size: 1.8rem; font-weight: 700;
}
.avatar-overlay {
    position: absolute; inset: 0; border-radius: 50%;
    background: rgba(0,0,0,.45); display: flex; align-items: center;
    justify-content: center; opacity: 0; transition: opacity .2s; cursor: pointer;
}
.avatar-wrapper:hover .avatar-overlay { opacity: 1; }
</style>

<?php if ($msg = session()->getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible">
        <?= sanitize($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg = session()->getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible">
        <?= sanitize($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Update Profile -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-user-edit me-2 text-primary"></i>Profile Information</h6>
            </div>
            <div class="card-body">
                <!-- Avatar upload -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <form method="POST" action="/profile/avatar" enctype="multipart/form-data" id="avatarForm">
                        <?= csrf_field() ?>
                        <div class="avatar-wrapper" onclick="document.getElementById('avatarInput').click()" title="Click to change photo">
                            <?php if ($user->avatar): ?>
                                <img src="<?= sanitize($avatarUrl) ?>" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar-initials"><?= strtoupper(substr($user->name, 0, 2)) ?></div>
                            <?php endif; ?>
                            <div class="avatar-overlay text-white"><i class="fas fa-camera"></i></div>
                        </div>
                        <input type="file" id="avatarInput" name="avatar" accept="image/*" class="d-none">
                    </form>
                    <div>
                        <div class="fw-semibold"><?= sanitize($user->name) ?></div>
                        <div class="text-muted small">JPEG, PNG, WebP or GIF · Max 2 MB</div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-1"
                                onclick="document.getElementById('avatarInput').click()">
                            <i class="fas fa-upload me-1"></i>Upload Photo
                        </button>
                    </div>
                </div>

                <form method="POST" action="/profile">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($old['name'] ?? $user->name) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($old['email'] ?? $user->email) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= sanitize($old['phone'] ?? $user->phone ?? '') ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                        <a href="/profile" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-lock me-2 text-warning"></i>Change Password</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/profile/password">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password"
                               class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" required>
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['current_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password"
                               class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                               minlength="8" required>
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['new_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password"
                               class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                               minlength="8" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('avatarInput').addEventListener('change', function () {
    if (this.files.length > 0) document.getElementById('avatarForm').submit();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
