<?php
$pageTitle = 'Order #' . sanitize($order['reference_no'] ?? '');
ob_start();

$statusColors = [
    'pending'    => 'warning',
    'confirmed'  => 'info',
    'processing' => 'primary',
    'shipped'    => 'secondary',
    'delivered'  => 'success',
    'cancelled'  => 'danger',
];
$statusClass = $statusColors[$order['status'] ?? ''] ?? 'secondary';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <a href="/sales/orders" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Orders
        </a>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (in_array($order['status'] ?? '', ['pending', 'confirmed'])): ?>
            <?php if (($order['status'] ?? '') === 'pending'): ?>
                <form method="POST" action="/sales/orders/<?= (int)$order['id'] ?>/approve" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-check me-1"></i>Confirm Order
                    </button>
                </form>
            <?php endif; ?>
            <form method="POST" action="/sales/orders/<?= (int)$order['id'] ?>/cancel" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Cancel order? This action cannot be undone.')">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
            </form>
            <form method="POST" action="/sales/orders/<?= (int)$order['id'] ?>/invoice" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-file-invoice me-1"></i>Create Invoice
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Order Meta Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="text-muted fw-semibold text-uppercase mb-1" style="font-size:0.75rem;">Customer</h6>
                <h5 class="mb-1 fw-bold"><?= sanitize($order['customer_name'] ?? '—') ?></h5>
                <?php if (!empty($order['customer_email'])): ?>
                    <div class="text-muted"><i class="fas fa-envelope me-1 small"></i><?= sanitize($order['customer_email']) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['customer_phone'])): ?>
                    <div class="text-muted"><i class="fas fa-phone me-1 small"></i><?= sanitize($order['customer_phone']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <h6 class="text-muted fw-semibold text-uppercase mb-1" style="font-size:0.75rem;">Order Details</h6>
                <div class="mb-1">
                    <span class="text-muted me-2">Reference #</span>
                    <strong><?= sanitize($order['reference_no'] ?? '—') ?></strong>
                </div>
                <div class="mb-1">
                    <span class="text-muted me-2">Issue Date</span>
                    <strong><?= !empty($order['issue_date']) ? date('d M Y', strtotime($order['issue_date'])) : '—' ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted me-2">Delivery Date</span>
                    <strong><?= !empty($order['delivery_date']) ? date('d M Y', strtotime($order['delivery_date'])) : '—' ?></strong>
                </div>
                <span class="badge bg-<?= $statusClass ?> fs-6 px-3 py-2"><?= ucfirst($order['status'] ?? '') ?></span>
            </div>
        </div>
        <?php if (!empty($order['notes'])): ?>
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted fw-semibold text-uppercase">Notes</small>
                <p class="mb-0 mt-1"><?= sanitize($order['notes']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Line Items -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-list me-2 text-primary"></i>Items</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Description</th>
                        <th class="text-end" style="width:80px;">Qty</th>
                        <th class="text-end" style="width:130px;">Unit Price</th>
                        <th class="text-end" style="width:120px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($order['items'])): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No items found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($item['product_name'] ?? '—') ?></td>
                                <td class="text-muted"><?= sanitize($item['description'] ?? '') ?></td>
                                <td class="text-end"><?= number_format((float)($item['quantity'] ?? 0), 2) ?></td>
                                <td class="text-end">৳<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                                <td class="text-end fw-semibold">৳<?= number_format((float)($item['total'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary -->
<div class="row justify-content-end mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">Subtotal</td>
                        <td class="text-end fw-semibold">৳<?= number_format((float)($order['subtotal'] ?? 0), 2) ?></td>
                    </tr>
                    <?php if (!empty($order['tax_rate']) && (float)$order['tax_rate'] > 0): ?>
                    <tr>
                        <td class="text-muted">Tax (<?= number_format((float)$order['tax_rate'], 2) ?>%)</td>
                        <td class="text-end">৳<?= number_format((float)($order['tax_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($order['discount']) && (float)$order['discount'] > 0): ?>
                    <tr>
                        <td class="text-muted">Discount (<?= number_format((float)$order['discount'], 2) ?>%)</td>
                        <td class="text-end text-danger">-৳<?= number_format((float)($order['discount_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-top fw-bold">
                        <td>Total</td>
                        <td class="text-end text-primary fs-5">৳<?= number_format((float)($order['total_amount'] ?? 0), 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
