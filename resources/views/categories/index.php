<?php
$pageTitle   = 'Product Categories';
$breadcrumbs = ['Inventory' => null, 'Categories' => null];
$headerActions = '<a href="/products/categories/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>Add Category
</a>';
ob_start();
?>

<!-- Filter bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/products/categories" class="row g-3">
            <div class="col-12 col-md-6">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search by name..."
                       value="<?= sanitize($search ?? '') ?>">
            </div>
            <div class="col-12 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="/products/categories" class="btn btn-outline-secondary">Reset</a>
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
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-tags fa-3x mb-3 d-block opacity-25"></i>
                                No categories found.
                                <a href="/products/categories/create" class="d-block mt-2">Add the first category</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= sanitize($item['name']) ?></td>
                                <td><code class="small text-muted"><?= sanitize($item['slug']) ?></code></td>
                                <td class="text-muted small"><?= sanitize($item['description'] ?? '—') ?></td>
                                <td class="small"><?= sanitize($item['parent_name'] ?? '—') ?></td>
                                <td>
                                    <?php if ($item['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/products/categories/<?= (int) $item['id'] ?>/edit"
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                title="Delete"
                                                onclick="confirmDelete('/products/categories/<?= (int) $item['id'] ?>')">
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
