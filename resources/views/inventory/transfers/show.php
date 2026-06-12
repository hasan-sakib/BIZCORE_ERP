<?php
$pageTitle   = 'Transfer — ' . sanitize($transfer['reference_no'] ?? '');
$breadcrumbs = ['Inventory' => null, 'Transfers' => '/inventory/transfers', sanitize($transfer['reference_no'] ?? '') => null];
ob_start();
?>

<div class="row g-4">
    <!-- Record header -->
    <div class="col-12 col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-exchange-alt me-2 text-primary"></i>Transfer Details</h6>
                <div class="d-flex gap-2">
                    <?php if ($transfer['status'] === 'draft'): ?>
                        <form method="POST" action="/inventory/transfers/<?= (int) $transfer['id'] ?>/confirm" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-check me-1"></i>Confirm
                            </button>
                        </form>
                    <?php elseif ($transfer['status'] === 'confirmed'): ?>
                        <form method="POST" action="/inventory/transfers/<?= (int) $transfer['id'] ?>/receive" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-success btn-sm">
                                <i class="fas fa-box me-1"></i>Mark Received
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if (!in_array($transfer['status'], ['received', 'cancelled'])): ?>
                        <form method="POST" action="/inventory/transfers/<?= (int) $transfer['id'] ?>/cancel" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Cancel this transfer?')">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="/inventory/transfers" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Reference</dt>
                    <dd class="col-sm-8 fw-semibold"><?= sanitize($transfer['reference_no'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">From Warehouse</dt>
                    <dd class="col-sm-8"><?= sanitize($transfer['from_warehouse_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">To Warehouse</dt>
                    <dd class="col-sm-8"><?= sanitize($transfer['to_warehouse_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Date</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($transfer['date'])): ?>
                            <?= date('d M Y', strtotime($transfer['date'])) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <?php
                        $sc = match ((string) ($transfer['status'] ?? '')) {
                            'draft'      => 'bg-secondary',
                            'confirmed'  => 'bg-info text-dark',
                            'in_transit' => 'bg-warning text-dark',
                            'received'   => 'bg-success',
                            'cancelled'  => 'bg-danger',
                            default      => 'bg-light text-dark',
                        };
                        $statusLabel = ucwords(str_replace('_', ' ', (string) ($transfer['status'] ?? '')));
                        ?>
                        <span class="badge <?= $sc ?>"><?= sanitize($statusLabel) ?></span>
                    </dd>

                    <?php if (!empty($transfer['created_by_name'])): ?>
                        <dt class="col-sm-4 text-muted">Created By</dt>
                        <dd class="col-sm-8 small"><?= sanitize($transfer['created_by_name']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($transfer['notes'])): ?>
                        <dt class="col-sm-4 text-muted">Notes</dt>
                        <dd class="col-sm-8 small"><?= sanitize($transfer['notes']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="col-12 col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-info"></i>Summary</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center text-center">
                <div class="display-6 fw-bold text-primary"><?= count($transfer['items'] ?? []) ?></div>
                <div class="text-muted small mt-1">Line Item(s)</div>
                <hr>
                <div class="text-muted small">
                    <?= sanitize($transfer['from_warehouse_name'] ?? '—') ?>
                    <i class="fas fa-arrow-right mx-2"></i>
                    <?= sanitize($transfer['to_warehouse_name'] ?? '—') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Transferred Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>SKU</th>
                                <th>Product</th>
                                <th class="text-end">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transfer['items'])): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transfer['items'] as $i => $item): ?>
                                    <tr>
                                        <td class="text-muted small"><?= $i + 1 ?></td>
                                        <td><code class="small"><?= sanitize($item['sku'] ?? '') ?></code></td>
                                        <td><?= sanitize($item['product_name'] ?? '') ?></td>
                                        <td class="text-end"><?= number_format((float) ($item['quantity'] ?? $item['qty'] ?? 0), 4) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
