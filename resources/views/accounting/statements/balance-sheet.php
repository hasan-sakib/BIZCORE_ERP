<?php ob_start(); ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><h6 class="mb-0 fw-semibold">Assets</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($assets as $acc): ?>
                            <tr>
                                <td><code><?= sanitize($acc['code']) ?></code> <?= sanitize($acc['name']) ?></td>
                                <td class="text-end">৳<?= number_format((float) $acc['balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-primary fw-bold">
                        <tr><td>Total Assets</td><td class="text-end">৳<?= number_format($totalAssets, 2) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark"><h6 class="mb-0 fw-semibold">Liabilities</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($liabilities as $acc): ?>
                            <tr>
                                <td><code><?= sanitize($acc['code']) ?></code> <?= sanitize($acc['name']) ?></td>
                                <td class="text-end">৳<?= number_format((float) $acc['balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white"><h6 class="mb-0 fw-semibold">Equity</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($equity as $acc): ?>
                            <tr>
                                <td><code><?= sanitize($acc['code']) ?></code> <?= sanitize($acc['name']) ?></td>
                                <td class="text-end">৳<?= number_format((float) $acc['balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-info fw-bold">
                        <tr><td>Total Liab. + Equity</td><td class="text-end">৳<?= number_format($totalLiabEquity, 2) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
