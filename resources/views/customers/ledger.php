<?php
$pageTitle = sanitize(($customer['name'] ?? 'Customer') . ' — Ledger');
ob_start();

$customer = $customer ?? [];
$id       = (int) ($customer['id'] ?? 0);
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm text-center py-5">
            <div class="card-body">
                <i class="fas fa-book fa-4x text-muted mb-4 opacity-50"></i>
                <h4 class="fw-semibold">Ledger Feature Coming Soon</h4>
                <p class="text-muted mt-2 mb-4">
                    The customer ledger for <strong><?= sanitize($customer['name'] ?? '') ?></strong>
                    will be available in a future release. It will show all transactions,
                    invoices, payments, and running balance.
                </p>
                <a href="/customers/<?= $id ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Customer
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
