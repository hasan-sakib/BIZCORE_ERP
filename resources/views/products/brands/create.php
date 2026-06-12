<?php
$pageTitle   = 'Add Brand';
$breadcrumbs = ['Inventory' => null, 'Brands' => '/products/brands', 'Add' => null];
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">

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

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-trademark me-2 text-primary"></i>New Brand</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/products/brands">
                    <?= csrf_field() ?>

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($old['name'] ?? '') ?>"
                               maxlength="100"
                               required>
                        <?php if (!empty($errors['name'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['name'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description"
                                  name="description"
                                  class="form-control"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Optional description..."><?= sanitize($old['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label for="status" class="form-label fw-semibold">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select id="status"
                                name="status"
                                class="form-select <?= !empty($errors['status']) ? 'is-invalid' : '' ?>">
                            <option value="active"   <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($old['status'] ?? '')       === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <?php if (!empty($errors['status'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['status'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Brand
                        </button>
                        <a href="/products/brands" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
