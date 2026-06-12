<?php
$fullName  = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
$pageTitle = $fullName;
ob_start();

$statusColors = [
    'active'     => 'success',
    'inactive'   => 'secondary',
    'on_leave'   => 'warning',
    'terminated' => 'danger',
];
$statusColor = $statusColors[$employee['status'] ?? 'inactive'] ?? 'secondary';
?>

<div class="row">
    <!-- Left Column: Main Info -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-lg bg-primary text-white fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width:52px;height:52px;font-size:1.25rem">
                        <?= strtoupper(substr($employee['first_name'] ?? 'E', 0, 1) . substr($employee['last_name'] ?? 'M', 0, 1)) ?>
                    </div>
                    <div>
                        <h5 class="card-title mb-0"><?= sanitize($fullName) ?></h5>
                        <span class="text-muted small"><?= sanitize($employee['employee_number'] ?? '') ?></span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="/hr/employees/<?= (int) $employee['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete('/hr/employees/<?= (int) $employee['id'] ?>')">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">Personal</h6>
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted">Email</dt>
                            <dd class="col-7"><?= sanitize($employee['email'] ?? '-') ?></dd>

                            <dt class="col-5 text-muted">Phone</dt>
                            <dd class="col-7"><?= sanitize($employee['phone'] ?? '-') ?></dd>

                            <dt class="col-5 text-muted">Date of Birth</dt>
                            <dd class="col-7">
                                <?= !empty($employee['date_of_birth']) ? date('d M Y', strtotime($employee['date_of_birth'])) : '-' ?>
                            </dd>

                            <dt class="col-5 text-muted">Gender</dt>
                            <dd class="col-7"><?= sanitize(ucfirst($employee['gender'] ?? '-')) ?></dd>

                            <dt class="col-5 text-muted">Address</dt>
                            <dd class="col-7"><?= nl2br(sanitize($employee['address'] ?? '-')) ?></dd>
                        </dl>
                    </div>

                    <div class="col-12 col-md-6">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">Employment</h6>
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted">Department</dt>
                            <dd class="col-7">
                                <?php if (!empty($employee['department_id'])): ?>
                                    <a href="/hr/departments/<?= (int) $employee['department_id'] ?>">
                                        <?= sanitize($employee['department_name'] ?? '-') ?>
                                    </a>
                                <?php else: ?>-<?php endif; ?>
                            </dd>

                            <dt class="col-5 text-muted">Designation</dt>
                            <dd class="col-7">
                                <?php if (!empty($employee['designation_id'])): ?>
                                    <a href="/hr/designations/<?= (int) $employee['designation_id'] ?>">
                                        <?= sanitize($employee['designation_name'] ?? '-') ?>
                                    </a>
                                <?php else: ?>-<?php endif; ?>
                            </dd>

                            <dt class="col-5 text-muted">Date of Joining</dt>
                            <dd class="col-7">
                                <?= !empty($employee['date_of_joining']) ? date('d M Y', strtotime($employee['date_of_joining'])) : '-' ?>
                            </dd>

                            <dt class="col-5 text-muted">Date of Leaving</dt>
                            <dd class="col-7">
                                <?= !empty($employee['date_of_leaving']) ? date('d M Y', strtotime($employee['date_of_leaving'])) : '-' ?>
                            </dd>

                            <dt class="col-5 text-muted">Basic Salary</dt>
                            <dd class="col-7">$<?= number_format((float) ($employee['basic_salary'] ?? 0), 2) ?></dd>

                            <dt class="col-5 text-muted">Status</dt>
                            <dd class="col-7">
                                <span class="badge bg-<?= $statusColor ?>">
                                    <?= ucfirst(str_replace('_', ' ', $employee['status'] ?? 'inactive')) ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Actions -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">Quick Actions</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="/hr/employees/<?= (int) $employee['id'] ?>/edit" class="list-group-item list-group-item-action">
                    <i class="fas fa-edit me-2 text-secondary"></i>Edit Employee
                </a>
                <a href="/hr/employees/<?= (int) $employee['id'] ?>/timeline" class="list-group-item list-group-item-action">
                    <i class="fas fa-history me-2 text-info"></i>View Timeline
                </a>
                <a href="/attendance?employee_id=<?= (int) $employee['id'] ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-clock me-2 text-success"></i>Attendance Records
                </a>
                <button class="list-group-item list-group-item-action text-danger"
                        onclick="confirmDelete('/hr/employees/<?= (int) $employee['id'] ?>')">
                    <i class="fas fa-trash me-2"></i>Delete Employee
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="card-title mb-0">Record Info</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted">Created</dt>
                    <dd class="col-6">
                        <?= !empty($employee['created_at']) ? date('d M Y', strtotime($employee['created_at'])) : '-' ?>
                    </dd>
                    <dt class="col-6 text-muted">Updated</dt>
                    <dd class="col-6">
                        <?= !empty($employee['updated_at']) ? date('d M Y', strtotime($employee['updated_at'])) : '-' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
