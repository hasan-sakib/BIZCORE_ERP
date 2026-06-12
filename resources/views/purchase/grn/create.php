<?php
$pageTitle   = 'New Goods Receipt Note';
$breadcrumbs = ['Purchasing' => null, 'GRN' => '/purchasing/grn', 'New' => null];
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

<form method="POST" action="/purchasing/grn" id="grnForm">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Header card -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-boxes me-2 text-success"></i>Receipt Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- PO Reference -->
                        <div class="col-md-4">
                            <label for="purchase_order_id" class="form-label fw-semibold">PO Reference</label>
                            <select id="purchase_order_id" name="purchase_order_id" class="form-select">
                                <option value="">— No PO —</option>
                                <?php foreach ($purchaseOrders ?? [] as $po): ?>
                                    <option value="<?= (int) $po['id'] ?>"
                                        <?= ($old['purchase_order_id'] ?? '') == $po['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($po['reference_no'] ?? '') ?>
                                        <?= !empty($po['supplier_name']) ? ' — ' . sanitize($po['supplier_name']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Supplier -->
                        <div class="col-md-4">
                            <label for="supplier_id" class="form-label fw-semibold">
                                Supplier <span class="text-danger">*</span>
                            </label>
                            <select id="supplier_id"
                                    name="supplier_id"
                                    class="form-select <?= !empty($errors['supplier_id']) ? 'is-invalid' : '' ?>"
                                    required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers ?? [] as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"
                                        <?= ($old['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['supplier_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['supplier_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Warehouse -->
                        <div class="col-md-4">
                            <label for="warehouse_id" class="form-label fw-semibold">
                                Warehouse <span class="text-danger">*</span>
                            </label>
                            <select id="warehouse_id"
                                    name="warehouse_id"
                                    class="form-select <?= !empty($errors['warehouse_id']) ? 'is-invalid' : '' ?>"
                                    required>
                                <option value="">Select Warehouse</option>
                                <?php foreach ($warehouses ?? [] as $wh): ?>
                                    <option value="<?= (int) $wh['id'] ?>"
                                        <?= ($old['warehouse_id'] ?? '') == $wh['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($wh['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['warehouse_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['warehouse_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Receipt Date -->
                        <div class="col-md-4">
                            <label for="receipt_date" class="form-label fw-semibold">
                                Receipt Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   id="receipt_date"
                                   name="receipt_date"
                                   class="form-control <?= !empty($errors['receipt_date']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['receipt_date'] ?? date('Y-m-d')) ?>"
                                   required>
                            <?php if (!empty($errors['receipt_date'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['receipt_date'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Notes -->
                        <div class="col-md-8">
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
                    <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Received Items</h6>
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
                        <div class="col-md-5"><span class="form-label fw-semibold small">Product</span></div>
                        <div class="col-md-2"><span class="form-label fw-semibold small">Quantity</span></div>
                        <div class="col-md-2"><span class="form-label fw-semibold small">Unit Cost (৳)</span></div>
                        <div class="col-md-2"><span class="form-label fw-semibold small">Line Total</span></div>
                        <div class="col-md-1"></div>
                    </div>

                    <div id="items-container">
                        <div class="item-row row g-2 mb-2 align-items-center" data-index="0">
                            <div class="col-md-5">
                                <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products ?? [] as $p): ?>
                                        <option value="<?= (int) $p['id'] ?>">
                                            <?= sanitize($p['name']) ?> (<?= sanitize($p['sku'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[0][quantity]"
                                       class="form-control form-control-sm qty-input"
                                       placeholder="Qty" min="0.0001" step="0.0001" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[0][unit_cost]"
                                       class="form-control form-control-sm cost-input"
                                       placeholder="0.00" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control form-control-sm line-total bg-light"
                                       placeholder="0.00" readonly>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mt-2">
                        <button type="button" id="add-row" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Add Item
                        </button>
                        <div class="ms-auto">
                            <span class="text-muted small me-2">Grand Total:</span>
                            <strong class="fs-5" id="grand-total">৳0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-1"></i>Save GRN
            </button>
            <a href="/purchasing/grn" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

<script>
let rowIdx = 1;

function calcRow(row) {
    const qty  = parseFloat(row.querySelector('.qty-input').value)  || 0;
    const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
    row.querySelector('.line-total').value = (qty * cost).toFixed(2);
    calcGrandTotal();
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('.line-total').forEach(function(el) {
        grand += parseFloat(el.value) || 0;
    });
    document.getElementById('grand-total').textContent = '৳' + grand.toFixed(2);
}

document.getElementById('add-row').addEventListener('click', function () {
    const container = document.getElementById('items-container');
    const clone     = container.querySelector('.item-row').cloneNode(true);
    clone.dataset.index = rowIdx;
    clone.querySelectorAll('[name]').forEach(function(el) {
        el.name  = el.name.replace(/\[0\]/, '[' + rowIdx + ']');
        el.value = '';
    });
    clone.querySelector('.line-total').value = '';
    container.appendChild(clone);
    rowIdx++;
});

document.getElementById('items-container').addEventListener('click', function (e) {
    if (e.target.closest('.remove-row')) {
        if (document.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
            calcGrandTotal();
        }
    }
});

document.getElementById('items-container').addEventListener('input', function (e) {
    const row = e.target.closest('.item-row');
    if (row && (e.target.classList.contains('qty-input') || e.target.classList.contains('cost-input'))) {
        calcRow(row);
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
