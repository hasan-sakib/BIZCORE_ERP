<?php
$pageTitle = $pageTitle ?? $branch->name;
ob_start();

$stats = $stats ?? [];
?>

<div class="row g-4">

    <!-- Branch Info -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="fas fa-building me-2 text-primary"></i>Branch Details</h6>
                <a href="/branches/<?= (int) $branch->id ?>/edit" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
            </div>
            <div class="card-body">

                <?php if ($branch->isHeadOffice()): ?>
                    <div class="alert alert-info py-2 d-flex align-items-center gap-2 mb-3">
                        <i class="fas fa-star text-primary"></i>
                        <span class="small fw-medium">Head Office</span>
                    </div>
                <?php endif; ?>

                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">ID</dt>
                    <dd class="col-7"><?= (int) $branch->id ?></dd>

                    <dt class="col-5 text-muted fw-normal">Name</dt>
                    <dd class="col-7 fw-medium"><?= sanitize($branch->name) ?></dd>

                    <dt class="col-5 text-muted fw-normal">Code</dt>
                    <dd class="col-7"><code class="badge bg-secondary"><?= sanitize($branch->code) ?></code></dd>

                    <dt class="col-5 text-muted fw-normal">Status</dt>
                    <dd class="col-7">
                        <span class="badge <?= $branch->isActive() ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $branch->isActive() ? 'Active' : 'Inactive' ?>
                        </span>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Email</dt>
                    <dd class="col-7 text-muted small">
                        <?php if ($branch->email): ?>
                            <a href="mailto:<?= sanitize($branch->email) ?>"><?= sanitize($branch->email) ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-7 text-muted small">
                        <?= $branch->phone ? sanitize($branch->phone) : '—' ?>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Address</dt>
                    <dd class="col-7 text-muted small">
                        <?php $addr = $branch->formattedAddress(); ?>
                        <?= $addr ? sanitize($addr) : '—' ?>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Created</dt>
                    <dd class="col-7 text-muted small">
                        <?= sanitize($branch->createdAt->format('d M Y')) ?>
                    </dd>
                </dl>
            </div>
            <div class="card-footer d-flex gap-2 flex-wrap">
                <?php if ($branch->isActive()): ?>
                    <form method="POST" action="/branches/switch/<?= (int) $branch->id ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-exchange-alt me-1"></i>Switch to Branch
                        </button>
                    </form>
                <?php endif; ?>
                <a href="/branches/<?= (int) $branch->id ?>/edit" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
                <?php if (!$branch->isHeadOffice()): ?>
                    <button type="button"
                            class="btn btn-outline-danger btn-sm"
                            onclick="confirmDelete('/branches/<?= (int) $branch->id ?>')">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-3">
            <a href="/branches" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to Branches
            </a>
        </div>
    </div>

    <!-- Stats / Dashboard -->
    <div class="col-12 col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card text-center py-3">
                    <div class="text-primary fs-4 fw-bold">
                        <?= number_format((float) ($stats['employee_count'] ?? 0)) ?>
                    </div>
                    <div class="text-muted small">Employees</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-3">
                    <div class="text-success fs-4 fw-bold">
                        <?= number_format((float) ($stats['pending_orders'] ?? 0)) ?>
                    </div>
                    <div class="text-muted small">Pending Orders</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-3">
                    <div class="text-info fs-4 fw-bold">
                        <?= number_format((float) ($stats['total_invoices'] ?? 0)) ?>
                    </div>
                    <div class="text-muted small">Invoices</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-3">
                    <div class="text-warning fs-4 fw-bold">
                        ৳<?= number_format((float) ($stats['revenue'] ?? 0), 0) ?>
                    </div>
                    <div class="text-muted small">Revenue (Month)</div>
                </div>
            </div>
        </div>

        <!-- Settings preview -->
        <?php if (!empty($branch->settings)): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-cog me-2 text-muted"></i>Branch Settings</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <?php foreach ($branch->settings as $key => $value): ?>
                            <?php if (!is_array($value)): ?>
                                <dt class="col-sm-4 text-muted fw-normal small"><?= sanitize((string) $key) ?></dt>
                                <dd class="col-sm-8 small"><?= sanitize((string) $value) ?></dd>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
