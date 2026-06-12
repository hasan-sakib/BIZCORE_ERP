<?php ob_start(); ?>

<?php if ($flash = session()->getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-2"></i><?= sanitize($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash = session()->getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible"><i class="fas fa-exclamation-circle me-2"></i><?= sanitize($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" action="/accounting/accounts" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search accounts..." value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach (['asset', 'liability', 'equity', 'revenue', 'expense'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($filters['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="/accounting/accounts" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="accountsTable">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th>Normal Balance</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No accounts found.</td></tr>
                <?php else: ?>
                    <?php foreach ($accounts as $acc): ?>
                        <tr>
                            <td><code><?= sanitize($acc['code']) ?></code></td>
                            <td><?= sanitize($acc['name']) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst(sanitize($acc['type'])) ?></span></td>
                            <td><?= ucfirst(sanitize($acc['normal_balance'] ?? 'debit')) ?></td>
                            <td class="text-end">৳<?= number_format((float) $acc['balance'], 2) ?></td>
                            <td>
                                <?php if ($acc['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                                <?php if ($acc['is_system']): ?>
                                    <span class="badge bg-info ms-1">System</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="/accounting/accounts/<?= (int) $acc['id'] ?>" class="btn btn-outline-info btn-xs" title="View"><i class="fas fa-eye"></i></a>
                                <a href="/accounting/accounts/<?= (int) $acc['id'] ?>/edit" class="btn btn-outline-primary btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                                <?php if (!$acc['is_system']): ?>
                                    <button type="button" class="btn btn-outline-danger btn-xs"
                                            onclick="confirmDelete('/accounting/accounts/<?= (int) $acc['id'] ?>')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
