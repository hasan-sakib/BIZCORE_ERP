<?php
$pageTitle   = 'Stock Transfers';
$breadcrumbs = ['Inventory' => null, 'Transfers' => null];
$headerActions = '<a href="/inventory/transfers/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>New Transfer
</a>';
ob_start();
?>

<!-- Filter bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/inventory/transfers" class="row g-3">
            <div class="col-12 col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="draft"      <?= ($filters['status'] ?? '') === 'draft'      ? 'selected' : '' ?>>Draft</option>
                    <option value="confirmed"  <?= ($filters['status'] ?? '') === 'confirmed'  ? 'selected' : '' ?>>Confirmed</option>
                    <option value="in_transit" <?= ($filters['status'] ?? '') === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
                    <option value="received"   <?= ($filters['status'] ?? '') === 'received'   ? 'selected' : '' ?>>Received</option>
                    <option value="cancelled"  <?= ($filters['status'] ?? '') === 'cancelled'  ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <input type="date" name="date_from" class="form-control"
                       value="<?= sanitize($filters['date_from'] ?? '') ?>"
                       placeholder="From date">
            </div>
            <div class="col-12 col-md-2">
                <input type="date" name="date_to" class="form-control"
                       value="<?= sanitize($filters['date_to'] ?? '') ?>"
                       placeholder="To date">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="/inventory/transfers" class="btn btn-outline-secondary">Reset</a>
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
                        <th>From Warehouse</th>
                        <th>To Warehouse</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-exchange-alt fa-3x mb-3 d-block opacity-25"></i>
                                No transfers found.
                                <a href="/inventory/transfers/create" class="d-block mt-2">Create the first one</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($result['items'] as $transfer): ?>
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
                            <tr>
                                <td>
                                    <a href="/inventory/transfers/<?= (int) $transfer['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($transfer['reference_no'] ?? '') ?>
                                    </a>
                                </td>
                                <td class="small"><?= sanitize($transfer['from_warehouse_name'] ?? '—') ?></td>
                                <td class="small"><?= sanitize($transfer['to_warehouse_name'] ?? '—') ?></td>
                                <td class="small">
                                    <?php if (!empty($transfer['date'])): ?>
                                        <?= date('d M Y', strtotime($transfer['date'])) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $sc ?>"><?= sanitize($statusLabel) ?></span></td>
                                <td class="text-end">
                                    <a href="/inventory/transfers/<?= (int) $transfer['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View">
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

    <?php if (!empty($pagination)): ?>
        <div class="card-footer">
            <?php include __DIR__ . '/../../components/pagination.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($result['total'])): ?>
    <div class="text-muted small mt-2">Total: <?= number_format($result['total']) ?> record(s)</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
