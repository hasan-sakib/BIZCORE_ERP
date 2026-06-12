<?php
$pageTitle  = $pageTitle  ?? 'Edit Expense';
$errors     = $errors     ?? [];
$old        = $old        ?? [];
$expense    = $expense    ?? [];
$categories = $categories ?? [];
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-edit me-2 text-primary"></i>
                    Edit Expense
                    <span class="text-muted ms-1 fw-normal"><?= sanitize($expense['reference_no'] ?? '') ?></span>
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/expenses/<?= (int) $expense['id'] ?>" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row g-3">

                        <!-- Category -->
                        <div class="col-12 col-md-6">
                            <label for="category_id" class="form-label fw-semibold">
                                Category <span class="text-danger">*</span>
                            </label>
                            <select id="category_id"
                                    name="category_id"
                                    class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>"
                                    required>
                                <option value="">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php $selected = ($old['category_id'] ?? $expense['category_id'] ?? '') == $cat['id']; ?>
                                    <option value="<?= (int) $cat['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                                        <?= sanitize($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['category_id']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Amount -->
                        <div class="col-12 col-md-6">
                            <label for="amount" class="form-label fw-semibold">
                                Amount <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number"
                                       id="amount"
                                       name="amount"
                                       class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>"
                                       value="<?= sanitize($old['amount'] ?? $expense['amount'] ?? '') ?>"
                                       min="0.01"
                                       step="0.01"
                                       required>
                                <?php if (isset($errors['amount'])): ?>
                                    <div class="invalid-feedback"><?= sanitize($errors['amount']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-12 col-md-6">
                            <label for="date" class="form-label fw-semibold">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   id="date"
                                   name="date"
                                   class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['date'] ?? $expense['date'] ?? '') ?>"
                                   required>
                            <?php if (isset($errors['date'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['date']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Receipt Upload -->
                        <div class="col-12 col-md-6">
                            <label for="receipt" class="form-label fw-semibold">Receipt (optional)</label>
                            <input type="file"
                                   id="receipt"
                                   name="receipt"
                                   class="form-control"
                                   accept="image/*,.pdf">
                            <?php if (!empty($expense['receipt_path'])): ?>
                                <div class="form-text">
                                    Current: <a href="<?= sanitize($expense['receipt_path']) ?>" target="_blank">View receipt</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control"
                                      rows="3"><?= sanitize($old['description'] ?? $expense['description'] ?? '') ?></textarea>
                        </div>

                    </div><!-- /row -->

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                        <a href="/expenses/<?= (int) $expense['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
