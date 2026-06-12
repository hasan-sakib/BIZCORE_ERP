<?php
$pageTitle   = 'Products';
$breadcrumbs = ['Inventory' => null, 'Products' => null];
$headerActions = '<a href="/products/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>Add Product
</a>';
ob_start();
?>

<!-- Filter bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/products" class="row g-3">
            <div class="col-12 col-md-4">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search name or SKU..."
                       value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories ?? [] as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"
                            <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= sanitize($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active"       <?= ($filters['status'] ?? '') === 'active'       ? 'selected' : '' ?>>Active</option>
                    <option value="inactive"     <?= ($filters['status'] ?? '') === 'inactive'     ? 'selected' : '' ?>>Inactive</option>
                    <option value="discontinued" <?= ($filters['status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="/products" class="btn btn-outline-secondary">Reset</a>
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
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th class="text-end">Selling Price</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($result['items'])): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-box-open fa-3x mb-3 d-block opacity-25"></i>
                                No products found.
                                <a href="/products/create" class="d-block mt-2">Add the first product</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($result['items'] as $product): ?>
                            <tr>
                                <td><code class="small"><?= sanitize($product['sku']) ?></code></td>
                                <td class="fw-semibold"><?= sanitize($product['name']) ?></td>
                                <td class="small"><?= sanitize($product['category_name'] ?? '—') ?></td>
                                <td class="small"><?= sanitize($product['brand_name'] ?? '—') ?></td>
                                <td class="text-end">৳<?= number_format((float) $product['selling_price'], 2) ?></td>
                                <td>
                                    <?php
                                    $statusClass = match ($product['status']) {
                                        'active'       => 'bg-success',
                                        'inactive'     => 'bg-secondary',
                                        'discontinued' => 'bg-warning text-dark',
                                        default        => 'bg-light text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= sanitize(ucfirst($product['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/products/<?= (int) $product['id'] ?>"
                                           class="btn btn-outline-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/products/<?= (int) $product['id'] ?>/edit"
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                title="Delete"
                                                onclick="confirmDelete('/products/<?= (int) $product['id'] ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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
            <?php include __DIR__ . '/../components/pagination.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($result['total'])): ?>
    <div class="text-muted small mt-2">
        Total: <?= number_format($result['total']) ?> product(s)
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
include __DIR__ . '/../layouts/app.php';
