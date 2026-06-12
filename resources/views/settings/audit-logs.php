<?php
$pageTitle = $pageTitle ?? 'Audit Logs';
$result    = $result    ?? ['items' => [], 'total' => 0, 'page' => 1, 'lastPage' => 1];
$filters   = $filters   ?? [];
$activeTab = $activeTab ?? 'audit-logs';
ob_start();
?>

<div class="row">
    <!-- Settings Sidebar Nav -->
    <div class="col-12 col-md-3 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Settings</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="/settings/general"
                   class="list-group-item list-group-item-action <?= $activeTab === 'general' ? 'active' : '' ?>">
                    <i class="fas fa-sliders-h me-2"></i>General
                </a>
                <a href="/settings/company"
                   class="list-group-item list-group-item-action <?= $activeTab === 'company' ? 'active' : '' ?>">
                    <i class="fas fa-building me-2"></i>Company
                </a>
                <a href="/settings/tax"
                   class="list-group-item list-group-item-action <?= $activeTab === 'tax' ? 'active' : '' ?>">
                    <i class="fas fa-percent me-2"></i>Tax &amp; VAT
                </a>
                <a href="/settings/email"
                   class="list-group-item list-group-item-action <?= $activeTab === 'email' ? 'active' : '' ?>">
                    <i class="fas fa-envelope me-2"></i>Email / SMTP
                </a>
                <a href="/settings/audit-logs"
                   class="list-group-item list-group-item-action <?= $activeTab === 'audit-logs' ? 'active' : '' ?>">
                    <i class="fas fa-history me-2"></i>Audit Logs
                </a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="col-12 col-md-9">
        <!-- Filter bar -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="/settings/audit-logs" class="row g-3">
                    <div class="col-12 col-md-3">
                        <input type="text" name="user" class="form-control"
                               value="<?= sanitize($filters['user'] ?? '') ?>"
                               placeholder="User name or email">
                    </div>
                    <div class="col-12 col-md-2">
                        <input type="text" name="action" class="form-control"
                               value="<?= sanitize($filters['action'] ?? '') ?>"
                               placeholder="Action">
                    </div>
                    <div class="col-12 col-md-2">
                        <input type="text" name="model_type" class="form-control"
                               value="<?= sanitize($filters['model_type'] ?? '') ?>"
                               placeholder="Model type">
                    </div>
                    <div class="col-12 col-md-2">
                        <input type="date" name="date_from" class="form-control"
                               value="<?= sanitize($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="/settings/audit-logs" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Audit Logs</h6>
                <?php if (!empty($pagination['total'])): ?>
                    <span class="text-muted small"><?= number_format($pagination['total']) ?> record(s)</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Model Type</th>
                                <th>Record ID</th>
                                <th>IP Address</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="fas fa-history fa-3x mb-3 d-block opacity-25"></i>
                                        No audit logs found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold small"><?= sanitize($log['user_name'] ?? '—') ?></div>
                                            <?php if (!empty($log['user_email'])): ?>
                                                <div class="text-muted" style="font-size:0.75rem"><?= sanitize($log['user_email']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $actionColor = match (strtolower((string) ($log['action'] ?? ''))) {
                                                'created', 'create' => 'bg-success',
                                                'updated', 'update' => 'bg-primary',
                                                'deleted', 'delete' => 'bg-danger',
                                                'login'             => 'bg-info text-dark',
                                                'logout'            => 'bg-secondary',
                                                default             => 'bg-light text-dark',
                                            };
                                            ?>
                                            <span class="badge <?= $actionColor ?>"><?= sanitize(ucfirst((string) ($log['action'] ?? ''))) ?></span>
                                        </td>
                                        <td class="small text-muted"><?= sanitize($log['model_type'] ?? '—') ?></td>
                                        <td class="small text-muted"><?= sanitize((string) ($log['record_id'] ?? '—')) ?></td>
                                        <td>
                                            <?php if (!empty($log['ip_address'])): ?>
                                                <code class="small"><?= sanitize($log['ip_address']) ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php if (!empty($log['created_at'])): ?>
                                                <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($pagination)): ?>
                <div class="card-footer">
                    <?php include __DIR__ . '/../components/pagination.php'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
