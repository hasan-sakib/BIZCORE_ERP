<?php ob_start(); ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-landmark me-2 text-primary"></i>Account Details</h6>
                <div class="d-flex gap-2">
                    <a href="/accounting/accounts/<?= (int) $account['id'] ?>/edit" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">Code</dt>
                    <dd class="col-sm-7"><code><?= sanitize($account['code']) ?></code></dd>

                    <dt class="col-sm-5 text-muted">Name</dt>
                    <dd class="col-sm-7"><?= sanitize($account['name']) ?></dd>

                    <dt class="col-sm-5 text-muted">Type</dt>
                    <dd class="col-sm-7"><span class="badge bg-secondary"><?= ucfirst(sanitize($account['type'])) ?></span></dd>

                    <dt class="col-sm-5 text-muted">Sub-type</dt>
                    <dd class="col-sm-7"><?= sanitize($account['subtype'] ?? '—') ?></dd>

                    <dt class="col-sm-5 text-muted">Normal Balance</dt>
                    <dd class="col-sm-7"><?= ucfirst(sanitize($account['normal_balance'])) ?></dd>

                    <dt class="col-sm-5 text-muted">Current Balance</dt>
                    <dd class="col-sm-7 fw-bold">৳<?= number_format((float) $account['balance'], 2) ?></dd>

                    <dt class="col-sm-5 text-muted">Status</dt>
                    <dd class="col-sm-7">
                        <?php if ($account['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                        <?php if ($account['is_system']): ?>
                            <span class="badge bg-info ms-1">System Account</span>
                        <?php endif; ?>
                    </dd>

                    <?php if (!empty($account['description'])): ?>
                        <dt class="col-sm-5 text-muted">Description</dt>
                        <dd class="col-sm-7"><?= sanitize($account['description']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-history me-2 text-primary"></i>Recent Transactions</h6>
            </div>
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-chart-line fa-3x mb-3 opacity-25"></i>
                <p>Transaction history will be available here.</p>
                <a href="/accounting/ledger/<?= (int) $account['id'] ?>" class="btn btn-outline-primary btn-sm">View Full Ledger</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
