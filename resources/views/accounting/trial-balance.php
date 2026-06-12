<?php ob_start(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-balance-scale me-2 text-primary"></i>Trial Balance</h6>
        <small class="text-muted">As of <?= date('d M Y') ?></small>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th class="text-end">Debit (৳)</th>
                    <th class="text-end">Credit (৳)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $acc): ?>
                    <?php if ((float) $acc['balance'] == 0) continue; ?>
                    <tr>
                        <td><code><?= sanitize($acc['code']) ?></code></td>
                        <td><?= sanitize($acc['name']) ?></td>
                        <td><span class="badge bg-secondary"><?= ucfirst($acc['type']) ?></span></td>
                        <td class="text-end"><?= $acc['normal_balance'] === 'debit' ? '৳' . number_format((float) $acc['balance'], 2) : '—' ?></td>
                        <td class="text-end"><?= $acc['normal_balance'] === 'credit' ? '৳' . number_format((float) $acc['balance'], 2) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">Totals</td>
                    <td class="text-end <?= abs($totalDebit - $totalCredit) < 0.01 ? 'text-success' : 'text-danger' ?>">৳<?= number_format($totalDebit, 2) ?></td>
                    <td class="text-end <?= abs($totalDebit - $totalCredit) < 0.01 ? 'text-success' : 'text-danger' ?>">৳<?= number_format($totalCredit, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php if (abs($totalDebit - $totalCredit) > 0.01): ?>
        <div class="card-footer bg-warning-subtle">
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
            Trial balance is <strong>not balanced</strong>. Difference: ৳<?= number_format(abs($totalDebit - $totalCredit), 2) ?>
        </div>
    <?php else: ?>
        <div class="card-footer bg-success-subtle text-success">
            <i class="fas fa-check-circle me-2"></i>Trial balance is balanced.
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
