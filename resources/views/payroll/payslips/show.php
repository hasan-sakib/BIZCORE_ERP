<?php ob_start(); ?>
<div class="card shadow-sm"><div class="card-body text-center py-5 text-muted"><i class="fas fa-file-invoice fa-4x mb-3 opacity-25"></i><h5>Payslip #<?= (int) ($id ?? 0) ?></h5><p>Payslip details coming soon.</p></div></div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php'; ?>
