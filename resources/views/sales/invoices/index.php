<?php
$layout    = 'app';
$pageTitle = 'Invoices';
ob_start();

$statusColors = [
    'draft'     => 'secondary',
    'sent'      => 'primary',
    'partial'   => 'warning',
    'paid'      => 'success',
    'overdue'   => 'danger',
    'cancelled' => 'dark',
];
?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-mini bg-primary-soft">
            <div class="stat-label">Total</div>
            <div class="stat-value text-primary"><?= number_format((int)($summary['total_count'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini bg-success-soft">
            <div class="stat-label">Collected</div>
            <div class="stat-value text-success">৳<?= number_format((float)($summary['total_paid'] ?? 0), 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini bg-warning-soft">
            <div class="stat-label">Outstanding</div>
            <div class="stat-value text-warning">৳<?= number_format((float)($summary['outstanding'] ?? 0), 2) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini bg-info-soft">
            <div class="stat-label">Gross</div>
            <div class="stat-value text-info">৳<?= number_format((float)($summary['total_amount'] ?? 0), 2) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET" action="/invoices">
            <div class="col-12 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php foreach (array_keys($statusColors) as $s): ?>
                        <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <input type="date" name="from" class="form-control form-control-sm"
                       value="<?= $filters['from'] ?? '' ?>" placeholder="From">
            </div>
            <div class="col-12 col-md-2">
                <input type="date" name="to" class="form-control form-control-sm"
                       value="<?= $filters['to'] ?? '' ?>" placeholder="To">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="/invoices" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-file-invoice fa-3x mb-3 d-block opacity-25"></i>
                                No invoices found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                            <?php
                            $isOverdue = in_array($inv['status'], ['sent', 'partial'])
                                && $inv['due_date']
                                && $inv['due_date'] < date('Y-m-d');
                            $displayStatus = $isOverdue && $inv['status'] !== 'paid' ? 'overdue' : $inv['status'];
                            $colorClass    = $statusColors[$displayStatus] ?? 'secondary';
                            ?>
                            <tr class="<?= $isOverdue ? 'table-warning-subtle' : '' ?>">
                                <td>
                                    <a href="/invoices/<?= $inv['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($inv['invoice_number']) ?>
                                    </a>
                                </td>
                                <td><?= sanitize($inv['customer_name'] ?? '—') ?></td>
                                <td><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
                                <td class="<?= $isOverdue ? 'text-danger fw-semibold' : '' ?>">
                                    <?= $inv['due_date'] ? date('d M Y', strtotime($inv['due_date'])) : '—' ?>
                                </td>
                                <td class="text-end">৳<?= number_format((float)$inv['total_amount'], 2) ?></td>
                                <td class="text-end text-success">৳<?= number_format((float)$inv['paid_amount'], 2) ?></td>
                                <td class="text-end <?= (float)$inv['balance'] > 0 ? 'text-danger fw-semibold' : '' ?>">
                                    ৳<?= number_format((float)$inv['balance'], 2) ?>
                                </td>
                                <td><span class="badge bg-<?= $colorClass ?>"><?= ucfirst($displayStatus) ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/invoices/<?= $inv['id'] ?>" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (!in_array($inv['status'], ['paid', 'cancelled'])): ?>
                                            <a href="/invoices/<?= $inv['id'] ?>/payment" class="btn btn-outline-success" title="Record Payment">
                                                <i class="fas fa-dollar-sign"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?php include __DIR__ . '/../../components/pagination.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
