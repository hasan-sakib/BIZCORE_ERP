<?php
$pageTitle = 'My Attendance';
ob_start();

$statusColors = [
    'present'  => 'success',
    'absent'   => 'danger',
    'half_day' => 'warning',
    'late'     => 'warning',
    'holiday'  => 'info',
    'leave'    => 'secondary',
];

$canCheckIn  = $employee !== null && $today === null;
$canCheckOut = $employee !== null && $today !== null && empty($today['check_out']);
?>

<?php if ($employee === null): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="fas fa-exclamation-triangle fa-lg"></i>
        <div>
            No employee record is linked to your account. Please contact HR to have your account linked.
        </div>
    </div>
<?php else: ?>

    <!-- Today's Status Card -->
    <div class="row mb-4">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="fas fa-user-clock me-2"></i>Today's Attendance</h5>
                    <span class="text-muted small"><?= date('l, d M Y') ?></span>
                </div>
                <div class="card-body">
                    <?php if ($today): ?>
                        <div class="row g-3 text-center mb-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Check In</div>
                                    <div class="fw-bold fs-5 text-success">
                                        <?= !empty($today['check_in']) ? date('H:i', strtotime($today['check_in'])) : '--:--' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Check Out</div>
                                    <div class="fw-bold fs-5 <?= !empty($today['check_out']) ? 'text-danger' : 'text-muted' ?>">
                                        <?= !empty($today['check_out']) ? date('H:i', strtotime($today['check_out'])) : '--:--' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $color = $statusColors[$today['status'] ?? 'present'] ?? 'secondary';
                        $label = ucfirst(str_replace('_', ' ', $today['status'] ?? 'present'));
                        ?>
                        <p class="text-center mb-3">
                            Status: <span class="badge bg-<?= $color ?>"><?= $label ?></span>
                        </p>
                    <?php else: ?>
                        <p class="text-center text-muted mb-3">You have not checked in yet today.</p>
                    <?php endif; ?>

                    <div class="d-flex gap-2 justify-content-center">
                        <?php if ($canCheckIn): ?>
                            <form method="POST" action="/attendance/check-in">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-sign-in-alt me-1"></i>Check In
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($canCheckOut): ?>
                            <form method="POST" action="/attendance/check-out">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt me-1"></i>Check Out
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$canCheckIn && !$canCheckOut && $today): ?>
                            <span class="text-muted small align-self-center">
                                <i class="fas fa-check-circle text-success me-1"></i>Attendance complete for today.
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Attendance History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-calendar-times fa-3x mb-3 d-block opacity-25"></i>
                                    No attendance records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $rec): ?>
                                <tr>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
