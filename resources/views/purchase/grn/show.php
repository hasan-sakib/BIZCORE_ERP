<?php
$pageTitle   = 'GRN — ' . sanitize($grn['grn_number'] ?? '');
$breadcrumbs = ['Purchasing' => null, 'GRN' => '/purchasing/grn', sanitize($grn['grn_number'] ?? '') => null];
ob_start();
?>

<div class="row g-4">
    <!-- GRN header -->
    <div class="col-12 col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-boxes me-2 text-success"></i>GRN Details</h6>
                <a href="/purchasing/grn" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Reference</dt>
                    <dd class="col-sm-8 fw-semibold"><?= sanitize($grn['grn_number'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">PO Reference</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($grn['po_reference'])): ?>
                            <a href="/purchasing/orders/<?= (int) ($grn['po_id'] ?? 0) ?>" class="text-decoration-none">
                                <?= sanitize($grn['po_reference']) ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-muted">Supplier</dt>
                    <dd class="col-sm-8"><?= sanitize($grn['supplier_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Warehouse</dt>
                    <dd class="col-sm-8"><?= sanitize($grn['warehouse_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Receipt Date</dt>
                    <dd class="col-sm-8">
                        <?= !empty($grn['receipt_date']) ? date('d M Y', strtotime($grn['receipt_date'])) : '—' ?>
                    </dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <?php
                        $sc = match ((string) ($grn['status'] ?? '')) {
                            'received' => 'bg-success',
                            'partial'  => 'bg-warning text-dark',
                            'draft'    => 'bg-secondary',
                            default    => 'bg-light text-dark',
                        };
                        ?>
                        <span class="badge <?= $sc ?>"><?= sanitize(ucfirst((string) ($grn['status'] ?? ''))) ?></span>
                    </dd>

                    <?php if (!empty($grn['created_by_name'])): ?>
                        <dt class="col-sm-4 text-muted">Created By</dt>
                        <dd class="col-sm-8 small"><?= sanitize($grn['created_by_name']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($grn['notes'])): ?>
                        <dt class="col-sm-4 text-muted">Notes</dt>
                        <dd class="col-sm-8 small"><?= sanitize($grn['notes']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="col-12 col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-calculator me-2 text-info"></i>Summary</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center text-center">
                <div class="display-6 fw-bold text-success">৳<?= number_format((float) ($grn['total_amount'] ?? 0), 2) ?></div>
                <div class="text-muted small mt-1">Total Amount</div>
                <hr>
                <div class="text-muted small"><?= count($grn['items'] ?? []) ?> line item(s)</div>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Received Items</h6>
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
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($grn['items'])): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $grandTotal = 0;
                                foreach ($grn['items'] as $i => $item):
                                    $lt = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0);
                                    $grandTotal += $lt;
                                ?>
                                    <tr>
                                        <td class="text-muted small"><?= $i + 1 ?></td>
                                        <td><code class="small"><?= sanitize($item['sku'] ?? '') ?></code></td>
                                        <td><?= sanitize($item['product_name'] ?? '') ?></td>
                                        <td class="text-end"><?= number_format((float) ($item['quantity'] ?? 0), 4) ?></td>
                                        <td class="text-end">৳<?= number_format((float) ($item['unit_cost'] ?? 0), 2) ?></td>
                                        <td class="text-end fw-semibold">৳<?= number_format($lt, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="5" class="text-end">Grand Total</td>
                                    <td class="text-end text-success">৳<?= number_format((float) ($grn['total_amount'] ?? $grandTotal), 2) ?></td>
                                </tr>
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
