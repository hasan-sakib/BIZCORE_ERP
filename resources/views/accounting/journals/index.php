<?php ob_start(); ?>

<?php if ($flash = session()->getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle me-2"></i><?= sanitize($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash = session()->getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible"><i class="fas fa-exclamation-circle me-2"></i><?= sanitize($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" action="/accounting/journals" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by entry number or description..." value="<?= sanitize($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php foreach (['draft', 'posted', 'reversed'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="/accounting/journals" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Entry #</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $rows = $result['data'] ?? []; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No journal entries found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $entry): ?>
                        <tr>
                            <td><a href="/accounting/journals/<?= (int) $entry['id'] ?>" class="fw-semibold"><?= sanitize($entry['entry_number']) ?></a></td>
                            <td><?= sanitize($entry['date']) ?></td>
                            <td><?= sanitize(mb_substr($entry['description'] ?? '', 0, 60)) ?></td>
                            <td class="text-end">৳<?= number_format((float) $entry['total_debit'], 2) ?></td>
                            <td class="text-end">৳<?= number_format((float) $entry['total_credit'], 2) ?></td>
                            <td>
                                <?php
                                $statusBadge = match ($entry['status']) {
                                    'posted'   => 'bg-success',
                                    'reversed' => 'bg-danger',
                                    default    => 'bg-warning text-dark',
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= ucfirst(sanitize($entry['status'])) ?></span>
                            </td>
                            <td><?= sanitize($entry['created_by_name'] ?? '—') ?></td>
                            <td class="text-center">
                                <a href="/accounting/journals/<?= (int) $entry['id'] ?>" class="btn btn-outline-info btn-xs" title="View"><i class="fas fa-eye"></i></a>
                                <?php if ($entry['status'] === 'draft'): ?>
                                    <a href="/accounting/journals/<?= (int) $entry['id'] ?>/edit" class="btn btn-outline-primary btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($result['total'] ?? 0) > ($result['perPage'] ?? 20)): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($result['data']) ?> of <?= $result['total'] ?> entries</small>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
