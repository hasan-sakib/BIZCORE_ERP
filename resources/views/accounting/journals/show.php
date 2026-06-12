<?php ob_start();
$statusBadge = match ($entry['status']) {
    'posted'   => 'bg-success',
    'reversed' => 'bg-danger',
    default    => 'bg-warning text-dark',
};
?>

<?php if ($flash = session()->getFlash('success')): ?>
    <div class="alert alert-success alert-dismissible"><?= sanitize($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash = session()->getFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible"><?= sanitize($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-info-circle me-2 text-primary"></i>Entry Details</h6></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">Entry #</dt>
                    <dd class="col-sm-7 fw-bold"><?= sanitize($entry['entry_number']) ?></dd>

                    <dt class="col-sm-5 text-muted">Date</dt>
                    <dd class="col-sm-7"><?= sanitize($entry['date']) ?></dd>

                    <dt class="col-sm-5 text-muted">Status</dt>
                    <dd class="col-sm-7"><span class="badge <?= $statusBadge ?>"><?= ucfirst(sanitize($entry['status'])) ?></span></dd>

                    <dt class="col-sm-5 text-muted">Total Debit</dt>
                    <dd class="col-sm-7">৳<?= number_format((float) $entry['total_debit'], 2) ?></dd>

                    <dt class="col-sm-5 text-muted">Total Credit</dt>
                    <dd class="col-sm-7">৳<?= number_format((float) $entry['total_credit'], 2) ?></dd>

                    <?php if (!empty($entry['description'])): ?>
                        <dt class="col-sm-5 text-muted">Description</dt>
                        <dd class="col-sm-7"><?= sanitize($entry['description']) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-5 text-muted">Created By</dt>
                    <dd class="col-sm-7"><?= sanitize($entry['created_by_name'] ?? '—') ?></dd>
                </dl>
            </div>
            <?php if ($entry['status'] === 'draft'): ?>
                <div class="card-footer bg-white d-flex gap-2">
                    <form method="POST" action="/accounting/journals/<?= (int) $entry['id'] ?>/post">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Post this journal entry?')">
                            <i class="fas fa-check me-1"></i>Post Entry
                        </button>
                    </form>
                    <a href="/accounting/journals/<?= (int) $entry['id'] ?>/edit" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
                </div>
            <?php elseif ($entry['status'] === 'posted'): ?>
                <div class="card-footer bg-white">
                    <form method="POST" action="/accounting/journals/<?= (int) $entry['id'] ?>/void">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Void this journal entry? This cannot be undone.')">
                            <i class="fas fa-ban me-1"></i>Void Entry
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-list me-2 text-primary"></i>Journal Lines</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-end">Debit (৳)</th>
                            <th class="text-end">Credit (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entry['lines'] ?? [] as $line): ?>
                            <tr>
                                <td><code><?= sanitize($line['account_code']) ?></code> <?= sanitize($line['account_name']) ?></td>
                                <td><?= sanitize($line['description'] ?? '') ?></td>
                                <td class="text-end"><?= (float) $line['debit'] > 0 ? '৳' . number_format((float) $line['debit'], 2) : '—' ?></td>
                                <td class="text-end"><?= (float) $line['credit'] > 0 ? '৳' . number_format((float) $line['credit'], 2) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">Totals</td>
                            <td class="text-end">৳<?= number_format((float) $entry['total_debit'], 2) ?></td>
                            <td class="text-end">৳<?= number_format((float) $entry['total_credit'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
