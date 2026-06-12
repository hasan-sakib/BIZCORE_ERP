<?php
$pageTitle   = 'Purchase Order — ' . sanitize($order['po_number'] ?? '');
$breadcrumbs = ['Purchasing' => null, 'Orders' => '/purchasing/orders', sanitize($order['po_number'] ?? '') => null];
ob_start();
?>

<div class="row g-4">
    <!-- PO Header -->
    <div class="col-12 col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-shopping-cart me-2 text-primary"></i>Order Details</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (in_array($order['status'], ['draft', 'sent'])): ?>
                        <form method="POST" action="/purchasing/orders/<?= (int) $order['id'] ?>/approve" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-success btn-sm">
                                <i class="fas fa-check me-1"></i>Approve
                            </button>
                        </form>
                        <form method="POST" action="/purchasing/orders/<?= (int) $order['id'] ?>/cancel" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Cancel this order?')">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if (!in_array($order['status'], ['received', 'cancelled'])): ?>
                        <a href="/purchasing/orders/<?= (int) $order['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                    <?php endif; ?>
                    <a href="/purchasing/orders" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Reference</dt>
                    <dd class="col-sm-8 fw-semibold"><?= sanitize($order['po_number'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Supplier</dt>
                    <dd class="col-sm-8"><?= sanitize($order['supplier_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Order Date</dt>
                    <dd class="col-sm-8">
                        <?= !empty($order['order_date']) ? date('d M Y', strtotime($order['order_date'])) : '—' ?>
                    </dd>

                    <dt class="col-sm-4 text-muted">Expected Date</dt>
                    <dd class="col-sm-8">
                        <?= !empty($order['expected_date']) ? date('d M Y', strtotime($order['expected_date'])) : '—' ?>
                    </dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <?php
                        $sc = match ((string) ($order['status'] ?? '')) {
                            'draft'     => 'bg-secondary',
                            'sent'      => 'bg-info text-dark',
                            'approved'  => 'bg-primary',
                            'partial'   => 'bg-warning text-dark',
                            'received'  => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default     => 'bg-light text-dark',
                        };
                        ?>
                        <span class="badge <?= $sc ?>"><?= sanitize(ucfirst((string) ($order['status'] ?? ''))) ?></span>
                    </dd>

                    <?php if (!empty($order['notes'])): ?>
                        <dt class="col-sm-4 text-muted">Notes</dt>
                        <dd class="col-sm-8 small"><?= sanitize($order['notes']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Summary card -->
    <div class="col-12 col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-calculator me-2 text-info"></i>Financial Summary</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-semibold">৳<?= number_format((float) ($order['subtotal'] ?? 0), 2) ?></span>
                </div>
                <?php if (($order['discount_percent'] ?? 0) > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Discount (<?= number_format((float) $order['discount_percent'], 2) ?>%)</span>
                    <span class="text-danger">- ৳<?= number_format((float) ($order['discount_amount'] ?? 0), 2) ?></span>
                </div>
                <?php endif; ?>
                <?php if (($order['tax_percent'] ?? 0) > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tax (<?= number_format((float) $order['tax_percent'], 2) ?>%)</span>
                    <span>+ ৳<?= number_format((float) ($order['tax_amount'] ?? 0), 2) ?></span>
                </div>
                <?php endif; ?>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold fs-6">Grand Total</span>
                    <span class="fw-bold fs-5 text-success">৳<?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Line Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>SKU</th>
                                <th>Product</th>
                                <th>Description</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($order['items'])): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $subtotal = 0;
                                foreach ($order['items'] as $i => $item):
                                    $lt = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0);
                                    $subtotal += $lt;
                                ?>
                                    <tr>
                                        <td class="text-muted small"><?= $i + 1 ?></td>
                                        <td><code class="small"><?= sanitize($item['sku'] ?? '') ?></code></td>
                                        <td><?= sanitize($item['product_name'] ?? '') ?></td>
                                        <td class="small text-muted"><?= sanitize($item['description'] ?? '—') ?></td>
                                        <td class="text-end"><?= number_format((float) ($item['quantity'] ?? 0), 4) ?></td>
                                        <td class="text-end">৳<?= number_format((float) ($item['unit_cost'] ?? 0), 2) ?></td>
                                        <td class="text-end fw-semibold">৳<?= number_format($lt, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="6" class="text-end">Grand Total</td>
                                    <td class="text-end text-success">৳<?= number_format((float) ($order['total'] ?? $order['grand_total'] ?? $subtotal), 2) ?></td>
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
