<?php ob_start(); ?>

<?php if ($flash = session()->getFlash('success')): ?><div class="alert alert-success alert-dismissible"><?= sanitize($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($flash = session()->getFlash('error')): ?><div class="alert alert-danger alert-dismissible"><?= sanitize($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Employee #</th>
                    <th class="text-end">Basic Salary (৳)</th>
                    <th class="text-end">Gross Salary (৳)</th>
                    <th class="text-end">Net Salary (৳)</th>
                    <th>Effective Date</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($structures)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No salary structures found.</td></tr>
                <?php else: ?>
                    <?php foreach ($structures as $s): ?>
                        <tr>
                            <td><?= sanitize($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td><code><?= sanitize($s['employee_number']) ?></code></td>
                            <td class="text-end">৳<?= number_format((float) $s['basic_salary'], 2) ?></td>
                            <td class="text-end">৳<?= number_format((float) $s['gross_salary'], 2) ?></td>
                            <td class="text-end">৳<?= number_format((float) $s['net_salary'], 2) ?></td>
                            <td><?= sanitize($s['effective_date']) ?></td>
                            <td class="text-center">
                                <a href="/payroll/salary-structures/<?= (int) $s['id'] ?>" class="btn btn-outline-info btn-xs"><i class="fas fa-eye"></i></a>
                                <a href="/payroll/salary-structures/<?= (int) $s['id'] ?>/edit" class="btn btn-outline-primary btn-xs"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php'; ?>
