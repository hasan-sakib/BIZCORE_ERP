<?php
$pageTitle  = $pageTitle  ?? 'Create Expense';
$errors     = $errors     ?? [];
$old        = $old        ?? [];
$categories = $categories ?? [];
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-receipt me-2 text-primary"></i>New Expense</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/expenses" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>

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
                                    <option value="<?= (int) $cat['id'] ?>"
                                        <?= ($old['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
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
                                       value="<?= sanitize($old['amount'] ?? '') ?>"
                                       min="0.01"
                                       step="0.01"
                                       placeholder="0.00"
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
                                   value="<?= sanitize($old['date'] ?? date('Y-m-d')) ?>"
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
                            <div class="form-text">Upload a photo or PDF of the receipt.</div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Brief description of the expense..."><?= sanitize($old['description'] ?? '') ?></textarea>
                        </div>

                    </div><!-- /row -->

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Expense
                        </button>
                        <a href="/expenses" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
