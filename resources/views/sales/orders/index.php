<?php
$pageTitle = 'Sales Orders';
ob_start();

$statusColors = [
    'pending'    => 'warning',
    'confirmed'  => 'info',
    'processing' => 'primary',
    'shipped'    => 'secondary',
    'delivered'  => 'success',
    'cancelled'  => 'danger',
];

$items    = $orders ?? [];
$total    = $pagination['total'] ?? 0;
$page     = $pagination['current_page'] ?? 1;
$lastPage = $pagination['total_pages'] ?? 1;
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2 text-primary"></i>Sales Orders</h4>
    <a href="/sales/orders/create" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>New Order
    </a>
</div>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET" action="/sales/orders">
            <div class="col-12 col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search reference..." value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"
                            <?= ((string)($filters['customer_id'] ?? '')) === ((string)$c['id']) ? 'selected' : '' ?>>
                            <?= sanitize($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
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
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="/sales/orders" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>Customer</th>
                        <th>Issue Date</th>
                        <th>Delivery Date</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-shopping-cart fa-3x mb-3 d-block opacity-25"></i>
                                No orders found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $order): ?>
                            <?php $colorClass = $statusColors[$order['status'] ?? ''] ?? 'secondary'; ?>
                            <tr>
                                <td>
                                    <a href="/sales/orders/<?= (int)$order['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= sanitize($order['reference_no'] ?? '—') ?>
                                    </a>
                                </td>
                                <td><?= sanitize($order['customer_name'] ?? '—') ?></td>
                                <td><?= !empty($order['issue_date']) ? date('d M Y', strtotime($order['issue_date'])) : '—' ?></td>
                                <td><?= !empty($order['delivery_date']) ? date('d M Y', strtotime($order['delivery_date'])) : '—' ?></td>
                                <td class="text-end fw-semibold">৳<?= number_format((float)($order['total_amount'] ?? 0), 2) ?></td>
                                <td><span class="badge bg-<?= $colorClass ?>"><?= ucfirst($order['status'] ?? '') ?></span></td>
                                <td class="text-end">
                                    <a href="/sales/orders/<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
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
