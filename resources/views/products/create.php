<?php
$pageTitle   = 'Add Product';
$breadcrumbs = ['Inventory' => null, 'Products' => '/products', 'Add' => null];
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

<form method="POST" action="/products">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Left column: Core fields -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Basic Information</h6>
                </div>
                <div class="card-body">
                    <!-- SKU -->
                    <div class="mb-3">
                        <label for="sku" class="form-label fw-semibold">SKU</label>
                        <input type="text"
                               id="sku"
                               name="sku"
                               class="form-control <?= !empty($errors['sku']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($old['sku'] ?? '') ?>"
                               maxlength="50"
                               placeholder="<?= sanitize($autoSku ?? '') ?>">
                        <?php if (!empty($errors['sku'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['sku'][0]) ?></div>
                        <?php else: ?>
                            <div class="form-text">Leave blank to auto-generate (e.g. <?= sanitize($autoSku ?? 'PRD-00001') ?>).</div>
                        <?php endif; ?>
                    </div>

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            Product Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($old['name'] ?? '') ?>"
                               maxlength="200"
                               required>
                        <?php if (!empty($errors['name'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['name'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mb-0">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description"
                                  name="description"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Optional product description..."><?= sanitize($old['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing & Tax -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-dollar-sign me-2 text-success"></i>Pricing</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="cost_price" class="form-label fw-semibold">Cost Price</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number"
                                       id="cost_price"
                                       name="cost_price"
                                       class="form-control"
                                       value="<?= sanitize($old['cost_price'] ?? '0') ?>"
                                       min="0"
                                       step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="selling_price" class="form-label fw-semibold">
                                Selling Price <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number"
                                       id="selling_price"
                                       name="selling_price"
                                       class="form-control <?= !empty($errors['selling_price']) ? 'is-invalid' : '' ?>"
                                       value="<?= sanitize($old['selling_price'] ?? '0') ?>"
                                       min="0"
                                       step="0.01"
                                       required>
                                <?php if (!empty($errors['selling_price'])): ?>
                                    <div class="invalid-feedback"><?= sanitize($errors['selling_price'][0]) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="tax_rate" class="form-label fw-semibold">Tax Rate (%)</label>
                            <div class="input-group">
                                <input type="number"
                                       id="tax_rate"
                                       name="tax_rate"
                                       class="form-control"
                                       value="<?= sanitize($old['tax_rate'] ?? '0') ?>"
                                       min="0"
                                       max="100"
                                       step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Levels -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-warehouse me-2 text-warning"></i>Stock Thresholds</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="min_stock" class="form-label fw-semibold">Minimum Stock</label>
                            <input type="number"
                                   id="min_stock"
                                   name="min_stock"
                                   class="form-control"
                                   value="<?= sanitize($old['min_stock'] ?? '0') ?>"
                                   min="0"
                                   step="1">
                            <div class="form-text">Alert when stock falls below this level.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="max_stock" class="form-label fw-semibold">Maximum Stock</label>
                            <input type="number"
                                   id="max_stock"
                                   name="max_stock"
                                   class="form-control"
                                   value="<?= sanitize($old['max_stock'] ?? '0') ?>"
                                   min="0"
                                   step="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column: Classification -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-layer-group me-2 text-info"></i>Classification</h6>
                </div>
                <div class="card-body">
                    <!-- Category -->
                    <div class="mb-3">
                        <label for="category_id" class="form-label fw-semibold">
                            Category <span class="text-danger">*</span>
                        </label>
                        <select id="category_id"
                                name="category_id"
                                class="form-select <?= !empty($errors['category_id']) ? 'is-invalid' : '' ?>"
                                required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories ?? [] as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>"
                                    <?= ($old['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['category_id'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['category_id'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Brand -->
                    <div class="mb-3">
                        <label for="brand_id" class="form-label fw-semibold">Brand</label>
                        <select id="brand_id" name="brand_id" class="form-select">
                            <option value="">— No Brand —</option>
                            <?php foreach ($brands ?? [] as $brand): ?>
                                <option value="<?= (int) $brand['id'] ?>"
                                    <?= ($old['brand_id'] ?? '') == $brand['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($brand['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Unit -->
                    <div class="mb-3">
                        <label for="unit_id" class="form-label fw-semibold">Unit of Measure</label>
                        <select id="unit_id" name="unit_id" class="form-select">
                            <option value="">— No Unit —</option>
                            <?php foreach ($units ?? [] as $unit): ?>
                                <option value="<?= (int) $unit['id'] ?>"
                                    <?= ($old['unit_id'] ?? '') == $unit['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($unit['name']) ?> (<?= sanitize($unit['symbol']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="mb-3">
                        <label for="type" class="form-label fw-semibold">Product Type</label>
                        <select id="type" name="type" class="form-select">
                            <option value="standard" <?= ($old['type'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>Standard</option>
                            <option value="variant"  <?= ($old['type'] ?? '')         === 'variant'  ? 'selected' : '' ?>>Variant</option>
                            <option value="service"  <?= ($old['type'] ?? '')         === 'service'  ? 'selected' : '' ?>>Service</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="mb-0">
                        <label for="status" class="form-label fw-semibold">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select id="status"
                                name="status"
                                class="form-select <?= !empty($errors['status']) ? 'is-invalid' : '' ?>">
                            <option value="active"       <?= ($old['status'] ?? 'active') === 'active'       ? 'selected' : '' ?>>Active</option>
                            <option value="inactive"     <?= ($old['status'] ?? '')       === 'inactive'     ? 'selected' : '' ?>>Inactive</option>
                            <option value="discontinued" <?= ($old['status'] ?? '')       === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
                        </select>
                        <?php if (!empty($errors['status'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['status'][0]) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Save Product
                </button>
                <a href="/products" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>

</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
