<?php ob_start(); ?>
<div class="card shadow-sm"><div class="card-body text-center py-5 text-muted"><i class="fas fa-chart-pie fa-4x mb-3 opacity-25"></i><h5>Retained Earnings Statement</h5><p>Coming soon.</p></div></div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php'; ?>
