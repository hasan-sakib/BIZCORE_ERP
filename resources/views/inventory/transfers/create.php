<?php
$pageTitle   = 'New Stock Transfer';
$breadcrumbs = ['Inventory' => null, 'Transfers' => '/inventory/transfers', 'New' => null];
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Please fix the errors below:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $field => $messages): ?>
                <?php foreach ((array) $messages as $msg): ?>
                    <li><?= sanitize($msg) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" action="/inventory/transfers" id="transferForm">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Header card -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-exchange-alt me-2 text-primary"></i>Transfer Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- From Warehouse -->
                        <div class="col-md-4">
                            <label for="from_warehouse_id" class="form-label fw-semibold">
                                From Warehouse <span class="text-danger">*</span>
                            </label>
                            <select id="from_warehouse_id"
                                    name="from_warehouse_id"
                                    class="form-select <?= !empty($errors['from_warehouse_id']) ? 'is-invalid' : '' ?>"
                                    required>
                                <option value="">Select Source Warehouse</option>
                                <?php foreach ($warehouses ?? [] as $wh): ?>
                                    <option value="<?= (int) $wh['id'] ?>"
                                        <?= ($old['from_warehouse_id'] ?? '') == $wh['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($wh['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['from_warehouse_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['from_warehouse_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- To Warehouse -->
                        <div class="col-md-4">
                            <label for="to_warehouse_id" class="form-label fw-semibold">
                                To Warehouse <span class="text-danger">*</span>
                            </label>
                            <select id="to_warehouse_id"
                                    name="to_warehouse_id"
                                    class="form-select <?= !empty($errors['to_warehouse_id']) ? 'is-invalid' : '' ?>"
                                    required>
                                <option value="">Select Destination Warehouse</option>
                                <?php foreach ($warehouses ?? [] as $wh): ?>
                                    <option value="<?= (int) $wh['id'] ?>"
                                        <?= ($old['to_warehouse_id'] ?? '') == $wh['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($wh['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['to_warehouse_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['to_warehouse_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Date -->
                        <div class="col-md-4">
                            <label for="date" class="form-label fw-semibold">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   id="date"
                                   name="date"
                                   class="form-control <?= !empty($errors['date']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['date'] ?? date('Y-m-d')) ?>"
                                   required>
                            <?php if (!empty($errors['date'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['date'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2"
                                      placeholder="Optional notes..."><?= sanitize($old['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items card -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Items to Transfer</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors['items'])): ?>
                        <div class="alert alert-warning py-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <?= sanitize($errors['items'][0]) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Column headers -->
                    <div class="row g-2 mb-2 d-none d-md-flex">
                        <div class="col-md-8"><span class="form-label fw-semibold small">Product</span></div>
                        <div class="col-md-3"><span class="form-label fw-semibold small">Quantity</span></div>
                        <div class="col-md-1"></div>
                    </div>

                    <div id="items-container">
                        <div class="item-row row g-2 mb-2 align-items-center" data-index="0">
                            <div class="col-md-8">
                                <select name="items[0][product_id]" class="form-select form-select-sm" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products ?? [] as $p): ?>
                                        <option value="<?= (int) $p['id'] ?>">
                                            <?= sanitize($p['name']) ?> (<?= sanitize($p['sku'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="items[0][quantity]"
                                       class="form-control form-control-sm"
                                       placeholder="Qty" min="0.0001" step="0.0001" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        <button type="button" id="add-row" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Add Item
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Save Transfer
            </button>
            <a href="/inventory/transfers" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

<script>
let rowIdx = 1;

document.getElementById('add-row').addEventListener('click', function () {
    const container = document.getElementById('items-container');
    const clone     = container.querySelector('.item-row').cloneNode(true);
    clone.dataset.index = rowIdx;
    clone.querySelectorAll('[name]').forEach(function(el) {
        el.name  = el.name.replace(/\[0\]/, '[' + rowIdx + ']');
        el.value = '';
    });
    container.appendChild(clone);
    rowIdx++;
});

document.getElementById('items-container').addEventListener('click', function (e) {
    if (e.target.closest('.remove-row')) {
        if (document.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
