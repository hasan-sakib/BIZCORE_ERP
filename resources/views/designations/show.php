<?php
$pageTitle = sanitize($designation['name'] ?? 'Designation');
ob_start();
?>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="fas fa-briefcase me-2"></i><?= sanitize($designation['name']) ?></h5>
                <div class="d-flex gap-2">
                    <a href="/hr/designations/<?= (int) $designation['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete('/hr/designations/<?= (int) $designation['id'] ?>')">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8"><?= sanitize($designation['name']) ?></dd>

                    <dt class="col-sm-4 text-muted">Department</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($designation['department_id'])): ?>
                            <a href="/hr/departments/<?= (int) $designation['department_id'] ?>">
                                <?= sanitize($designation['department_name'] ?? '-') ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <?php $cls = ($designation['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary'; ?>
                        <span class="badge <?= $cls ?>"><?= ucfirst(sanitize($designation['status'] ?? 'inactive')) ?></span>
                    </dd>

                    <?php if (!empty($designation['description'])): ?>
                        <dt class="col-sm-4 text-muted">Description</dt>
                        <dd class="col-sm-8"><?= nl2br(sanitize($designation['description'])) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-4 text-muted">Created</dt>
                    <dd class="col-sm-8"><?= !empty($designation['created_at']) ? date('d M Y, H:i', strtotime($designation['created_at'])) : '-' ?></dd>

                    <dt class="col-sm-4 text-muted">Last Updated</dt>
                    <dd class="col-sm-8"><?= !empty($designation['updated_at']) ? date('d M Y, H:i', strtotime($designation['updated_at'])) : '-' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="mb-2">
                    <i class="fas fa-briefcase fa-3x text-primary opacity-75"></i>
                </div>
                <p class="text-muted small mb-3">
                    View employees holding this designation.
                </p>
                <a href="/hr/employees?designation_id=<?= (int) $designation['id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                    <i class="fas fa-users me-1"></i>View Employees
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
