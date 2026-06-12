<?php ob_start(); ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-arrow-up me-2"></i>Revenue</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($revenues as $acc): ?>
                            <tr>
                                <td><code><?= sanitize($acc['code']) ?></code> <?= sanitize($acc['name']) ?></td>
                                <td class="text-end">৳<?= number_format((float) $acc['balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-success fw-bold">
                        <tr>
                            <td>Total Revenue</td>
                            <td class="text-end">৳<?= number_format($totalRevenue, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-arrow-down me-2"></i>Expenses</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($expenses as $acc): ?>
                            <tr>
                                <td><code><?= sanitize($acc['code']) ?></code> <?= sanitize($acc['name']) ?></td>
                                <td class="text-end">৳<?= number_format((float) $acc['balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-danger fw-bold">
                        <tr>
                            <td>Total Expenses</td>
                            <td class="text-end">৳<?= number_format($totalExpense, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Net Income</h5>
            <h5 class="mb-0 <?= $netIncome >= 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                <?= $netIncome < 0 ? '-' : '' ?>৳<?= number_format(abs($netIncome), 2) ?>
            </h5>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
