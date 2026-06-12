<?php
$pageTitle = 'Departments';
ob_start();
?>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3" method="GET" action="/hr/departments">
            <div class="col-12 col-md-5">
                <input type="text" name="search" class="form-control"
                       placeholder="Search name or code..."
                       value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active"   <?= ($filters['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="/hr/departments" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Head</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-sitemap fa-3x mb-3 d-block opacity-25"></i>
                                No departments found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($dept['name']) ?></td>
                                <td><code><?= sanitize($dept['code'] ?? '-') ?></code></td>
                                <td><?= sanitize($dept['head_name'] ?? '-') ?></td>
                                <td>
                                    <?php $cls = ($dept['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary'; ?>
                                    <span class="badge <?= $cls ?>"><?= ucfirst(sanitize($dept['status'] ?? 'inactive')) ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/hr/departments/<?= (int) $dept['id'] ?>" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/hr/departments/<?= (int) $dept['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" title="Delete"
                                                onclick="confirmDelete('/hr/departments/<?= (int) $dept['id'] ?>')">
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
