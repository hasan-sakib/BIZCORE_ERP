<?php
$pageTitle = $pageTitle ?? 'Create Expense Category';
$errors    = $errors ?? [];
$old       = $old    ?? [];
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-tag me-2 text-primary"></i>New Expense Category</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/expenses/categories" novalidate>
                    <?= csrf_field() ?>

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($old['name'] ?? '') ?>"
                               maxlength="150"
                               placeholder="e.g. Office Supplies"
                               required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Color -->
                    <div class="mb-3">
                        <label for="color" class="form-label fw-semibold">Color</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="color"
                                   id="color"
                                   name="color"
                                   class="form-control form-control-color"
                                   value="<?= sanitize($old['color'] ?? '#6c757d') ?>"
                                   title="Pick a color for this category">
                            <span class="text-muted small">Used for badges and charts</span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description"
                                  name="description"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Optional description..."><?= sanitize($old['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="active"   <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($old['status'] ?? '')        === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create Category
                        </button>
                        <a href="/expenses/categories" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
