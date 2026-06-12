<?php
$pageTitle = 'Attendance';
ob_start();

$statusColors = [
    'present'  => 'success',
    'absent'   => 'danger',
    'half_day' => 'warning',
    'late'     => 'warning',
    'holiday'  => 'info',
    'leave'    => 'secondary',
];
?>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3" method="GET" action="/attendance">

            <div class="col-12 col-md-3">
                <select name="employee_id" class="form-select">
                    <option value="">All Employees</option>
                    <?php foreach ($employeeList as $emp): ?>
                        <option value="<?= (int) $emp['id'] ?>"
                            <?= ((int) ($filters['employee_id'] ?? 0)) === (int) $emp['id'] ? 'selected' : '' ?>>
                            <?= sanitize(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <input type="date" name="date_from" class="form-control"
                       value="<?= sanitize($filters['date_from'] ?? '') ?>" placeholder="From date">
            </div>

            <div class="col-12 col-md-2">
                <input type="date" name="date_to" class="form-control"
                       value="<?= sanitize($filters['date_to'] ?? '') ?>" placeholder="To date">
            </div>

            <div class="col-12 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <?php
                    $statuses = ['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half Day', 'late' => 'Late', 'holiday' => 'Holiday', 'leave' => 'Leave'];
                    foreach ($statuses as $sVal => $sLabel):
                    ?>
                        <option value="<?= $sVal ?>" <?= ($filters['status'] ?? '') === $sVal ? 'selected' : '' ?>><?= $sLabel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="/attendance" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-calendar-times fa-3x mb-3 d-block opacity-25"></i>
                                No attendance records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $rec): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        <?= sanitize(($rec['first_name'] ?? '') . ' ' . ($rec['last_name'] ?? '')) ?>
                                    </div>
                                    <div class="text-muted small">
                                        <code><?= sanitize($rec['employee_number'] ?? '') ?></code>
                                    </div>
                                </td>
                                <td><?= !empty($rec['date']) ? date('d M Y', strtotime($rec['date'])) : '-' ?></td>
                                <td><?= !empty($rec['check_in']) ? date('H:i', strtotime($rec['check_in'])) : '-' ?></td>
                                <td><?= !empty($rec['check_out']) ? date('H:i', strtotime($rec['check_out'])) : '-' ?></td>
                                <td>
                                    <?php
                                    $color = $statusColors[$rec['status'] ?? 'present'] ?? 'secondary';
                                    $label = ucfirst(str_replace('_', ' ', $rec['status'] ?? 'present'));
                                    ?>
                                    <span class="badge bg-<?= $color ?>"><?= $label ?></span>
                                </td>
                                <td class="text-muted small"><?= sanitize($rec['notes'] ?? '') ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/attendance/<?= (int) $rec['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" title="Delete"
                                                onclick="confirmDelete('/attendance/<?= (int) $rec['id'] ?>')">
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
