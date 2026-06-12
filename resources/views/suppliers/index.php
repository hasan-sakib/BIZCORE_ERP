<?php
$pageTitle = 'Suppliers';
ob_start();
?>

<!-- Filter Bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET" action="/suppliers">
            <div class="col-12 col-md-5">
                <input type="text" name="search" class="form-control"
                       placeholder="Search name, email, phone..."
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
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="/suppliers" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Payment Terms</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-truck fa-3x mb-3 d-block opacity-25"></i>
                                No suppliers found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm bg-warning-soft text-warning fw-bold rounded-circle
                                                    d-flex align-items-center justify-content-center">
                                            <?= strtoupper(substr((string) ($supplier['name'] ?? 'S'), 0, 2)) ?>
                                        </div>
                                        <div>
                                            <a href="/suppliers/<?= (int) $supplier['id'] ?>"
                                               class="fw-semibold text-decoration-none text-dark">
                                                <?= sanitize($supplier['name']) ?>
                                            </a>
                                            <?php if (!empty($supplier['city'])): ?>
                                                <div class="text-muted small"><?= sanitize($supplier['city']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= sanitize($supplier['email'] ?? '—') ?></td>
                                <td><?= sanitize($supplier['phone'] ?? '—') ?></td>
                                <td>
                                    <span class="text-muted small">
                                        <?= sanitize($supplier['payment_terms'] ?? '—') ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold
                                    <?= (float) ($supplier['balance'] ?? 0) > 0 ? 'text-danger' : '' ?>">
                                    <?= number_format((float) ($supplier['balance'] ?? 0), 2) ?>
                                </td>
                                <td>
                                    <?php $status = $supplier['status'] ?? 'inactive'; ?>
                                    <span class="badge <?= $status === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/suppliers/<?= (int) $supplier['id'] ?>"
                                           class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/suppliers/<?= (int) $supplier['id'] ?>/edit"
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" title="Delete"
                                                onclick="confirmDelete('/suppliers/<?= (int) $supplier['id'] ?>')">
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
    <?php if (!empty($pagination) && ($pagination['total_pages'] ?? 1) > 1): ?>
        <div class="card-footer">
            <?php include __DIR__ . '/../components/pagination.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
