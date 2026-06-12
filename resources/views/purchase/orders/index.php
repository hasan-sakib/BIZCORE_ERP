<?php
$pageTitle   = 'Purchase Orders';
$breadcrumbs = ['Purchasing' => null, 'Orders' => null];
$headerActions = '<a href="/purchasing/orders/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>New Order
</a>';
ob_start();
?>

<!-- Filter bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/purchasing/orders" class="row g-3">
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
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="draft"    <?= ($filters['status'] ?? '') === 'draft'    ? 'selected' : '' ?>>Draft</option>
                    <option value="sent"     <?= ($filters['status'] ?? '') === 'sent'     ? 'selected' : '' ?>>Sent</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="partial"  <?= ($filters['status'] ?? '') === 'partial'  ? 'selected' : '' ?>>Partial</option>
                    <option value="received" <?= ($filters['status'] ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
                    <option value="cancelled"<?= ($filters['status'] ?? '') === 'cancelled'? 'selected' : '' ?>>Cancelled</option>
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
                <a href="/purchasing/orders" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected Date</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-shopping-cart fa-3x mb-3 d-block opacity-25"></i>
                                No purchase orders found.
                                <a href="/purchasing/orders/create" class="d-block mt-2">Create the first one</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $sc = match ((string) ($order['status'] ?? '')) {
                                'draft'     => 'bg-secondary',
                                'sent'      => 'bg-info text-dark',
                                'approved'  => 'bg-primary',
                                'partial'   => 'bg-warning text-dark',
                                'received'  => 'bg-success',
                                'cancelled' => 'bg-danger',
                                default     => 'bg-light text-dark',
                            };
                            ?>
                            <tr>
                                <td>
                                    <a href="/purchasing/orders/<?= (int) $order['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($order['po_number'] ?? '') ?>
                                    </a>
                                </td>
                                <td class="small"><?= sanitize($order['supplier_name'] ?? '—') ?></td>
                                <td class="small">
                                    <?= !empty($order['order_date']) ? date('d M Y', strtotime($order['order_date'])) : '—' ?>
                                </td>
                                <td class="small">
                                    <?= !empty($order['expected_date']) ? date('d M Y', strtotime($order['expected_date'])) : '—' ?>
                                </td>
                                <td class="text-end">৳<?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></td>
                                <td><span class="badge <?= $sc ?>"><?= sanitize(ucfirst((string) ($order['status'] ?? ''))) ?></span></td>
                                <td class="text-end">
                                    <a href="/purchasing/orders/<?= (int) $order['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/purchasing/orders/<?= (int) $order['id'] ?>/edit"
                                       class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete('/purchasing/orders/<?= (int) $order['id'] ?>')"
                                            class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
