<?php
$pageTitle = sanitize($department['name'] ?? 'Department');
ob_start();
?>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="fas fa-sitemap me-2"></i><?= sanitize($department['name']) ?></h5>
                <div class="d-flex gap-2">
                    <a href="/hr/departments/<?= (int) $department['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete('/hr/departments/<?= (int) $department['id'] ?>')">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8"><?= sanitize($department['name']) ?></dd>

                    <dt class="col-sm-4 text-muted">Code</dt>
                    <dd class="col-sm-8"><code><?= sanitize($department['code'] ?? '-') ?></code></dd>

                    <dt class="col-sm-4 text-muted">Department Head</dt>
                    <dd class="col-sm-8"><?= sanitize($department['head_name'] ?? '-') ?></dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <?php $cls = ($department['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary'; ?>
                        <span class="badge <?= $cls ?>"><?= ucfirst(sanitize($department['status'] ?? 'inactive')) ?></span>
                    </dd>

                    <?php if (!empty($department['description'])): ?>
                        <dt class="col-sm-4 text-muted">Description</dt>
                        <dd class="col-sm-8"><?= nl2br(sanitize($department['description'])) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-4 text-muted">Created</dt>
                    <dd class="col-sm-8"><?= !empty($department['created_at']) ? date('d M Y, H:i', strtotime($department['created_at'])) : '-' ?></dd>

                    <dt class="col-sm-4 text-muted">Last Updated</dt>
                    <dd class="col-sm-8"><?= !empty($department['updated_at']) ? date('d M Y, H:i', strtotime($department['updated_at'])) : '-' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="mb-2">
                    <i class="fas fa-sitemap fa-3x text-primary opacity-75"></i>
                </div>
                <p class="text-muted small mb-3">
                    Manage the employees and designations that belong to this department.
                </p>
                <a href="/hr/employees?department_id=<?= (int) $department['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="fas fa-users me-1"></i>View Employees
                </a>
                <a href="/hr/designations?department_id=<?= (int) $department['id'] ?>" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-briefcase me-1"></i>View Designations
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
