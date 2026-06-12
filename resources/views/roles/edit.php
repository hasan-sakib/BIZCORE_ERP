<?php
$pageTitle = $pageTitle ?? 'Edit Role';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];

function roleEditVal(string $key, mixed $default, array $old, object $role): mixed
{
    if (isset($old[$key])) {
        return $old[$key];
    }
    return match ($key) {
        'name'        => $role->name,
        'description' => $role->description,
        default       => $default,
    };
}
?>

<div class="row g-4">

    <!-- Edit Form -->
    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="fas fa-edit me-2 text-primary"></i>Edit Role</h6>
                <a href="/roles/<?= (int) $role->id ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body">

                <?php if ($role->isSystem): ?>
                    <div class="alert alert-warning d-flex gap-2">
                        <i class="fas fa-lock mt-1 flex-shrink-0"></i>
                        <div><strong>System Role:</strong> The name and description are read-only. Permissions are managed in code.</div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($errors as $field => $messages): ?>
                                <?php foreach ((array) $messages as $msg): ?>
                                    <li><?= sanitize($msg) ?></li>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/roles/<?= (int) $role->id ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize(roleEditVal('name', '', $old, $role)) ?>"
                                required
                                autofocus
                                <?= $role->isSystem ? 'readonly' : '' ?>
                            >
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['name'])) ?></div>
                            <?php else: ?>
                                <div class="form-text">Slug: <code><?= sanitize($role->slug) ?></code></div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                rows="3"
                                <?= $role->isSystem ? 'readonly' : '' ?>
                            ><?= sanitize(roleEditVal('description', '', $old, $role)) ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['description'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$role->isSystem): ?>
                        <hr class="my-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="/roles/<?= (int) $role->id ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Permissions Panel -->
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-key me-2 text-primary"></i>Permissions</h6>
            </div>
            <div class="card-body">

                <?php if ($role->isSystem): ?>
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Permissions for system roles are defined in code and cannot be changed via the UI.
                    </div>
                    <div class="mt-3">
                        <?php foreach ($role->permissions as $perm): ?>
                            <span class="badge bg-secondary me-1 mb-1"><?= sanitize($perm) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <form method="POST" action="/roles/<?= (int) $role->id ?>/assign">
                        <?= csrf_field() ?>

                        <?php if (!empty($allPermissions)): ?>
                            <?php foreach ($allPermissions as $group => $permissions): ?>
                                <div class="mb-4">
                                    <div class="fw-medium text-muted small text-uppercase mb-2 border-bottom pb-1">
                                        <?= sanitize($group) ?>
                                    </div>
                                    <div class="row g-2">
                                        <?php foreach ($permissions as $perm): ?>
                                            <?php $permId = is_array($perm) ? $perm['id'] : $perm; ?>
                                            <?php $permName = is_array($perm) ? ($perm['name'] ?? $perm['slug'] ?? $permId) : $perm; ?>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input"
                                                        name="permissions[]"
                                                        value="<?= (int) $permId ?>"
                                                        id="perm_<?= (int) $permId ?>"
                                                        <?= in_array($permId, array_column($role->permissions, 'id') ?: $role->permissions, true) ? 'checked' : '' ?>
                                                    >
                                                    <label class="form-check-label small" for="perm_<?= (int) $permId ?>">
                                                        <?= sanitize((string) $permName) ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted small mb-3">No permissions have been defined yet.</p>
                        <?php endif; ?>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-sync me-1"></i>Sync Permissions
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
