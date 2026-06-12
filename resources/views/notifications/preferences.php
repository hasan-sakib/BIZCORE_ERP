<?php ob_start(); ?>
<div class="card shadow-sm"><div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-cog me-2 text-primary"></i>Notification Preferences</h6></div><div class="card-body text-center py-5 text-muted"><i class="fas fa-bell fa-4x mb-3 opacity-25"></i><p>Notification preferences coming soon.</p></div></div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
