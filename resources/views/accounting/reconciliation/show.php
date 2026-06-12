<?php ob_start(); ?>
<div class="card shadow-sm"><div class="card-body text-center py-5 text-muted"><h5>Reconciliation #<?= (int) ($id ?? 0) ?></h5><p>Details coming soon.</p></div></div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php'; ?>
