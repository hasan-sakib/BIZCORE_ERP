<?php
$pageTitle = $pageTitle ?? 'Expense Categories';
ob_start();
?>

<!-- Search bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body py-3">
        <form method="GET" action="/expenses/categories" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="text" name="search" class="form-control"
                       placeholder="Search categories..."
                       value="<?= sanitize($search ?? '') ?>">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Search
                </button>
                <a href="/expenses/categories" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">
            <i class="fas fa-tags me-2 text-primary"></i>
            Expense Categories
            <span class="badge bg-secondary ms-1"><?= count($categories) ?></span>
        </h6>
        <a href="/expenses/categories/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>New Category
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($categories)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-tags fa-3x mb-3 d-block opacity-25"></i>
                <p class="mb-1">No expense categories found.</p>
                <a href="/expenses/categories/create" class="btn btn-primary btn-sm mt-2">
                    <i class="fas fa-plus me-1"></i>Create First Category
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Name</th>
                            <th>Color</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= (int) $cat['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="color-dot rounded-circle d-inline-block"
                                              style="width:14px;height:14px;background:<?= sanitize($cat['color'] ?? '#6c757d') ?>;flex-shrink:0;"></span>
                                        <span class="fw-medium"><?= sanitize($cat['name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge"
                                          style="background:<?= sanitize($cat['color'] ?? '#6c757d') ?>">
                                        <?= sanitize($cat['color'] ?? '#6c757d') ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?= $cat['description'] ? sanitize(mb_strimwidth($cat['description'], 0, 80, '…')) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= ($cat['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst(sanitize($cat['status'] ?? 'inactive')) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="/expenses/categories/<?= (int) $cat['id'] ?>/edit"
                                           class="btn btn-outline-primary btn-sm"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                title="Delete"
                                                onclick="confirmDelete('/expenses/categories/<?= (int) $cat['id'] ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
