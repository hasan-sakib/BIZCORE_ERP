<?php
$pageTitle = 'My Profile';
ob_start();

$statusClass = match ($user->status->value) {
    'active'   => 'bg-success',
    'inactive' => 'bg-secondary',
    'locked'   => 'bg-danger',
    default    => 'bg-secondary',
};
$avatarUrl = $user->avatarUrl();
?>

<style>
.avatar-wrapper { position: relative; width: 100px; height: 100px; margin: 0 auto 1rem; }
.avatar-wrapper img,
.avatar-wrapper .avatar-initials {
    width: 100px; height: 100px; border-radius: 50%; object-fit: cover;
}
.avatar-initials {
    display: flex; align-items: center; justify-content: center;
    background: var(--bs-primary); color: #fff; font-size: 2rem; font-weight: 700;
}
.avatar-overlay {
    position: absolute; inset: 0; border-radius: 50%;
    background: rgba(0,0,0,.45); display: flex; align-items: center;
    justify-content: center; opacity: 0; transition: opacity .2s; cursor: pointer;
}
.avatar-wrapper:hover .avatar-overlay { opacity: 1; }
</style>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm text-center">
            <div class="card-body py-4">

                <!-- Avatar with click-to-upload -->
                <form method="POST" action="/profile/avatar" enctype="multipart/form-data" id="avatarForm">
                    <?= csrf_field() ?>
                    <div class="avatar-wrapper" onclick="document.getElementById('avatarInput').click()" title="Click to change photo">
                        <?php if ($user->avatar): ?>
                            <img src="<?= sanitize($avatarUrl) ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-initials"><?= strtoupper(substr($user->name, 0, 2)) ?></div>
                        <?php endif; ?>
                        <div class="avatar-overlay text-white">
                            <i class="fas fa-camera fa-lg"></i>
                        </div>
                    </div>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" class="d-none">
                </form>

                <h5 class="mb-1"><?= sanitize($user->name) ?></h5>
                <p class="text-muted mb-2 small"><?= sanitize($user->email) ?></p>
                <span class="badge <?= $statusClass ?> mb-3"><?= sanitize($user->status->label()) ?></span>
                <div class="d-grid gap-2 mt-3">
                    <a href="/profile/edit" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit Profile
                    </a>
                </div>
                <p class="text-muted small mt-2 mb-0">Click photo to upload a new one</p>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="col-12 col-lg-8">
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

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-user me-2 text-primary"></i>Account Details</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Full Name</dt>
                    <dd class="col-sm-8"><?= sanitize($user->name) ?></dd>

                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8"><?= sanitize($user->email) ?></dd>

                    <dt class="col-sm-4 text-muted">Phone</dt>
                    <dd class="col-sm-8"><?= sanitize($user->phone ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8"><span class="badge <?= $statusClass ?>"><?= sanitize($user->status->label()) ?></span></dd>

                    <dt class="col-sm-4 text-muted">Member Since</dt>
                    <dd class="col-sm-8"><?= $user->createdAt->format('d M Y') ?></dd>

                    <?php if ($user->lastLoginAt): ?>
                        <dt class="col-sm-4 text-muted">Last Login</dt>
                        <dd class="col-sm-8"><?= $user->lastLoginAt->format('d M Y, H:i') ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('avatarInput').addEventListener('change', function () {
    if (this.files.length > 0) {
        document.getElementById('avatarForm').submit();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
