<?php
$pageTitle = 'Payment #' . sanitize($payment['reference_no'] ?? '');
ob_start();

$methodColors = [
    'cash'           => 'success',
    'bank_transfer'  => 'primary',
    'cheque'         => 'info',
    'card'           => 'warning',
    'mobile_banking' => 'secondary',
];
$methodColor = $methodColors[$payment['payment_method'] ?? ''] ?? 'secondary';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <a href="/sales/payments" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Payments
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-receipt me-2 text-success"></i>Payment Receipt
                </h5>
                <span class="badge bg-<?= $methodColor ?> fs-6 px-3 py-2">
                    <?= sanitize(ucwords(str_replace('_', ' ', $payment['payment_method'] ?? ''))) ?>
                </span>
            </div>
            <div class="card-body">

                <!-- Amount highlight -->
                <div class="text-center py-4 mb-3 bg-success-subtle rounded">
                    <div class="text-muted small mb-1">Amount Received</div>
                    <div class="fw-bold text-success" style="font-size: 2.5rem;">
                        ৳<?= number_format((float)($payment['amount'] ?? 0), 2) ?>
                    </div>
                </div>

                <!-- Details table -->
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted fw-semibold" style="width:40%;">Reference No</td>
                            <td class="fw-bold"><?= sanitize($payment['reference_no'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Invoice</td>
                            <td>
                                <a href="/sales/invoices/<?= (int)($payment['invoice_id'] ?? 0) ?>" class="text-decoration-none fw-semibold">
                                    <?= sanitize($payment['invoice_number'] ?? '—') ?>
                                    <i class="fas fa-external-link-alt ms-1 small"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Customer</td>
                            <td><?= sanitize($payment['customer_name'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Payment Date</td>
                            <td><?= !empty($payment['payment_date']) ? date('d M Y', strtotime($payment['payment_date'])) : '—' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Method</td>
                            <td>
                                <span class="badge bg-<?= $methodColor ?>">
                                    <?= sanitize(ucwords(str_replace('_', ' ', $payment['payment_method'] ?? ''))) ?>
                                </span>
                            </td>
                        </tr>
                        <?php if (!empty($payment['reference'])): ?>
                        <tr>
                            <td class="text-muted fw-semibold">Transaction Ref.</td>
                            <td><?= sanitize($payment['reference']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($payment['notes'])): ?>
                        <tr>
                            <td class="text-muted fw-semibold">Notes</td>
                            <td class="text-muted"><?= sanitize($payment['notes']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($payment['created_at'])): ?>
                        <tr>
                            <td class="text-muted fw-semibold">Recorded At</td>
                            <td class="text-muted small"><?= date('d M Y, H:i', strtotime($payment['created_at'])) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="/sales/invoices/<?= (int)($payment['invoice_id'] ?? 0) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-file-invoice me-1"></i>View Invoice
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
