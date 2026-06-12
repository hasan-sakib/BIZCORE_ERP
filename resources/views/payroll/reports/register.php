<?php ob_start(); ?>
<div class="card shadow-sm"><div class="card-body text-center py-5 text-muted"><i class="fas fa-file-alt fa-4x mb-3 opacity-25"></i><h5>Payroll Register Report</h5><p>This report is being implemented.</p></div></div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php'; ?>
