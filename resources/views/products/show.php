<?php
$pageTitle   = $product['name'] ?? 'Product Details';
$breadcrumbs = ['Inventory' => null, 'Products' => '/products', $product['name'] ?? 'Details' => null];
$headerActions = '
<div class="d-flex gap-2">
    <a href="/products/' . (int) $product['id'] . '/edit" class="btn btn-primary btn-sm">
        <i class="fas fa-pencil-alt me-1"></i>Edit
    </a>
    <a href="/products/' . (int) $product['id'] . '/stock" class="btn btn-outline-info btn-sm">
        <i class="fas fa-warehouse me-1"></i>Stock Levels
    </a>
    <button type="button" class="btn btn-outline-danger btn-sm"
            onclick="confirmDelete(\'/products/' . (int) $product['id'] . '\')">
        <i class="fas fa-trash me-1"></i>Delete
    </button>
</div>';
ob_start();
?>

<div class="row g-4">
    <!-- Main info -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="fas fa-box me-2 text-primary"></i>Product Details</h6>
                <?php
                $statusClass = match ($product['status']) {
                    'active'       => 'bg-success',
                    'inactive'     => 'bg-secondary',
                    'discontinued' => 'bg-warning text-dark',
                    default        => 'bg-light text-dark',
                };
                ?>
                <span class="badge <?= $statusClass ?> fs-6"><?= sanitize(ucfirst($product['status'])) ?></span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">SKU</dt>
                    <dd class="col-sm-8"><code><?= sanitize($product['sku']) ?></code></dd>

                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8 fw-semibold"><?= sanitize($product['name']) ?></dd>

                    <dt class="col-sm-4 text-muted">Category</dt>
                    <dd class="col-sm-8"><?= sanitize($product['category_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Brand</dt>
                    <dd class="col-sm-8"><?= sanitize($product['brand_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4 text-muted">Unit</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($product['unit_name'])): ?>
                            <?= sanitize($product['unit_name']) ?>
                            <span class="text-muted small">(<?= sanitize($product['unit_symbol'] ?? '') ?>)</span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-muted">Type</dt>
                    <dd class="col-sm-8"><?= sanitize(ucfirst($product['type'] ?? 'standard')) ?></dd>

                    <?php if (!empty($product['description'])): ?>
                        <dt class="col-sm-4 text-muted">Description</dt>
                        <dd class="col-sm-8"><?= nl2br(sanitize($product['description'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Pricing -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-dollar-sign me-2 text-success"></i>Pricing</h6>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small mb-1">Cost Price</div>
                            <div class="fs-5 fw-bold">৳<?= number_format((float) $product['cost_price'], 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-primary-subtle">
                            <div class="text-muted small mb-1">Selling Price</div>
                            <div class="fs-5 fw-bold text-primary">৳<?= number_format((float) $product['selling_price'], 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small mb-1">Tax Rate</div>
                            <div class="fs-5 fw-bold"><?= number_format((float) $product['tax_rate'], 2) ?>%</div>
                        </div>
                    </div>
                </div>
                <?php
                $margin = (float) $product['selling_price'] - (float) $product['cost_price'];
                $marginPct = $product['cost_price'] > 0
                    ? ($margin / (float) $product['cost_price']) * 100
                    : 0;
                ?>
                <?php if ($product['cost_price'] > 0): ?>
                    <div class="mt-3 text-muted small text-center">
                        Margin: ৳<?= number_format($margin, 2) ?>
                        (<?= number_format($marginPct, 1) ?>%)
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($showStock)): ?>
        <!-- Per-warehouse stock levels -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-warehouse me-2 text-warning"></i>Stock by Warehouse</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Warehouse</th>
                                <th class="text-end">On Hand</th>
                                <th class="text-end">Reserved</th>
                                <th class="text-end">Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stockRows)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No warehouse stock data available.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stockRows as $row): ?>
                                    <tr>
                                        <td><?= sanitize($row['warehouse_name']) ?></td>
                                        <td class="text-end"><?= number_format((float) $row['quantity_on_hand'], 2) ?></td>
                                        <td class="text-end text-warning"><?= number_format((float) $row['quantity_reserved'], 2) ?></td>
                                        <td class="text-end fw-semibold <?= (float) $row['quantity_available'] <= 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= number_format((float) $row['quantity_available'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right sidebar -->
    <div class="col-12 col-lg-4">
        <!-- Stock thresholds -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-sliders-h me-2 text-warning"></i>Stock Thresholds</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted">Min Stock</dt>
                    <dd class="col-6 text-end"><?= number_format((int) $product['min_stock']) ?></dd>

                    <dt class="col-6 text-muted">Max Stock</dt>
                    <dd class="col-6 text-end"><?= number_format((int) $product['max_stock']) ?></dd>
                </dl>
            </div>
        </div>

        <!-- Timestamps -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-clock me-2 text-secondary"></i>Timestamps</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7"><?= formatDate($product['created_at'] ?? null, 'd/m/Y H:i') ?></dd>

                    <dt class="col-5 text-muted">Updated</dt>
                    <dd class="col-7"><?= formatDate($product['updated_at'] ?? null, 'd/m/Y H:i') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(url) {
    document.getElementById('deleteForm').action = url;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
