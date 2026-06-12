<?php
$pageTitle = 'Quotation #' . sanitize($quotation['reference_no'] ?? '');
ob_start();

$statusColors = [
    'draft'    => 'secondary',
    'sent'     => 'info',
    'accepted' => 'success',
    'rejected' => 'danger',
    'expired'  => 'warning',
];
$statusClass = $statusColors[$quotation['status'] ?? ''] ?? 'secondary';
$isEditable  = in_array($quotation['status'] ?? '', ['draft', 'sent']);
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <a href="/sales/quotations" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Quotations
        </a>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($isEditable): ?>
            <a href="/sales/quotations/<?= (int)$quotation['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
        <?php endif; ?>

        <form method="POST" action="/sales/quotations/<?= (int)$quotation['id'] ?>/email" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-envelope me-1"></i>Send Email
            </button>
        </form>

        <?php if (in_array($quotation['status'] ?? '', ['sent', 'accepted'])): ?>
            <form method="POST" action="/sales/quotations/<?= (int)$quotation['id'] ?>/convert" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-sm"
                        onclick="return confirm('Convert this quotation to a sales order?')">
                    <i class="fas fa-exchange-alt me-1"></i>Convert to Order
                </button>
            </form>
        <?php endif; ?>

        <?php if ($isEditable): ?>
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="confirmDelete('/sales/quotations/<?= (int)$quotation['id'] ?>')">
                <i class="fas fa-trash me-1"></i>Delete
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Quotation Meta Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="text-muted fw-semibold text-uppercase mb-1" style="font-size:0.75rem;">Prepared For</h6>
                <h5 class="mb-1 fw-bold"><?= sanitize($quotation['customer_name'] ?? '—') ?></h5>
                <?php if (!empty($quotation['customer_email'])): ?>
                    <div class="text-muted"><i class="fas fa-envelope me-1 small"></i><?= sanitize($quotation['customer_email']) ?></div>
                <?php endif; ?>
                <?php if (!empty($quotation['customer_phone'])): ?>
                    <div class="text-muted"><i class="fas fa-phone me-1 small"></i><?= sanitize($quotation['customer_phone']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <h6 class="text-muted fw-semibold text-uppercase mb-1" style="font-size:0.75rem;">Quotation Details</h6>
                <div class="mb-1">
                    <span class="text-muted me-2">Reference #</span>
                    <strong><?= sanitize($quotation['reference_no'] ?? '—') ?></strong>
                </div>
                <div class="mb-1">
                    <span class="text-muted me-2">Issue Date</span>
                    <strong><?= !empty($quotation['issue_date']) ? date('d M Y', strtotime($quotation['issue_date'])) : '—' ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted me-2">Expiry Date</span>
                    <strong><?= !empty($quotation['expiry_date']) ? date('d M Y', strtotime($quotation['expiry_date'])) : '—' ?></strong>
                </div>
                <span class="badge bg-<?= $statusClass ?> fs-6 px-3 py-2"><?= ucfirst($quotation['status'] ?? '') ?></span>
            </div>
        </div>
        <?php if (!empty($quotation['notes'])): ?>
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted fw-semibold text-uppercase">Notes</small>
                <p class="mb-0 mt-1"><?= sanitize($quotation['notes']) ?></p>
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
                    <?php if (empty($quotation['items'])): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No items found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quotation['items'] as $item): ?>
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
                        <td class="text-end fw-semibold">৳<?= number_format((float)($quotation['subtotal'] ?? 0), 2) ?></td>
                    </tr>
                    <?php if (!empty($quotation['tax_rate']) && (float)$quotation['tax_rate'] > 0): ?>
                    <tr>
                        <td class="text-muted">Tax (<?= number_format((float)$quotation['tax_rate'], 2) ?>%)</td>
                        <td class="text-end">৳<?= number_format((float)($quotation['tax_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($quotation['discount']) && (float)$quotation['discount'] > 0): ?>
                    <tr>
                        <td class="text-muted">Discount (<?= number_format((float)$quotation['discount'], 2) ?>%)</td>
                        <td class="text-end text-danger">-৳<?= number_format((float)($quotation['discount_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-top fw-bold">
                        <td>Total</td>
                        <td class="text-end text-primary fs-5">৳<?= number_format((float)($quotation['total_amount'] ?? 0), 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
