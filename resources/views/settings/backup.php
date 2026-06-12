<?php ob_start(); ?>
<div class="card shadow-sm"><div class="card-body text-center py-5 text-muted"><i class="fas fa-database fa-4x mb-3 opacity-25"></i><h5>Backup & Restore</h5><p>Database backup feature coming soon.</p></div></div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
