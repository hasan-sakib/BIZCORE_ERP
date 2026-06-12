<?php
$breadcrumbs = ['Inventory' => null, 'Warehouses' => '/inventory/warehouses', sanitize($warehouse['name'] ?? '') => null];
$headerActions = '
<div class="btn-group btn-group-sm">
    <a href="/inventory/warehouses/' . (int) ($warehouse['id'] ?? 0) . '/edit" class="btn btn-outline-primary">
        <i class="fas fa-pencil-alt me-1"></i>Edit
    </a>
    <button type="button" class="btn btn-outline-danger"
            onclick="confirmDelete(\'/inventory/warehouses/' . (int) ($warehouse['id'] ?? 0) . '\')">
        <i class="fas fa-trash me-1"></i>Delete
    </button>
</div>';
ob_start();

$activeTab = $tab ?? 'details';
$whId      = (int) ($warehouse['id'] ?? 0);
?>

<!-- Tab nav -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'details' ? 'active' : '' ?>"
           href="/inventory/warehouses/<?= $whId ?>">
            <i class="fas fa-info-circle me-1"></i>Details
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'stock' ? 'active' : '' ?>"
           href="/inventory/warehouses/<?= $whId ?>/stock">
            <i class="fas fa-boxes me-1"></i>Stock Levels
        </a>
    </li>
</ul>

<?php if ($activeTab === 'details'): ?>

    <div class="row g-4">
        <div class="col-12 col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-warehouse me-2 text-primary"></i>Warehouse Information</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Name</dt>
                        <dd class="col-sm-8 fw-semibold"><?= sanitize($warehouse['name']) ?></dd>

                        <dt class="col-sm-4 text-muted">Code</dt>
                        <dd class="col-sm-8"><code><?= sanitize($warehouse['code']) ?></code></dd>

                        <dt class="col-sm-4 text-muted">Location</dt>
                        <dd class="col-sm-8"><?= sanitize($warehouse['location'] ?? '—') ?></dd>

                        <dt class="col-sm-4 text-muted">Manager</dt>
                        <dd class="col-sm-8"><?= sanitize($warehouse['manager_name'] ?? '—') ?></dd>

                        <dt class="col-sm-4 text-muted">Capacity</dt>
                        <dd class="col-sm-8">
                            <?= !empty($warehouse['capacity']) ? number_format((float) $warehouse['capacity'], 2) : '—' ?>
                        </dd>

                        <dt class="col-sm-4 text-muted">Status</dt>
                        <dd class="col-sm-8">
                            <?php $sc = ($warehouse['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary'; ?>
                            <span class="badge <?= $sc ?>"><?= sanitize(ucfirst((string) ($warehouse['status'] ?? ''))) ?></span>
                        </dd>

                        <dt class="col-sm-4 text-muted">Default</dt>
                        <dd class="col-sm-8">
                            <?php if ($warehouse['is_default'] ?? false): ?>
                                <span class="badge bg-primary">Yes</span>
                            <?php else: ?>
                                <span class="text-muted">No</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4 text-muted">Created</dt>
                        <dd class="col-sm-8 small text-muted"><?= sanitize($warehouse['created_at'] ?? '—') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2 text-info"></i>Quick Stats</h6>
                </div>
                <div class="card-body">
                    <?php $totalStock = 0; foreach ($stockLevels ?? [] as $sl) { $totalStock += (float) $sl['quantity']; } ?>
                    <div class="text-center py-3">
                        <div class="display-6 fw-bold text-primary"><?= number_format($totalStock, 2) ?></div>
                        <div class="text-muted small">Total Units in Stock</div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Distinct Products</span>
                        <span class="fw-semibold"><?= count($stockLevels ?? []) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: /* stock tab */ ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="fas fa-boxes me-2 text-warning"></i>Stock Levels</h6>
            <a href="/inventory/stock-in/create" class="btn btn-sm btn-outline-success">
                <i class="fas fa-plus me-1"></i>Stock In
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Product</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Reserved</th>
                            <th class="text-end">Available</th>
                            <th class="text-muted small">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stockLevels)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-boxes fa-3x mb-3 d-block opacity-25"></i>
                                    No stock levels recorded for this warehouse.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stockLevels as $sl): ?>
                                <tr>
                                    <td><code class="small"><?= sanitize($sl['sku'] ?? '') ?></code></td>
                                    <td><?= sanitize($sl['product_name'] ?? '') ?></td>
                                    <td class="text-end"><?= number_format((float) $sl['quantity'], 4) ?></td>
                                    <td class="text-end text-warning"><?= number_format((float) $sl['reserved_quantity'], 4) ?></td>
                                    <td class="text-end fw-semibold">
                                        <?php $avail = (float) $sl['available_quantity']; ?>
                                        <span class="<?= $avail <= 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= number_format($avail, 4) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= sanitize($sl['last_updated'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
function confirmDelete(url) {
    document.getElementById('deleteForm').action = url;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
