<?php
$pageTitle = sanitize($supplier['name'] ?? 'Supplier');
ob_start();

$supplier = $supplier ?? [];
$id       = (int) ($supplier['id'] ?? 0);
$status   = $supplier['status'] ?? 'inactive';
?>

<div class="row g-4">
    <!-- Left: main details -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="fas fa-truck me-2 text-primary"></i>
                    <?= sanitize($supplier['name'] ?? '') ?>
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
                            <?php if (!empty($supplier['email'])): ?>
                                <a href="mailto:<?= sanitize($supplier['email']) ?>">
                                    <?= sanitize($supplier['email']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Phone</div>
                        <div>
                            <?php if (!empty($supplier['phone'])): ?>
                                <a href="tel:<?= sanitize($supplier['phone']) ?>">
                                    <?= sanitize($supplier['phone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">City</div>
                        <div><?= sanitize($supplier['city'] ?? '—') ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Country</div>
                        <div><?= sanitize($supplier['country'] ?? '—') ?></div>
                    </div>
                    <?php if (!empty($supplier['address'])): ?>
                        <div class="col-12">
                            <div class="text-muted small mb-1">Address</div>
                            <div><?= nl2br(sanitize($supplier['address'])) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Tax Number</div>
                        <div><?= sanitize($supplier['tax_number'] ?? '—') ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Payment Terms</div>
                        <div><?= sanitize($supplier['payment_terms'] ?? '—') ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">Member Since</div>
                        <div>
                            <?= !empty($supplier['created_at'])
                                ? date('d M Y', strtotime($supplier['created_at']))
                                : '—' ?>
                        </div>
                    </div>
                    <?php if (!empty($supplier['notes'])): ?>
                        <div class="col-12">
                            <div class="text-muted small mb-1">Notes</div>
                            <div class="text-muted"><?= nl2br(sanitize($supplier['notes'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="/suppliers/<?= $id ?>/edit" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
                <button class="btn btn-danger btn-sm"
                        onclick="confirmDelete('/suppliers/<?= $id ?>')">
                    <i class="fas fa-trash me-1"></i>Delete
                </button>
                <a href="/suppliers" class="btn btn-outline-secondary btn-sm ms-auto">
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
                <div class="display-6 fw-bold <?= (float) ($supplier['balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= number_format((float) ($supplier['balance'] ?? 0), 2) ?>
                </div>
                <div class="mt-2 text-muted small">
                    Credit Limit: <?= number_format((float) ($supplier['credit_limit'] ?? 0), 2) ?>
                </div>
            </div>
        </div>

        <!-- Quick links -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0">Quick Links</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="/suppliers/<?= $id ?>/ledger"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="fas fa-book text-primary"></i>
                    <span>Supplier Ledger</span>
                    <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                </a>
                <a href="/suppliers/<?= $id ?>/orders"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="fas fa-shopping-cart text-success"></i>
                    <span>Purchase Orders</span>
                    <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
