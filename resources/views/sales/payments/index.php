<?php
$pageTitle = 'Payments';
ob_start();

$methodColors = [
    'cash'           => 'success',
    'bank_transfer'  => 'primary',
    'cheque'         => 'info',
    'card'           => 'warning',
    'mobile_banking' => 'secondary',
];

$items    = $payments ?? [];
$total    = $pagination['total'] ?? 0;
$page     = $pagination['current_page'] ?? 1;
$lastPage = $pagination['total_pages'] ?? 1;
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fas fa-money-bill-wave me-2 text-success"></i>Payments</h4>
</div>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET" action="/sales/payments">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm text-muted">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= sanitize($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm text-muted">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= sanitize($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label form-label-sm text-muted">Method</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    <option value="cash"           <?= ($filters['method'] ?? '') === 'cash'           ? 'selected' : '' ?>>Cash</option>
                    <option value="bank_transfer"  <?= ($filters['method'] ?? '') === 'bank_transfer'  ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="cheque"         <?= ($filters['method'] ?? '') === 'cheque'         ? 'selected' : '' ?>>Cheque</option>
                    <option value="card"           <?= ($filters['method'] ?? '') === 'card'           ? 'selected' : '' ?>>Card</option>
                    <option value="mobile_banking" <?= ($filters['method'] ?? '') === 'mobile_banking' ? 'selected' : '' ?>>Mobile Banking</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="/sales/payments" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th class="text-end">Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-money-bill-wave fa-3x mb-3 d-block opacity-25"></i>
                                No payments found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $pay): ?>
                            <?php $methodColor = $methodColors[$pay['payment_method'] ?? ''] ?? 'secondary'; ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($pay['reference_no'] ?? '—') ?></td>
                                <td>
                                    <a href="/sales/invoices/<?= (int)($pay['invoice_id'] ?? 0) ?>" class="text-decoration-none">
                                        <?= sanitize($pay['invoice_number'] ?? '—') ?>
                                    </a>
                                </td>
                                <td><?= sanitize($pay['customer_name'] ?? '—') ?></td>
                                <td class="text-end fw-semibold text-success">৳<?= number_format((float)($pay['amount'] ?? 0), 2) ?></td>
                                <td><?= !empty($pay['payment_date']) ? date('d M Y', strtotime($pay['payment_date'])) : '—' ?></td>
                                <td>
                                    <span class="badge bg-<?= $methodColor ?>">
                                        <?= sanitize(ucwords(str_replace('_', ' ', $pay['payment_method'] ?? ''))) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="/sales/payments/<?= (int)$pay['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($lastPage > 1): ?>
    <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
        <small class="text-muted">
            Showing page <?= (int)$page ?> of <?= (int)$lastPage ?> (<?= number_format($total) ?> records)
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $page - 1])) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $p])) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $lastPage): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($filters ?? [], ['page' => $page + 1])) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
