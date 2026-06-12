<?php ob_start(); ?>

<?php if (!empty($errors['lines'])): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= sanitize($errors['lines']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-file-invoice me-2 text-primary"></i>New Journal Entry</h6></div>
    <div class="card-body">
        <form method="POST" action="/accounting/journals" id="journalForm">
            <?= csrf_field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>"
                           value="<?= sanitize($old['date'] ?? date('Y-m-d')) ?>" required>
                    <?php if (isset($errors['date'])): ?><div class="invalid-feedback"><?= sanitize($errors['date']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= sanitize($old['description'] ?? '') ?>" placeholder="Journal entry description...">
                </div>
            </div>

            <h6 class="fw-semibold mb-3 border-bottom pb-2">Journal Lines</h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="linesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40%">Account</th>
                            <th>Description</th>
                            <th style="width:15%">Debit (৳)</th>
                            <th style="width:15%">Credit (৳)</th>
                            <th style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody id="lineRows">
                        <tr class="line-row">
                            <td>
                                <select name="lines[0][account_id]" class="form-select form-select-sm account-select" required>
                                    <option value="">Select account...</option>
                                    <?php foreach ($accounts as $acc): ?>
                                        <option value="<?= (int) $acc['id'] ?>"><?= sanitize($acc['code'] . ' - ' . $acc['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="lines[0][description]" class="form-control form-control-sm" placeholder="Line description..."></td>
                            <td><input type="number" name="lines[0][debit]" class="form-control form-control-sm debit-field" value="0" min="0" step="0.01" oninput="updateTotals()"></td>
                            <td><input type="number" name="lines[0][credit]" class="form-control form-control-sm credit-field" value="0" min="0" step="0.01" oninput="updateTotals()"></td>
                            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-xs remove-line" onclick="removeLine(this)"><i class="fas fa-times"></i></button></td>
                        </tr>
                        <tr class="line-row">
                            <td>
                                <select name="lines[1][account_id]" class="form-select form-select-sm account-select" required>
                                    <option value="">Select account...</option>
                                    <?php foreach ($accounts as $acc): ?>
                                        <option value="<?= (int) $acc['id'] ?>"><?= sanitize($acc['code'] . ' - ' . $acc['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="lines[1][description]" class="form-control form-control-sm" placeholder="Line description..."></td>
                            <td><input type="number" name="lines[1][debit]" class="form-control form-control-sm debit-field" value="0" min="0" step="0.01" oninput="updateTotals()"></td>
                            <td><input type="number" name="lines[1][credit]" class="form-control form-control-sm credit-field" value="0" min="0" step="0.01" oninput="updateTotals()"></td>
                            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-xs remove-line" onclick="removeLine(this)"><i class="fas fa-times"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">Totals</td>
                            <td id="totalDebit">৳0.00</td>
                            <td id="totalCredit">৳0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-4" onclick="addLine()">
                <i class="fas fa-plus me-1"></i>Add Line
            </button>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Draft</button>
                <a href="/accounting/journals" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
let lineIndex = 2;
const accountOptions = `<?php foreach ($accounts as $acc): ?><option value="<?= (int) $acc['id'] ?>"><?= sanitize($acc['code'] . ' - ' . $acc['name']) ?></option><?php endforeach; ?>`;

function addLine() {
    const tbody = document.getElementById('lineRows');
    const i = lineIndex++;
    const tr = document.createElement('tr');
    tr.className = 'line-row';
    tr.innerHTML = `
        <td><select name="lines[${i}][account_id]" class="form-select form-select-sm account-select" required>
            <option value="">Select account...</option>${accountOptions}</select></td>
        <td><input type="text" name="lines[${i}][description]" class="form-control form-control-sm" placeholder="Line description..."></td>
        <td><input type="number" name="lines[${i}][debit]" class="form-control form-control-sm debit-field" value="0" min="0" step="0.01" oninput="updateTotals()"></td>
        <td><input type="number" name="lines[${i}][credit]" class="form-control form-control-sm credit-field" value="0" min="0" step="0.01" oninput="updateTotals()"></td>
        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-xs" onclick="removeLine(this)"><i class="fas fa-times"></i></button></td>`;
    tbody.appendChild(tr);
}

function removeLine(btn) {
    const rows = document.querySelectorAll('.line-row');
    if (rows.length > 2) { btn.closest('tr').remove(); updateTotals(); }
}

function updateTotals() {
    let debit = 0, credit = 0;
    document.querySelectorAll('.debit-field').forEach(f => debit += parseFloat(f.value) || 0);
    document.querySelectorAll('.credit-field').forEach(f => credit += parseFloat(f.value) || 0);
    document.getElementById('totalDebit').textContent = '৳' + debit.toFixed(2);
    document.getElementById('totalCredit').textContent = '৳' + credit.toFixed(2);
    const td = document.getElementById('totalDebit');
    const tc = document.getElementById('totalCredit');
    const balanced = Math.abs(debit - credit) < 0.01;
    td.className = balanced ? 'text-success fw-bold' : 'text-danger fw-bold';
    tc.className = balanced ? 'text-success fw-bold' : 'text-danger fw-bold';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
