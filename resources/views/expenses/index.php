<?php
$pageTitle = $pageTitle ?? 'Expenses';
$filters   = $filters   ?? [];
$expenses  = $expenses  ?? [];
$categories = $categories ?? [];
ob_start();
?>

<!-- Filter Bar -->
<div class="card mb-4 shadow-sm">
    <div class="card-body py-3">
        <form method="GET" action="/expenses" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"
                            <?= ($filters['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                            <?= sanitize($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="pending"  <?= ($filters['status'] ?? '') === 'pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label form-label-sm mb-1">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= sanitize($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label form-label-sm mb-1">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= sanitize($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Ref / description..."
                       value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="/expenses" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Expense Table -->
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">
            <i class="fas fa-receipt me-2 text-primary"></i>
            Expenses
            <?php if (!empty($pagination)): ?>
                <span class="badge bg-secondary ms-1"><?= number_format($pagination['total']) ?></span>
            <?php endif; ?>
        </h6>
        <a href="/expenses/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>New Expense
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($expenses)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-receipt fa-3x mb-3 d-block opacity-25"></i>
                <p class="mb-1">No expenses found.</p>
                <a href="/expenses/create" class="btn btn-primary btn-sm mt-2">
                    <i class="fas fa-plus me-1"></i>Record First Expense
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Reference</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <?php
                            $statusBadge = match ($expense['status'] ?? 'pending') {
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default    => 'bg-warning text-dark',
                            };
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <a href="/expenses/<?= (int) $expense['id'] ?>" class="fw-medium text-decoration-none">
                                        <?= sanitize($expense['reference_no'] ?? '—') ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($expense['category_name'])): ?>
                                        <span class="badge"
                                              style="background:<?= sanitize($expense['category_color'] ?? '#6c757d') ?>">
                                            <?= sanitize($expense['category_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold">
                                    <?= number_format((float) ($expense['amount'] ?? 0), 2) ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $expense['date'] ? date('d M Y', strtotime($expense['date'])) : '—' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadge ?>">
                                        <?= ucfirst(sanitize($expense['status'] ?? 'pending')) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="/expenses/<?= (int) $expense['id'] ?>"
                                           class="btn btn-outline-secondary btn-sm"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (($expense['status'] ?? '') === 'pending'): ?>
                                            <a href="/expenses/<?= (int) $expense['id'] ?>/edit"
                                               class="btn btn-outline-primary btn-sm"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST"
                                                  action="/expenses/<?= (int) $expense['id'] ?>/approve"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Approve this expense?')">
                                                <?= csrf_field() ?>
                                                <button type="submit"
                                                        class="btn btn-outline-success btn-sm"
                                                        title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                title="Delete"
                                                onclick="confirmDelete('/expenses/<?= (int) $expense['id'] ?>')">
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
    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?php include __DIR__ . '/../components/pagination.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
