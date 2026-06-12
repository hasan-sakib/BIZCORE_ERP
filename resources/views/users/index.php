<?php
$pageTitle = $pageTitle ?? 'Users';
ob_start();
?>

<!-- Filter Bar -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="/users" class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Name or email…"
                        value="<?= sanitize($filters['search'] ?? '') ?>"
                    >
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active"   <?= ($filters['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="locked"   <?= ($filters['status'] ?? '') === 'locked'   ? 'selected' : '' ?>>Locked</option>
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-3">
                <label class="form-label small text-muted mb-1">Role</label>
                <select name="role_id" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role->id ?>" <?= (string) ($filters['role_id'] ?? '') === (string) $role->id ? 'selected' : '' ?>>
                            <?= sanitize($role->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="/users" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">
            <i class="fas fa-users me-2 text-primary"></i>
            Users
            <span class="badge bg-secondary ms-1"><?= number_format($result->total) ?></span>
        </h6>
        <a href="/users/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Add User
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($result->items)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">No users found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result->items as $user): ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= (int) $user->id ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm flex-shrink-0">
                                            <?= strtoupper(substr($user->name, 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= sanitize($user->name) ?></div>
                                            <?php if ($user->phone): ?>
                                                <div class="text-muted small"><?= sanitize($user->phone) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted"><?= sanitize($user->email) ?></td>
                                <td>
                                    <?php
                                    $roleName = '';
                                    foreach ($roles as $r) {
                                        if ($r->id === $user->roleId) {
                                            $roleName = $r->name;
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-primary-soft text-primary">
                                        <?= sanitize($roleName ?: 'Unknown') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match ($user->status->value) {
                                        'active'   => 'bg-success',
                                        'inactive' => 'bg-secondary',
                                        'locked'   => 'bg-danger',
                                        default    => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= sanitize($user->status->label()) ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?= sanitize($user->createdAt->format('d M Y')) ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="/users/<?= (int) $user->id ?>"
                                           class="btn btn-outline-secondary btn-xs"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/users/<?= (int) $user->id ?>/edit"
                                           class="btn btn-outline-primary btn-xs"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user->status->value === 'active'): ?>
                                            <form method="POST" action="/users/<?= (int) $user->id ?>/toggle-status" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="inactive">
                                                <button type="submit"
                                                        class="btn btn-outline-warning btn-xs"
                                                        title="Deactivate"
                                                        onclick="return confirm('Deactivate this account?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="/users/<?= (int) $user->id ?>/toggle-status" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit"
                                                        class="btn btn-outline-success btn-xs"
                                                        title="Activate"
                                                        onclick="return confirm('Activate this account?')">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-xs"
                                                title="Delete"
                                                onclick="confirmDelete('/users/<?= (int) $user->id ?>')">
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
    <?php if ($result->lastPage() > 1): ?>
        <div class="card-footer">
            <?php
            $pagination = [
                'total'        => $result->total,
                'per_page'     => $result->perPage,
                'current_page' => $result->page,
                'total_pages'  => $result->lastPage(),
                'from'         => (($result->page - 1) * $result->perPage) + 1,
                'to'           => min($result->page * $result->perPage, $result->total),
            ];
            include __DIR__ . '/../../components/pagination.php';
            ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
