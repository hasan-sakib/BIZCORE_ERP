<?php
$pageTitle   = 'Warehouses';
$breadcrumbs = ['Inventory' => null, 'Warehouses' => null];
$headerActions = '<a href="/inventory/warehouses/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>Add Warehouse
</a>';
ob_start();
?>

<!-- Search bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="/inventory/warehouses" class="row g-3">
            <div class="col-12 col-md-6">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search by name, code or location..."
                       value="<?= sanitize($search ?? '') ?>">
            </div>
            <div class="col-12 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Search
                </button>
                <a href="/inventory/warehouses" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Name</th>
                        <th>Code</th>
                        <th>Location</th>
                        <th>Manager</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($warehouses)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-warehouse fa-3x mb-3 d-block opacity-25"></i>
                                No warehouses found.
                                <a href="/inventory/warehouses/create" class="d-block mt-2">Add the first warehouse</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($warehouses as $wh): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <a href="/inventory/warehouses/<?= (int) $wh['id'] ?>" class="text-decoration-none">
                                        <?= sanitize($wh['name']) ?>
                                    </a>
                                </td>
                                <td><code class="small"><?= sanitize($wh['code']) ?></code></td>
                                <td class="small text-muted"><?= sanitize($wh['location'] ?? '—') ?></td>
                                <td class="small"><?= sanitize($wh['manager_name'] ?? '—') ?></td>
                                <td>
                                    <?php $statusClass = $wh['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= sanitize(ucfirst((string) $wh['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($wh['is_default']): ?>
                                        <span class="badge bg-primary">Default</span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/inventory/warehouses/<?= (int) $wh['id'] ?>"
                                           class="btn btn-outline-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/inventory/warehouses/<?= (int) $wh['id'] ?>/edit"
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                title="Delete"
                                                onclick="confirmDelete('/inventory/warehouses/<?= (int) $wh['id'] ?>')">
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

<?php if (!empty($warehouses)): ?>
    <div class="text-muted small mt-2">Total: <?= count($warehouses) ?> warehouse(s)</div>
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
