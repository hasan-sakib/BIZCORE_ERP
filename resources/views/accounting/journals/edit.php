<?php ob_start(); ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Only <strong>draft</strong> entries can be edited. Once posted, an entry can only be voided.
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-edit me-2 text-primary"></i>Edit Journal Entry #<?= sanitize($entry['entry_number']) ?></h6></div>
    <div class="card-body">
        <p class="text-muted">Edit functionality is coming soon. <a href="/accounting/journals/<?= (int) $entry['id'] ?>">Go back to view</a>.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
