<?php
$pageTitle   = 'Units of Measure';
$breadcrumbs = ['Inventory' => null, 'Units' => null];
$headerActions = '<a href="/products/units/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>Add Unit
</a>';
ob_start();
?>

<!-- Filter bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/products/units" class="row g-3">
            <div class="col-12 col-md-6">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search by name or symbol..."
                       value="<?= sanitize($search ?? '') ?>">
            </div>
            <div class="col-12 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="/products/units" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Symbol</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-ruler-combined fa-3x mb-3 d-block opacity-25"></i>
                                No units found.
                                <a href="/products/units/create" class="d-block mt-2">Add the first unit</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= sanitize($item['name']) ?></td>
                                <td><code class="small"><?= sanitize($item['symbol']) ?></code></td>
                                <td class="text-muted small"><?= sanitize($item['description'] ?? '—') ?></td>
                                <td>
                                    <?php if ($item['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/products/units/<?= (int) $item['id'] ?>/edit"
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                title="Delete"
                                                onclick="confirmDelete('/products/units/<?= (int) $item['id'] ?>')">
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
include __DIR__ . '/../../layouts/app.php';
