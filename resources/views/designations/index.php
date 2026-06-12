<?php
$pageTitle = 'Designations';
ob_start();
?>

<!-- Filters -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3" method="GET" action="/hr/designations">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control"
                       placeholder="Search designation name..."
                       value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-4">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int) $dept['id'] ?>"
                            <?= ((int) ($filters['department_id'] ?? 0)) === (int) $dept['id'] ? 'selected' : '' ?>>
                            <?= sanitize($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="/hr/designations" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Department</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($designations)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="fas fa-briefcase fa-3x mb-3 d-block opacity-25"></i>
                                No designations found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($designations as $des): ?>
                            <tr>
                                <td class="fw-semibold"><?= sanitize($des['name']) ?></td>
                                <td><?= sanitize($des['department_name'] ?? '-') ?></td>
                                <td>
                                    <?php $cls = ($des['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary'; ?>
                                    <span class="badge <?= $cls ?>"><?= ucfirst(sanitize($des['status'] ?? 'inactive')) ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/hr/designations/<?= (int) $des['id'] ?>" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/hr/designations/<?= (int) $des['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" title="Delete"
                                                onclick="confirmDelete('/hr/designations/<?= (int) $des['id'] ?>')">
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
