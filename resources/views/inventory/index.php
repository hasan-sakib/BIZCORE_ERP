<?php
$layout    = 'app';
$pageTitle = 'Inventory';
ob_start();
?>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3" method="GET" action="/inventory">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control"
                       placeholder="Search product name or SKU..."
                       value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filters['catId'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= sanitize($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="low_stock" value="1" id="lowStock"
                           <?= ($filters['lowStock'] ?? '') ? 'checked' : '' ?>>
                    <label class="form-check-label text-danger" for="lowStock">
                        <i class="fas fa-exclamation-triangle me-1"></i>Low Stock Only
                    </label>
                </div>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="/inventory" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="text-end">In Stock</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Avg Cost</th>
                        <th class="text-end">Stock Value</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fas fa-warehouse fa-3x mb-3 d-block opacity-25"></i>
                                No inventory records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="<?= $item['is_low_stock'] ? 'table-warning-subtle' : '' ?>">
                                <td class="fw-semibold"><?= sanitize($item['name']) ?></td>
                                <td><code class="small"><?= sanitize($item['sku']) ?></code></td>
                                <td><?= sanitize($item['category_name'] ?? '—') ?></td>
                                <td class="text-end"><?= number_format((float)$item['total_qty'], 2) ?></td>
                                <td class="text-end text-warning"><?= number_format((float)$item['reserved_qty'], 2) ?></td>
                                <td class="text-end fw-semibold"><?= number_format((float)$item['available_qty'], 2) ?></td>
                                <td class="text-end">৳<?= number_format((float)$item['avg_cost'], 2) ?></td>
                                <td class="text-end fw-semibold text-primary">৳<?= number_format((float)$item['stock_value'], 2) ?></td>
                                <td>
                                    <?php if ($item['is_low_stock']): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Low Stock
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/products/<?= $item['id'] ?>" class="btn btn-outline-primary" title="View Product">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/inventory/stock-in?product_id=<?= $item['id'] ?>"
                                           class="btn btn-outline-success" title="Stock In">
                                            <i class="fas fa-plus"></i>
                                        </a>
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
            <?php include __DIR__ . '/../components/pagination.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
