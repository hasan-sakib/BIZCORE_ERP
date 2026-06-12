<?php
$pageTitle = 'Invoice #' . sanitize($invoice['reference_no'] ?? '');
ob_start();

$statusClass = [
    'draft'   => 'secondary',
    'sent'    => 'info',
    'paid'    => 'success',
    'partial' => 'warning',
    'overdue' => 'danger',
    'void'    => 'dark',
][$invoice['status'] ?? ''] ?? 'secondary';

$isPaidOrVoid = in_array($invoice['status'] ?? '', ['paid', 'void']);
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <a href="/sales/invoices" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Invoices
        </a>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!$isPaidOrVoid): ?>
            <a href="/sales/invoices/<?= (int)$invoice['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
            <form method="POST" action="/sales/invoices/<?= (int)$invoice['id'] ?>/email" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-envelope me-1"></i>Email
                </button>
            </form>
            <form method="POST" action="/sales/invoices/<?= (int)$invoice['id'] ?>/void" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-dark"
                        onclick="return confirm('Void this invoice? This cannot be undone.')">
                    <i class="fas fa-ban me-1"></i>Void
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Invoice Meta Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="text-muted fw-semibold text-uppercase mb-1" style="font-size:0.75rem;">Bill To</h6>
                <h5 class="mb-1 fw-bold"><?= sanitize($invoice['customer_name'] ?? '—') ?></h5>
                <?php if (!empty($invoice['customer_email'])): ?>
                    <div class="text-muted"><i class="fas fa-envelope me-1 small"></i><?= sanitize($invoice['customer_email']) ?></div>
                <?php endif; ?>
                <?php if (!empty($invoice['customer_phone'])): ?>
                    <div class="text-muted"><i class="fas fa-phone me-1 small"></i><?= sanitize($invoice['customer_phone']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <h6 class="text-muted fw-semibold text-uppercase mb-1" style="font-size:0.75rem;">Invoice Details</h6>
                <div class="mb-1">
                    <span class="text-muted me-2">Invoice #</span>
                    <strong><?= sanitize($invoice['reference_no'] ?? '—') ?></strong>
                </div>
                <div class="mb-1">
                    <span class="text-muted me-2">Issue Date</span>
                    <strong><?= !empty($invoice['issue_date']) ? date('d M Y', strtotime($invoice['issue_date'])) : '—' ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted me-2">Due Date</span>
                    <strong><?= !empty($invoice['due_date']) ? date('d M Y', strtotime($invoice['due_date'])) : '—' ?></strong>
                </div>
                <span class="badge bg-<?= $statusClass ?> fs-6 px-3 py-2"><?= ucfirst($invoice['status'] ?? '') ?></span>
            </div>
        </div>
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
                        <th class="text-end" style="width:120px;">Unit Price</th>
                        <th class="text-end" style="width:90px;">Discount</th>
                        <th class="text-end" style="width:120px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoice['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No items found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoice['items'] as $item): ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($item['product_name'] ?? '—') ?></td>
                                <td class="text-muted"><?= sanitize($item['description'] ?? '') ?></td>
                                <td class="text-end"><?= number_format((float)($item['quantity'] ?? 0), 2) ?></td>
                                <td class="text-end">৳<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                                <td class="text-end"><?= number_format((float)($item['discount'] ?? 0), 2) ?>%</td>
                                <td class="text-end fw-semibold">৳<?= number_format((float)($item['total'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary + Payment side by side -->
<div class="row g-4 mb-4">
    <div class="col-md-5 offset-md-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">Subtotal</td>
                        <td class="text-end fw-semibold">৳<?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?></td>
                    </tr>
                    <?php if (!empty($invoice['tax_rate']) && (float)$invoice['tax_rate'] > 0): ?>
                    <tr>
                        <td class="text-muted">Tax (<?= number_format((float)$invoice['tax_rate'], 2) ?>%)</td>
                        <td class="text-end">৳<?= number_format((float)($invoice['tax_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($invoice['discount']) && (float)$invoice['discount'] > 0): ?>
                    <tr>
                        <td class="text-muted">Discount (<?= number_format((float)$invoice['discount'], 2) ?>%)</td>
                        <td class="text-end text-danger">-৳<?= number_format((float)($invoice['discount_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-top fw-bold">
                        <td>Total</td>
                        <td class="text-end text-primary fs-5">৳<?= number_format((float)($invoice['total_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Paid</td>
                        <td class="text-end text-success">৳<?= number_format((float)($invoice['paid_amount'] ?? 0), 2) ?></td>
                    </tr>
                    <tr class="border-top">
                        <td class="fw-bold">Balance Due</td>
                        <?php $balance = (float)($invoice['total_amount'] ?? 0) - (float)($invoice['paid_amount'] ?? 0); ?>
                        <td class="text-end fw-bold fs-5 <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                            ৳<?= number_format($balance, 2) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Payment History -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-history me-2 text-success"></i>Payment History</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoice['payments'])): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No payments recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoice['payments'] as $pay): ?>
                            <tr>
                                <td><?= !empty($pay['payment_date']) ? date('d M Y', strtotime($pay['payment_date'])) : '—' ?></td>
                                <td><span class="badge bg-secondary"><?= sanitize(ucwords(str_replace('_', ' ', $pay['payment_method'] ?? ''))) ?></span></td>
                                <td class="text-end fw-semibold text-success">৳<?= number_format((float)($pay['amount'] ?? 0), 2) ?></td>
                                <td><?= sanitize($pay['reference'] ?? '—') ?></td>
                                <td class="text-muted"><?= sanitize($pay['notes'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Record Payment Form -->
<?php if (!$isPaidOrVoid): ?>
<div class="card shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-plus-circle me-2 text-primary"></i>Record Payment</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="/sales/invoices/<?= (int)$invoice['id'] ?>/payment">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                               value="<?= number_format($balance, 2, '.', '') ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="card">Card</option>
                        <option value="mobile_banking">Mobile Banking</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Reference</label>
                    <input type="text" name="reference" class="form-control" placeholder="Transaction ID / Cheque #">
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-check me-1"></i>Record Payment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
