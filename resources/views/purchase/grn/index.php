<?php
$pageTitle   = 'Goods Receipt Notes';
$breadcrumbs = ['Purchasing' => null, 'GRN' => null];
$headerActions = '<a href="/purchasing/grn/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>New GRN
</a>';
ob_start();
?>

<!-- Filter bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/purchasing/grn" class="row g-3">
            <div class="col-12 col-md-3">
                <select name="supplier_id" class="form-select">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers ?? [] as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"
                            <?= ($filters['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= sanitize($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
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
                <a href="/purchasing/grn" class="btn btn-outline-secondary">Reset</a>
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
                        <th>PO Reference</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>Date</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receipts)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-boxes fa-3x mb-3 d-block opacity-25"></i>
                                No goods receipt notes found.
                                <a href="/purchasing/grn/create" class="d-block mt-2">Create the first one</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($receipts as $grn): ?>
                            <?php
                            $sc = match ((string) ($grn['status'] ?? '')) {
                                'draft'    => 'bg-secondary',
                                'received' => 'bg-success',
                                'partial'  => 'bg-warning text-dark',
                                default    => 'bg-light text-dark',
                            };
                            ?>
                            <tr>
                                <td>
                                    <a href="/purchasing/grn/<?= (int) $grn['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($grn['grn_number'] ?? '') ?>
                                    </a>
                                </td>
                                <td class="small">
                                    <?php if (!empty($grn['po_reference'])): ?>
                                        <a href="/purchasing/orders/<?= (int) ($grn['po_id'] ?? 0) ?>" class="text-decoration-none">
                                            <?= sanitize($grn['po_reference']) ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= sanitize($grn['supplier_name'] ?? '—') ?></td>
                                <td class="small"><?= sanitize($grn['warehouse_name'] ?? '—') ?></td>
                                <td class="small">
                                    <?= !empty($grn['receipt_date']) ? date('d M Y', strtotime($grn['receipt_date'])) : '—' ?>
                                </td>
                                <td class="text-end">৳<?= number_format((float) ($grn['total_amount'] ?? 0), 2) ?></td>
                                <td><span class="badge <?= $sc ?>"><?= sanitize(ucfirst((string) ($grn['status'] ?? ''))) ?></span></td>
                                <td class="text-end">
                                    <a href="/purchasing/grn/<?= (int) $grn['id'] ?>"
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

<?php if (!empty($pagination['total'])): ?>
    <div class="text-muted small mt-2">Total: <?= number_format($pagination['total']) ?> record(s)</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
