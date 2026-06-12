<?php
$pageTitle = $pageTitle ?? $user->name;
ob_start();

$statusClass = match ($user->status->value) {
    'active'   => 'bg-success',
    'inactive' => 'bg-secondary',
    'locked'   => 'bg-danger',
    default    => 'bg-secondary',
};
?>

<div class="row g-4">

    <!-- Profile Card -->
    <div class="col-12 col-lg-4">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="avatar-lg mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-circle fs-3 fw-bold"
                     style="width:80px;height:80px;">
                    <?= strtoupper(substr($user->name, 0, 2)) ?>
                </div>
                <h5 class="mb-1"><?= sanitize($user->name) ?></h5>
                <p class="text-muted mb-2"><?= sanitize($user->email) ?></p>
                <span class="badge <?= $statusClass ?> fs-6 mb-3">
                    <?= sanitize($user->status->label()) ?>
                </span>

                <!-- Quick Actions -->
                <div class="d-grid gap-2 mt-3">
                    <a href="/users/<?= (int) $user->id ?>/edit" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit Profile
                    </a>

                    <?php if ($user->status->value !== 'active'): ?>
                        <form method="POST" action="/users/<?= (int) $user->id ?>/toggle-status">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="active">
                            <button type="submit" class="btn btn-outline-success btn-sm w-100">
                                <i class="fas fa-check-circle me-1"></i>Activate
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($user->status->value === 'active'): ?>
                        <form method="POST" action="/users/<?= (int) $user->id ?>/toggle-status">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="inactive">
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fas fa-ban me-1"></i>Deactivate
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($user->status->value !== 'locked'): ?>
                        <form method="POST" action="/users/<?= (int) $user->id ?>/toggle-status">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="locked">
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="fas fa-lock me-1"></i>Lock Account
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" action="/users/<?= (int) $user->id ?>/reset-password">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                            <i class="fas fa-key me-1"></i>Reset Password
                        </button>
                    </form>

                    <hr class="my-1">

                    <button type="button"
                            class="btn btn-outline-danger btn-sm"
                            onclick="confirmDelete('/users/<?= (int) $user->id ?>')">
                        <i class="fas fa-trash me-1"></i>Delete User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Card -->
    <div class="col-12 col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Account Details</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">User ID</dt>
                    <dd class="col-sm-8"><?= (int) $user->id ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Full Name</dt>
                    <dd class="col-sm-8"><?= sanitize($user->name) ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Email</dt>
                    <dd class="col-sm-8">
                        <a href="mailto:<?= sanitize($user->email) ?>"><?= sanitize($user->email) ?></a>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Phone</dt>
                    <dd class="col-sm-8"><?= $user->phone ? sanitize($user->phone) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge <?= $statusClass ?>"><?= sanitize($user->status->label()) ?></span>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Role ID</dt>
                    <dd class="col-sm-8">
                        <a href="/roles/<?= (int) $user->roleId ?>">#<?= (int) $user->roleId ?></a>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Branch ID</dt>
                    <dd class="col-sm-8">
                        <a href="/branches/<?= (int) $user->branchId ?>">#<?= (int) $user->branchId ?></a>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Last Login</dt>
                    <dd class="col-sm-8">
                        <?= $user->lastLoginAt
                            ? sanitize($user->lastLoginAt->format('d M Y, H:i'))
                            : '<span class="text-muted">Never</span>' ?>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Failed Logins</dt>
                    <dd class="col-sm-8">
                        <?php if ($user->failedLoginAttempts > 0): ?>
                            <span class="badge bg-warning text-dark"><?= (int) $user->failedLoginAttempts ?></span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </dd>

                    <?php if ($user->lockedUntil): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Locked Until</dt>
                        <dd class="col-sm-8 text-danger">
                            <?= sanitize($user->lockedUntil->format('d M Y, H:i')) ?>
                        </dd>
                    <?php endif; ?>

                    <dt class="col-sm-4 text-muted fw-normal">Created</dt>
                    <dd class="col-sm-8"><?= sanitize($user->createdAt->format('d M Y, H:i')) ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Last Updated</dt>
                    <dd class="col-sm-8"><?= sanitize($user->updatedAt->format('d M Y, H:i')) ?></dd>
                </dl>
            </div>
        </div>

        <!-- Back link -->
        <a href="/users" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back to Users
        </a>
    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
