<?php ob_start(); ?>
<div class="card shadow-sm"><div class="card-body text-center py-4 text-muted"><p>Edit salary structure coming soon.</p><a href="/payroll/salary-structures/<?= (int) $structure['id'] ?>" class="btn btn-outline-secondary btn-sm">Go Back</a></div></div>
<?php $content = ob_get_clean(); include __DIR__ . '/../../layouts/app.php'; ?>
