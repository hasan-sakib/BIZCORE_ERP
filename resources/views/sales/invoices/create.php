<?php
$pageTitle = 'New Invoice';
ob_start();

$errors = $errors ?? [];
$old    = $old ?? [];
?>

<div class="card shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0"><i class="fas fa-file-invoice me-2 text-primary"></i>New Invoice</h5>
    </div>
    <div class="card-body">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $field => $msgs): ?>
                        <?php foreach ((array) $msgs as $msg): ?>
                            <li><?= sanitize($msg) ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/sales/invoices" id="invoice-form">
            <?= csrf_field() ?>

            <!-- Header Fields -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" class="form-select <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= ((string) ($old['customer_id'] ?? '')) === ((string) $c['id']) ? 'selected' : '' ?>>
                                <?= sanitize($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['customer_id'])): ?>
                        <div class="invalid-feedback"><?= sanitize(implode(' ', (array) $errors['customer_id'])) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Issue Date <span class="text-danger">*</span></label>
                    <input type="date" name="issue_date" class="form-control <?= isset($errors['issue_date']) ? 'is-invalid' : '' ?>"
                           value="<?= sanitize($old['issue_date'] ?? date('Y-m-d')) ?>" required>
                    <?php if (isset($errors['issue_date'])): ?>
                        <div class="invalid-feedback"><?= sanitize(implode(' ', (array) $errors['issue_date'])) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>"
                           value="<?= sanitize($old['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>" required>
                    <?php if (isset($errors['due_date'])): ?>
                        <div class="invalid-feedback"><?= sanitize(implode(' ', (array) $errors['due_date'])) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Discount %</label>
                    <input type="number" name="discount" id="header-discount" class="form-control" step="0.01" min="0" max="100"
                           value="<?= sanitize((string) ($old['discount'] ?? '0')) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tax Rate %</label>
                    <input type="number" name="tax_rate" id="header-tax" class="form-control" step="0.01" min="0" max="100"
                           value="<?= sanitize((string) ($old['tax_rate'] ?? '0')) ?>">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= sanitize($old['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Line Items -->
            <h6 class="fw-semibold border-bottom pb-2 mb-3">Line Items</h6>
            <?php if (isset($errors['items'])): ?>
                <div class="alert alert-warning py-2"><?= sanitize(implode(' ', (array) $errors['items'])) ?></div>
            <?php endif; ?>

            <div class="table-responsive mb-2">
                <table class="table table-bordered align-middle" id="items-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:220px">Product</th>
                            <th>Description</th>
                            <th style="width:90px">Qty</th>
                            <th style="width:120px">Unit Price</th>
                            <th style="width:80px">Disc %</th>
                            <th style="width:120px" class="text-end">Line Total</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody id="items-container">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][product_id]" class="form-select form-select-sm product-select">
                                    <option value="">-- Product --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= (int) $p['id'] ?>" data-price="<?= (float) $p['selling_price'] ?>"
                                                data-name="<?= sanitize($p['name']) ?>">
                                            <?= sanitize($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="items[0][description]" class="form-control form-control-sm" placeholder="Description"></td>
                            <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm item-qty" step="0.0001" min="0" value="1"></td>
                            <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm item-price" step="0.0001" min="0" value="0"></td>
                            <td><input type="number" name="items[0][discount]" class="form-control form-control-sm item-disc" step="0.01" min="0" max="100" value="0"></td>
                            <td class="text-end"><span class="line-total fw-semibold">0.00</span></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary mb-4" id="add-item-btn">
                <i class="fas fa-plus me-1"></i>Add Item
            </button>

            <!-- Totals Summary -->
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted">Subtotal</td>
                            <td class="text-end fw-semibold" id="summary-subtotal">0.00</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Discount</td>
                            <td class="text-end text-danger" id="summary-discount">0.00</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tax</td>
                            <td class="text-end" id="summary-tax">0.00</td>
                        </tr>
                        <tr class="fw-bold border-top">
                            <td>Total</td>
                            <td class="text-end text-primary fs-5" id="summary-total">0.00</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="/sales/invoices" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';

    let rowIdx = 1;

    function calcLine(row) {
        const qty   = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const disc  = parseFloat(row.querySelector('.item-disc').value) || 0;
        const total = qty * price * (1 - disc / 100);
        row.querySelector('.line-total').textContent = total.toFixed(2);
        return total;
    }

    function recalcTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            subtotal += calcLine(row);
        });
        const disc    = parseFloat(document.getElementById('header-discount').value) || 0;
        const taxRate = parseFloat(document.getElementById('header-tax').value) || 0;
        const discAmt = subtotal * (disc / 100);
        const taxable = subtotal - discAmt;
        const taxAmt  = taxable * (taxRate / 100);
        const total   = taxable + taxAmt;

        document.getElementById('summary-subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('summary-discount').textContent = discAmt.toFixed(2);
        document.getElementById('summary-tax').textContent      = taxAmt.toFixed(2);
        document.getElementById('summary-total').textContent    = total.toFixed(2);
    }

    document.getElementById('add-item-btn').addEventListener('click', () => {
        const container = document.getElementById('items-container');
        const first     = container.querySelector('.item-row');
        const clone     = first.cloneNode(true);
        clone.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, '[' + rowIdx + ']');
            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = el.classList.contains('item-qty') ? '1' : '0';
            }
        });
        clone.querySelector('.line-total').textContent = '0.00';
        container.appendChild(clone);
        rowIdx++;
    });

    document.getElementById('items-container').addEventListener('click', e => {
        if (e.target.closest('.remove-item')) {
            if (document.querySelectorAll('.item-row').length > 1) {
                e.target.closest('.item-row').remove();
                recalcTotals();
            }
        }
    });

    document.getElementById('items-container').addEventListener('change', e => {
        const row = e.target.closest('.item-row');
        if (!row) return;

        if (e.target.classList.contains('product-select')) {
            const opt = e.target.options[e.target.selectedIndex];
            const price = parseFloat(opt.dataset.price) || 0;
            const name  = opt.dataset.name || '';
            row.querySelector('.item-price').value = price.toFixed(4);
            const descField = row.querySelector('[name*="[description]"]');
            if (descField && !descField.value) descField.value = name;
        }
        recalcTotals();
    });

    document.getElementById('items-container').addEventListener('input', e => {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price') || e.target.classList.contains('item-disc')) {
            recalcTotals();
        }
    });

    document.getElementById('header-discount').addEventListener('input', recalcTotals);
    document.getElementById('header-tax').addEventListener('input', recalcTotals);

    recalcTotals();
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
