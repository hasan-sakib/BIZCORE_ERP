<?php
$pageTitle = $pageTitle ?? $role->name;
ob_start();
?>

<div class="row g-4">

    <!-- Role Info -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>Role Details</h6>
                <?php if (!$role->isSystem): ?>
                    <a href="/roles/<?= (int) $role->id ?>/edit" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">ID</dt>
                    <dd class="col-7"><?= (int) $role->id ?></dd>

                    <dt class="col-5 text-muted fw-normal">Name</dt>
                    <dd class="col-7 fw-medium"><?= sanitize($role->name) ?></dd>

                    <dt class="col-5 text-muted fw-normal">Slug</dt>
                    <dd class="col-7"><code class="small"><?= sanitize($role->slug) ?></code></dd>

                    <dt class="col-5 text-muted fw-normal">System</dt>
                    <dd class="col-7">
                        <?php if ($role->isSystem): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-lock me-1"></i>Yes
                            </span>
                        <?php else: ?>
                            <span class="text-muted">No</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Description</dt>
                    <dd class="col-7 text-muted small">
                        <?= $role->description ? sanitize($role->description) : '<em>No description</em>' ?>
                    </dd>
                </dl>
            </div>
            <?php if (!$role->isSystem): ?>
                <div class="card-footer d-flex gap-2">
                    <a href="/roles/<?= (int) $role->id ?>/edit" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit Role
                    </a>
                    <button type="button"
                            class="btn btn-outline-danger btn-sm"
                            onclick="confirmDelete('/roles/<?= (int) $role->id ?>')">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-3">
            <a href="/roles" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to Roles
            </a>
        </div>
    </div>

    <!-- Permissions -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0">
                    <i class="fas fa-key me-2 text-primary"></i>
                    Permissions
                    <span class="badge bg-secondary ms-1"><?= count($role->permissions) ?></span>
                </h6>
                <?php if (!$role->isSystem): ?>
                    <a href="/roles/<?= (int) $role->id ?>/edit" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-cog me-1"></i>Manage
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($allPermissions)): ?>
                    <?php
                    // Build a flat set of this role's active permission IDs or slugs for quick lookup.
                    $activePerms = [];
                    foreach ($role->permissions as $p) {
                        $activePerms[] = is_array($p) ? ($p['id'] ?? $p['slug'] ?? '') : $p;
                    }
                    $activePerms = array_map('strval', $activePerms);
                    ?>
                    <?php foreach ($allPermissions as $group => $permissions): ?>
                        <div class="mb-4">
                            <div class="fw-medium text-muted small text-uppercase mb-2 border-bottom pb-1">
                                <?= sanitize($group) ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($permissions as $perm): ?>
                                    <?php
                                    $permId   = is_array($perm) ? ($perm['id']   ?? '') : $perm;
                                    $permName = is_array($perm) ? ($perm['name'] ?? $perm['slug'] ?? $permId) : $perm;
                                    $active   = in_array((string) $permId, $activePerms, true);
                                    ?>
                                    <span class="badge <?= $active ? 'bg-success' : 'bg-light text-muted border' ?>">
                                        <?php if ($active): ?>
                                            <i class="fas fa-check me-1"></i>
                                        <?php endif; ?>
                                        <?= sanitize((string) $permName) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (empty($role->permissions)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-key fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0 small">No permissions assigned to this role.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($role->permissions as $perm): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check me-1"></i>
                                <?= sanitize(is_array($perm) ? ($perm['name'] ?? $perm['slug'] ?? '') : $perm) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
