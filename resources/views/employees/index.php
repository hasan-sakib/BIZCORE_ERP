<?php
$layout    = 'app';
$pageTitle = 'Employees';
ob_start();
?>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3" method="GET" action="/hr/employees">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search name, employee ID, email..."
                       value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($filters['deptId'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                            <?= sanitize($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active"   <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="on_leave" <?= ($filters['status'] ?? '') === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                    <option value="terminated" <?= ($filters['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="/hr/employees" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>ID</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-users fa-3x mb-3 d-block opacity-25"></i>
                                No employees found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm bg-primary-soft text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center">
                                            <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?= sanitize($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                                            <div class="text-muted small"><?= sanitize($emp['email'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><code><?= sanitize($emp['employee_number'] ?? '-') ?></code></td>
                                <td><?= sanitize($emp['department_name'] ?? '-') ?></td>
                                <td><?= sanitize($emp['designation_name'] ?? '-') ?></td>
                                <td><?= !empty($emp['join_date']) ? date('d M Y', strtotime($emp['join_date'])) : '-' ?></td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'active'     => 'bg-success',
                                        'inactive'   => 'bg-secondary',
                                        'on_leave'   => 'bg-warning',
                                        'terminated' => 'bg-danger',
                                    ];
                                    $cls = $statusClasses[$emp['status'] ?? 'inactive'] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= ucfirst(str_replace('_', ' ', $emp['status'] ?? 'inactive')) ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/hr/employees/<?= $emp['id'] ?>" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/hr/employees/<?= $emp['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" title="Delete"
                                                onclick="confirmDelete('/hr/employees/<?= $emp['id'] ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?php include __DIR__ . '/../components/pagination.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
