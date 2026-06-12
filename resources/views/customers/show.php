<?php
$pageTitle = sanitize($customer['name'] ?? 'Customer');
ob_start();

$customer = $customer ?? [];
$id       = (int) ($customer['id'] ?? 0);
$status   = $customer['status'] ?? 'inactive';
?>

<div class="row g-4">
    <!-- Left: main details -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2 text-primary"></i>
                    <?= sanitize($customer['name'] ?? '') ?>
                </h5>
                <span class="badge <?= $status === 'active' ? 'bg-success' : 'bg-secondary' ?> fs-6">
                    <?= ucfirst($status) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Email</div>
                        <div>
                            <?php if (!empty($customer['email'])): ?>
                                <a href="mailto:<?= sanitize($customer['email']) ?>">
                                    <?= sanitize($customer['email']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Phone</div>
                        <div>
                            <?php if (!empty($customer['phone'])): ?>
                                <a href="tel:<?= sanitize($customer['phone']) ?>">
                                    <?= sanitize($customer['phone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">City</div>
                        <div><?= sanitize($customer['city'] ?? '—') ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Country</div>
                        <div><?= sanitize($customer['country'] ?? '—') ?></div>
                    </div>
                    <?php if (!empty($customer['address'])): ?>
                        <div class="col-12">
                            <div class="text-muted small mb-1">Address</div>
                            <div><?= nl2br(sanitize($customer['address'])) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Tax Number</div>
                        <div><?= sanitize($customer['tax_number'] ?? '—') ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Member Since</div>
                        <div>
                            <?= !empty($customer['created_at'])
                                ? date('d M Y', strtotime($customer['created_at']))
                                : '—' ?>
                        </div>
                    </div>
                    <?php if (!empty($customer['notes'])): ?>
                        <div class="col-12">
                            <div class="text-muted small mb-1">Notes</div>
                            <div class="text-muted"><?= nl2br(sanitize($customer['notes'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="/customers/<?= $id ?>/edit" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
                <button class="btn btn-danger btn-sm"
                        onclick="confirmDelete('/customers/<?= $id ?>')">
                    <i class="fas fa-trash me-1"></i>Delete
                </button>
                <a href="/customers" class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="fas fa-arrow-left me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Right: balance + quick actions -->
    <div class="col-12 col-lg-4">
        <!-- Balance card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">Current Balance</div>
                <div class="display-6 fw-bold <?= (float) ($customer['balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= number_format((float) ($customer['balance'] ?? 0), 2) ?>
                </div>
                <div class="mt-2 text-muted small">
                    Credit Limit: <?= number_format((float) ($customer['credit_limit'] ?? 0), 2) ?>
                </div>
            </div>
        </div>

        <!-- Quick links -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0">Quick Links</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="/customers/<?= $id ?>/ledger"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="fas fa-book text-primary"></i>
                    <span>Customer Ledger</span>
                    <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                </a>
                <a href="/customers/<?= $id ?>/orders"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="fas fa-shopping-bag text-success"></i>
                    <span>Orders</span>
                    <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
