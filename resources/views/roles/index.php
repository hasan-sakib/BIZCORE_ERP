<?php
$pageTitle = $pageTitle ?? 'Roles';
ob_start();
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">
            <i class="fas fa-shield-alt me-2 text-primary"></i>
            Roles
            <span class="badge bg-secondary ms-1"><?= count($roles) ?></span>
        </h6>
        <a href="/roles/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Add Role
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($roles)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-shield-alt fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">No roles found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Role Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Permissions</th>
                            <th>System Role</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= (int) $role->id ?></td>
                                <td>
                                    <div class="fw-medium"><?= sanitize($role->name) ?></div>
                                </td>
                                <td>
                                    <code class="small text-muted"><?= sanitize($role->slug) ?></code>
                                </td>
                                <td class="text-muted small">
                                    <?= sanitize($role->description ?: '—') ?>
                                </td>
                                <td>
                                    <span class="badge bg-info-soft text-info">
                                        <?= count($role->permissions) ?> permissions
                                    </span>
                                </td>
                                <td>
                                    <?php if ($role->isSystem): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-lock me-1"></i>System
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="/roles/<?= (int) $role->id ?>"
                                           class="btn btn-outline-secondary btn-xs"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (!$role->isSystem): ?>
                                            <a href="/roles/<?= (int) $role->id ?>/edit"
                                               class="btn btn-outline-primary btn-xs"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-xs"
                                                    title="Delete"
                                                    onclick="confirmDelete('/roles/<?= (int) $role->id ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-xs"
                                                    disabled
                                                    title="System roles cannot be deleted">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
