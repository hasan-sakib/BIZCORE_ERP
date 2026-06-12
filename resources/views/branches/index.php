<?php
$pageTitle = $pageTitle ?? 'Branches';
ob_start();
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">
            <i class="fas fa-building me-2 text-primary"></i>
            Branches
            <span class="badge bg-secondary ms-1"><?= count($branches) ?></span>
        </h6>
        <a href="/branches/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Add Branch
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($branches)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-building fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">No branches found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Branch Name</th>
                            <th>Code</th>
                            <th>Email / Phone</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= (int) $branch->id ?></td>
                                <td>
                                    <div class="fw-medium"><?= sanitize($branch->name) ?></div>
                                    <?php $addr = $branch->formattedAddress(); ?>
                                    <?php if ($addr): ?>
                                        <div class="text-muted small"><?= sanitize($addr) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="badge bg-secondary"><?= sanitize($branch->code) ?></code>
                                </td>
                                <td class="text-muted small">
                                    <?php if ($branch->email): ?>
                                        <div><?= sanitize($branch->email) ?></div>
                                    <?php endif; ?>
                                    <?php if ($branch->phone): ?>
                                        <div><?= sanitize($branch->phone) ?></div>
                                    <?php endif; ?>
                                    <?php if (!$branch->email && !$branch->phone): ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $branch->isActive() ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $branch->isActive() ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($branch->isHeadOffice()): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-star me-1"></i>Head Office
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Branch</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="/branches/<?= (int) $branch->id ?>"
                                           class="btn btn-outline-secondary btn-xs"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/branches/<?= (int) $branch->id ?>/edit"
                                           class="btn btn-outline-primary btn-xs"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($branch->isActive()): ?>
                                            <form method="POST" action="/branches/switch/<?= (int) $branch->id ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit"
                                                        class="btn btn-outline-info btn-xs"
                                                        title="Switch to this branch">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (!$branch->isHeadOffice()): ?>
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-xs"
                                                    title="Delete"
                                                    onclick="confirmDelete('/branches/<?= (int) $branch->id ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-xs"
                                                    disabled
                                                    title="Head office cannot be deleted">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
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
include __DIR__ . '/../layouts/app.php';
