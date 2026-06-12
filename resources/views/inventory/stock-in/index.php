<?php
$pageTitle   = 'Stock In';
$breadcrumbs = ['Inventory' => null, 'Stock In' => null];
$headerActions = '<a href="/inventory/stock-in/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>New Stock In
</a>';
ob_start();
?>

<!-- Filter bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/inventory/stock-in" class="row g-3">
            <div class="col-12 col-md-3">
                <select name="warehouse_id" class="form-select">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses ?? [] as $wh): ?>
                        <option value="<?= (int) $wh['id'] ?>"
                            <?= ($filters['warehouse_id'] ?? '') == $wh['id'] ? 'selected' : '' ?>>
                            <?= sanitize($wh['name']) ?>
                        </option>
                    <?php endforeach; ?>
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
                <a href="/inventory/stock-in" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Supplier</th>
                        <th>Date</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['items'])): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-arrow-down fa-3x mb-3 d-block opacity-25"></i>
                                No stock-in orders found.
                                <a href="/inventory/stock-in/create" class="d-block mt-2">Create the first one</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($result['items'] as $order): ?>
                            <tr>
                                <td>
                                    <a href="/inventory/stock-in/<?= (int) $order['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($order['reference_no']) ?>
                                    </a>
                                </td>
                                <td class="small"><?= sanitize($order['warehouse_name'] ?? '—') ?></td>
                                <td class="small"><?= sanitize($order['supplier_name'] ?? '—') ?></td>
                                <td class="small"><?= sanitize($order['date']) ?></td>
                                <td class="text-end">৳<?= number_format((float) $order['total_amount'], 2) ?></td>
                                <td>
                                    <?php
                                    $sc = match ((string) ($order['status'] ?? '')) {
                                        'confirmed' => 'bg-success',
                                        'draft'     => 'bg-secondary',
                                        default     => 'bg-light text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $sc ?>"><?= sanitize(ucfirst((string) ($order['status'] ?? ''))) ?></span>
                                </td>
                                <td class="text-end">
                                    <a href="/inventory/stock-in/<?= (int) $order['id'] ?>"
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

    <?php if (!empty($pagination) && ($pagination['total_pages'] ?? 0) > 1): ?>
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
