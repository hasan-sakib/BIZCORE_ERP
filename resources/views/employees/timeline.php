<?php
$fullName  = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
$pageTitle = $fullName . ' — Timeline';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <!-- Employee mini header -->
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar-lg bg-primary text-white fw-bold rounded-circle d-flex align-items-center justify-content-center"
                     style="width:48px;height:48px;font-size:1.15rem">
                    <?= strtoupper(substr($employee['first_name'] ?? 'E', 0, 1) . substr($employee['last_name'] ?? 'M', 0, 1)) ?>
                </div>
                <div>
                    <div class="fw-semibold"><?= sanitize($fullName) ?></div>
                    <div class="text-muted small"><?= sanitize($employee['employee_number'] ?? '') ?>
                        &bull; <?= sanitize($employee['designation_name'] ?? '') ?>
                    </div>
                </div>
                <div class="ms-auto">
                    <a href="/hr/employees/<?= (int) $employee['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Activity Timeline</h5>
            </div>
            <div class="card-body">
                <?php if (empty($events)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-history fa-3x mb-3 d-block opacity-25"></i>
                        <p class="mb-0">No timeline events recorded yet.</p>
                        <p class="small">Employee activity such as transfers, status changes, and promotions will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($events as $event): ?>
                            <div class="timeline-item d-flex gap-3 mb-4">
                                <div class="timeline-icon bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:36px;height:36px">
                                    <i class="fas fa-circle-dot small"></i>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <div class="fw-semibold"><?= sanitize($event['title'] ?? '') ?></div>
                                    <div class="text-muted small"><?= sanitize($event['description'] ?? '') ?></div>
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= !empty($event['created_at']) ? date('d M Y, H:i', strtotime($event['created_at'])) : '-' ?>
                                    </div>
                                </div>
                            </div>
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
