<?php
$pageTitle = $pageTitle ?? 'Expense Detail';
$expense   = $expense   ?? [];
ob_start();
?>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0">
                    <i class="fas fa-receipt me-2 text-primary"></i>
                    Expense Details
                </h6>
                <div class="d-flex gap-2">
                    <?php
                    $statusBadge = match ($expense['status'] ?? 'pending') {
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        default    => 'bg-warning text-dark',
                    };
                    ?>
                    <span class="badge <?= $statusBadge ?> fs-6 px-3 py-2">
                        <?= ucfirst(sanitize($expense['status'] ?? 'pending')) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Reference No.</div>
                        <div class="fw-semibold font-monospace"><?= sanitize($expense['reference_no'] ?? '—') ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Amount</div>
                        <div class="fw-bold fs-5 text-primary">
                            ৳<?= number_format((float) ($expense['amount'] ?? 0), 2) ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Category</div>
                        <div>
                            <?php if (!empty($expense['category_name'])): ?>
                                <span class="badge"
                                      style="background:<?= sanitize($expense['category_color'] ?? '#6c757d') ?>">
                                    <?= sanitize($expense['category_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Date</div>
                        <div><?= $expense['date'] ? date('d M Y', strtotime($expense['date'])) : '—' ?></div>
                    </div>
                    <?php if (!empty($expense['description'])): ?>
                    <div class="col-12">
                        <div class="text-muted small mb-1">Description</div>
                        <div><?= nl2br(sanitize($expense['description'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($expense['receipt_path'])): ?>
                    <div class="col-12">
                        <div class="text-muted small mb-1">Receipt</div>
                        <a href="<?= sanitize($expense['receipt_path']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-file me-1"></i>View Receipt
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($expense['approved_at'])): ?>
                    <div class="col-12">
                        <hr class="my-2">
                        <div class="text-muted small mb-1">Approved / Rejected At</div>
                        <div><?= date('d M Y, H:i', strtotime($expense['approved_at'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Footer -->
            <div class="card-footer bg-transparent d-flex flex-wrap gap-2">
                <?php if (($expense['status'] ?? '') === 'pending'): ?>
                    <a href="/expenses/<?= (int) $expense['id'] ?>/edit"
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>

                    <form method="POST" action="/expenses/<?= (int) $expense['id'] ?>/approve"
                          class="d-inline"
                          onsubmit="return confirm('Approve this expense?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-check me-1"></i>Approve
                        </button>
                    </form>

                    <form method="POST" action="/expenses/<?= (int) $expense['id'] ?>/reject"
                          class="d-inline"
                          onsubmit="return confirm('Reject this expense?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-times me-1"></i>Reject
                        </button>
                    </form>
                <?php endif; ?>

                <a href="/expenses" class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="fas fa-arrow-left me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Metadata sidebar -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0 text-muted"><i class="fas fa-info-circle me-2"></i>Metadata</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">ID</dt>
                    <dd class="col-7"><?= (int) $expense['id'] ?></dd>
                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7">
                        <?= !empty($expense['created_at']) ? date('d M Y', strtotime($expense['created_at'])) : '—' ?>
                    </dd>
                    <dt class="col-5 text-muted">Updated</dt>
                    <dd class="col-7 mb-0">
                        <?= !empty($expense['updated_at']) ? date('d M Y', strtotime($expense['updated_at'])) : '—' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
