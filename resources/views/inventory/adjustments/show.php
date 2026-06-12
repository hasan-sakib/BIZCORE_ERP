<?php
$pageTitle   = 'Adjustment — ' . sanitize($adjustment['reference_no'] ?? '');
$breadcrumbs = ['Inventory' => null, 'Adjustments' => '/inventory/adjustments', sanitize($adjustment['reference_no'] ?? '') => null];
ob_start();
?>

<div class="row g-4">
    <!-- Record header -->
    <div class="col-12 col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-sliders-h me-2 text-warning"></i>Adjustment Details</h6>
                <div class="d-flex gap-2">
                    <?php if ($adjustment['status'] === 'pending'): ?>
                        <form method="POST" action="/inventory/adjustments/<?= (int) $adjustment['id'] ?>/approve" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-success btn-sm">
                                <i class="fas fa-check me-1"></i>Approve
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="/inventory/adjustments" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Reference</dt>
                    <dd class="col-sm-8 fw-semibold"><?= sanitize($adjustment['reference_no'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Warehouse</dt>
                    <dd class="col-sm-8"><?= sanitize($adjustment['warehouse_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Reason</dt>
                    <dd class="col-sm-8"><?= sanitize($adjustment['reason'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Date</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($adjustment['date'])): ?>
                            <?= date('d M Y', strtotime($adjustment['date'])) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <?php
                        $sc = match ((string) ($adjustment['status'] ?? '')) {
                            'pending'  => 'bg-warning text-dark',
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            default    => 'bg-light text-dark',
                        };
                        ?>
                        <span class="badge <?= $sc ?>"><?= sanitize(ucfirst((string) ($adjustment['status'] ?? ''))) ?></span>
                    </dd>

                    <?php if (!empty($adjustment['created_by_name'])): ?>
                        <dt class="col-sm-4 text-muted">Created By</dt>
                        <dd class="col-sm-8 small"><?= sanitize($adjustment['created_by_name']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($adjustment['notes'])): ?>
                        <dt class="col-sm-4 text-muted">Notes</dt>
                        <dd class="col-sm-8 small"><?= sanitize($adjustment['notes']) ?></dd>
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
                <div class="display-6 fw-bold text-warning"><?= count($adjustment['items'] ?? []) ?></div>
                <div class="text-muted small mt-1">Adjustment Item(s)</div>
                <hr>
                <?php
                $addCount    = 0;
                $removeCount = 0;
                foreach ($adjustment['items'] ?? [] as $item) {
                    if (($item['type'] ?? '') === 'add') {
                        $addCount++;
                    } else {
                        $removeCount++;
                    }
                }
                ?>
                <div class="d-flex justify-content-center gap-3 small text-muted">
                    <span><i class="fas fa-plus-circle text-success me-1"></i><?= $addCount ?> Add</span>
                    <span><i class="fas fa-minus-circle text-danger me-1"></i><?= $removeCount ?> Remove</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Adjustment Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>SKU</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th class="text-end">Quantity</th>
                                <th>Item Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($adjustment['items'])): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($adjustment['items'] as $i => $item): ?>
                                    <tr>
                                        <td class="text-muted small"><?= $i + 1 ?></td>
                                        <td><code class="small"><?= sanitize($item['sku'] ?? '') ?></code></td>
                                        <td><?= sanitize($item['product_name'] ?? '') ?></td>
                                        <td>
                                            <?php if (($item['type'] ?? '') === 'add'): ?>
                                                <span class="badge bg-success">Add</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Remove</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= number_format((float) ($item['quantity'] ?? $item['qty'] ?? 0), 4) ?></td>
                                        <td class="small text-muted"><?= sanitize($item['item_reason'] ?? '—') ?></td>
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
