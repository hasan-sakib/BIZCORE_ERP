<?php
$pageTitle   = 'Edit Purchase Order — ' . sanitize($order['reference_no'] ?? '');
$breadcrumbs = ['Purchasing' => null, 'Orders' => '/purchasing/orders', sanitize($order['reference_no'] ?? '') => '/purchasing/orders/' . (int) ($order['id'] ?? 0), 'Edit' => null];
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

<form method="POST" action="/purchasing/orders/<?= (int) ($order['id'] ?? 0) ?>" id="poForm">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="row g-4">
        <!-- Header card -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-shopping-cart me-2 text-primary"></i>Order Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
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
                                        <?= ($old['supplier_id'] ?? $order['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['supplier_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['supplier_id'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Date -->
                        <div class="col-md-4">
                            <label for="order_date" class="form-label fw-semibold">
                                Order Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   id="order_date"
                                   name="order_date"
                                   class="form-control <?= !empty($errors['order_date']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['order_date'] ?? $order['order_date'] ?? '') ?>"
                                   required>
                            <?php if (!empty($errors['order_date'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['order_date'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Expected Date -->
                        <div class="col-md-4">
                            <label for="expected_date" class="form-label fw-semibold">Expected Date</label>
                            <input type="date"
                                   id="expected_date"
                                   name="expected_date"
                                   class="form-control"
                                   value="<?= sanitize($old['expected_date'] ?? $order['expected_date'] ?? '') ?>">
                        </div>

                        <!-- Discount -->
                        <div class="col-md-3">
                            <label for="discount_percent" class="form-label fw-semibold">Discount %</label>
                            <input type="number"
                                   id="discount_percent"
                                   name="discount_percent"
                                   class="form-control"
                                   value="<?= sanitize($old['discount_percent'] ?? $order['discount_percent'] ?? '0') ?>"
                                   min="0" max="100" step="0.01">
                        </div>

                        <!-- Tax -->
                        <div class="col-md-3">
                            <label for="tax_percent" class="form-label fw-semibold">Tax %</label>
                            <input type="number"
                                   id="tax_percent"
                                   name="tax_percent"
                                   class="form-control"
                                   value="<?= sanitize($old['tax_percent'] ?? $order['tax_percent'] ?? '0') ?>"
                                   min="0" max="100" step="0.01">
                        </div>

                        <!-- Notes -->
                        <div class="col-md-6">
                            <label for="notes" class="form-label fw-semibold">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2"
                                      placeholder="Optional notes..."><?= sanitize($old['notes'] ?? $order['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items card -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Line Items</h6>
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
                        <div class="col-md-3"><span class="form-label fw-semibold small">Product</span></div>
                        <div class="col-md-3"><span class="form-label fw-semibold small">Description</span></div>
                        <div class="col-md-2"><span class="form-label fw-semibold small">Quantity</span></div>
                        <div class="col-md-2"><span class="form-label fw-semibold small">Unit Cost (৳)</span></div>
                        <div class="col-md-1"><span class="form-label fw-semibold small">Line Total</span></div>
                        <div class="col-md-1"></div>
                    </div>

                    <div id="items-container">
                        <?php
                        $existingItems = $items ?? [];
                        if (empty($existingItems)):
                        ?>
                        <div class="item-row row g-2 mb-2 align-items-center" data-index="0">
                            <div class="col-md-3">
                                <select name="items[0][product_id]" class="form-select form-select-sm" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products ?? [] as $p): ?>
                                        <option value="<?= (int) $p['id'] ?>"><?= sanitize($p['name']) ?> (<?= sanitize($p['sku'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="items[0][description]" class="form-control form-control-sm" placeholder="Description (optional)">
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[0][quantity]" class="form-control form-control-sm qty-input" placeholder="Qty" min="0.0001" step="0.0001" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[0][unit_cost]" class="form-control form-control-sm cost-input" placeholder="0.00" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-1">
                                <input type="text" class="form-control form-control-sm line-total bg-light" placeholder="0.00" readonly>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($existingItems as $idx => $item): ?>
                        <div class="item-row row g-2 mb-2 align-items-center" data-index="<?= $idx ?>">
                            <?php if (!empty($item['id'])): ?>
                                <input type="hidden" name="items[<?= $idx ?>][id]" value="<?= (int) $item['id'] ?>">
                            <?php endif; ?>
                            <div class="col-md-3">
                                <select name="items[<?= $idx ?>][product_id]" class="form-select form-select-sm" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products ?? [] as $p): ?>
                                        <option value="<?= (int) $p['id'] ?>"
                                            <?= ($item['product_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                            <?= sanitize($p['name']) ?> (<?= sanitize($p['sku'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="items[<?= $idx ?>][description]" class="form-control form-control-sm"
                                       value="<?= sanitize($item['description'] ?? '') ?>" placeholder="Description (optional)">
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[<?= $idx ?>][quantity]" class="form-control form-control-sm qty-input"
                                       value="<?= sanitize($item['quantity'] ?? '') ?>" placeholder="Qty" min="0.0001" step="0.0001" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[<?= $idx ?>][unit_cost]" class="form-control form-control-sm cost-input"
                                       value="<?= sanitize($item['unit_cost'] ?? '') ?>" placeholder="0.00" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-1">
                                <?php $lt = (float)($item['quantity'] ?? 0) * (float)($item['unit_cost'] ?? 0); ?>
                                <input type="text" class="form-control form-control-sm line-total bg-light"
                                       value="<?= number_format($lt, 2) ?>" readonly>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="mt-2">
                        <button type="button" id="add-row" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Add Item
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary card -->
        <div class="col-12 col-md-5 ms-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-calculator me-2 text-info"></i>Order Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span id="summary-subtotal" class="fw-semibold">৳0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Discount (<span id="summary-disc-pct">0</span>%)</span>
                        <span id="summary-discount" class="text-danger">- ৳0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tax (<span id="summary-tax-pct">0</span>%)</span>
                        <span id="summary-tax">+ ৳0.00</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Grand Total</span>
                        <span id="summary-grand" class="fw-bold fs-5 text-success">৳0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Update Purchase Order
            </button>
            <a href="/purchasing/orders/<?= (int) ($order['id'] ?? 0) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

<script>
let rowIdx = <?= max(count($items ?? []), 1) ?>;

function calcSummary() {
    let subtotal = 0;
    document.querySelectorAll('.line-total').forEach(function(el) {
        subtotal += parseFloat(el.value) || 0;
    });

    const discPct  = parseFloat(document.getElementById('discount_percent').value) || 0;
    const taxPct   = parseFloat(document.getElementById('tax_percent').value)      || 0;
    const discount = subtotal * discPct / 100;
    const taxable  = subtotal - discount;
    const tax      = taxable * taxPct / 100;
    const grand    = taxable + tax;

    document.getElementById('summary-subtotal').textContent  = '৳' + subtotal.toFixed(2);
    document.getElementById('summary-disc-pct').textContent  = discPct.toFixed(2);
    document.getElementById('summary-discount').textContent  = '- ৳' + discount.toFixed(2);
    document.getElementById('summary-tax-pct').textContent   = taxPct.toFixed(2);
    document.getElementById('summary-tax').textContent       = '+ ৳' + tax.toFixed(2);
    document.getElementById('summary-grand').textContent     = '৳' + grand.toFixed(2);
}

function calcRow(row) {
    const qty  = parseFloat(row.querySelector('.qty-input').value)  || 0;
    const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
    row.querySelector('.line-total').value = (qty * cost).toFixed(2);
    calcSummary();
}

document.addEventListener('DOMContentLoaded', calcSummary);

document.getElementById('add-row').addEventListener('click', function () {
    const container = document.getElementById('items-container');
    const template  = container.querySelector('.item-row');
    const clone     = template.cloneNode(true);
    clone.dataset.index = rowIdx;
    clone.querySelectorAll('[name]').forEach(function(el) {
        el.name  = el.name.replace(/\[\d+\]/, '[' + rowIdx + ']');
        el.value = '';
    });
    clone.querySelector('.line-total').value = '';
    clone.querySelectorAll('input[type=hidden]').forEach(function(el) { el.remove(); });
    container.appendChild(clone);
    rowIdx++;
});

document.getElementById('items-container').addEventListener('click', function (e) {
    if (e.target.closest('.remove-row')) {
        if (document.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
            calcSummary();
        }
    }
});

document.getElementById('items-container').addEventListener('input', function (e) {
    const row = e.target.closest('.item-row');
    if (row && (e.target.classList.contains('qty-input') || e.target.classList.contains('cost-input'))) {
        calcRow(row);
    }
});

document.getElementById('discount_percent').addEventListener('input', calcSummary);
document.getElementById('tax_percent').addEventListener('input', calcSummary);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
