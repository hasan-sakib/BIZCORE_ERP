<?php
$pageTitle   = 'Stock Adjustments';
$breadcrumbs = ['Inventory' => null, 'Adjustments' => null];
$headerActions = '<a href="/inventory/adjustments/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>New Adjustment
</a>';
ob_start();
?>

<!-- Filter bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/inventory/adjustments" class="row g-3">
            <div class="col-12 col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending"  <?= ($filters['status'] ?? '') === 'pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <input type="date" name="date_from" class="form-control"
                       value="<?= sanitize($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-2">
                <input type="date" name="date_to" class="form-control"
                       value="<?= sanitize($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="/inventory/adjustments" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Warehouse</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['items'])): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-sliders-h fa-3x mb-3 d-block opacity-25"></i>
                                No adjustments found.
                                <a href="/inventory/adjustments/create" class="d-block mt-2">Create the first one</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($result['items'] as $adj): ?>
                            <?php
                            $sc = match ((string) ($adj['status'] ?? '')) {
                                'pending'  => 'bg-warning text-dark',
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default    => 'bg-light text-dark',
                            };
                            ?>
                            <tr>
                                <td>
                                    <a href="/inventory/adjustments/<?= (int) $adj['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($adj['reference_no'] ?? '') ?>
                                    </a>
                                </td>
                                <td class="small"><?= sanitize($adj['warehouse_name'] ?? '—') ?></td>
                                <td class="small"><?= sanitize($adj['reason'] ?? '—') ?></td>
                                <td class="small">
                                    <?php if (!empty($adj['date'])): ?>
                                        <?= date('d M Y', strtotime($adj['date'])) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $sc ?>"><?= sanitize(ucfirst((string) ($adj['status'] ?? ''))) ?></span></td>
                                <td class="text-end">
                                    <a href="/inventory/adjustments/<?= (int) $adj['id'] ?>"
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
