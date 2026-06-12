<?php
$pageTitle   = 'Edit Product';
$breadcrumbs = ['Inventory' => null, 'Products' => '/products', 'Edit' => null];
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];
// $val merges saved data with any re-flash on validation failure
$val = array_merge($product ?? [], $old);
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

<form method="POST" action="/products/<?= (int) $product['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="row g-4">
        <!-- Left column -->
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
                               value="<?= sanitize($val['sku'] ?? '') ?>"
                               maxlength="50">
                        <?php if (!empty($errors['sku'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['sku'][0]) ?></div>
                        <?php else: ?>
                            <div class="form-text">Leave blank to auto-generate a new SKU.</div>
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
                               value="<?= sanitize($val['name'] ?? '') ?>"
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
                                  placeholder="Optional product description..."><?= sanitize($val['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
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
                                       value="<?= sanitize($val['cost_price'] ?? '0') ?>"
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
                                       value="<?= sanitize($val['selling_price'] ?? '0') ?>"
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
                                       value="<?= sanitize($val['tax_rate'] ?? '0') ?>"
                                       min="0"
                                       max="100"
                                       step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Thresholds -->
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
                                   value="<?= sanitize($val['min_stock'] ?? '0') ?>"
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
                                   value="<?= sanitize($val['max_stock'] ?? '0') ?>"
                                   min="0"
                                   step="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column -->
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
                                    <?= ($val['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
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
                                    <?= ($val['brand_id'] ?? '') == $brand['id'] ? 'selected' : '' ?>>
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
                                    <?= ($val['unit_id'] ?? '') == $unit['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($unit['name']) ?> (<?= sanitize($unit['symbol']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="mb-3">
                        <label for="type" class="form-label fw-semibold">Product Type</label>
                        <select id="type" name="type" class="form-select">
                            <option value="standard" <?= ($val['type'] ?? '') === 'standard' ? 'selected' : '' ?>>Standard</option>
                            <option value="variant"  <?= ($val['type'] ?? '') === 'variant'  ? 'selected' : '' ?>>Variant</option>
                            <option value="service"  <?= ($val['type'] ?? '') === 'service'  ? 'selected' : '' ?>>Service</option>
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
                            <option value="active"       <?= ($val['status'] ?? '') === 'active'       ? 'selected' : '' ?>>Active</option>
                            <option value="inactive"     <?= ($val['status'] ?? '') === 'inactive'     ? 'selected' : '' ?>>Inactive</option>
                            <option value="discontinued" <?= ($val['status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
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
                    <i class="fas fa-save me-1"></i>Update Product
                </button>
                <a href="/products/<?= (int) $product['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>

</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
