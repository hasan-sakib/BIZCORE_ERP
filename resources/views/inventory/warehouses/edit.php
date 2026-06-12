<?php
$pageTitle   = 'Edit Warehouse';
$breadcrumbs = ['Inventory' => null, 'Warehouses' => '/inventory/warehouses', 'Edit' => null];
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];

// Use flashed old input when available, otherwise fall back to current warehouse data
$val = function (string $field) use ($old, $warehouse): string {
    return sanitize((string) ($old[$field] ?? $warehouse[$field] ?? ''));
};
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

<form method="POST" action="/inventory/warehouses/<?= (int) $warehouse['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-warehouse me-2 text-primary"></i>Warehouse Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-semibold">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                                   value="<?= $val('name') ?>"
                                   maxlength="200"
                                   required>
                            <?php if (!empty($errors['name'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['name'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Code -->
                        <div class="col-md-4">
                            <label for="code" class="form-label fw-semibold">
                                Code <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="code"
                                   name="code"
                                   class="form-control <?= !empty($errors['code']) ? 'is-invalid' : '' ?>"
                                   value="<?= $val('code') ?>"
                                   maxlength="20"
                                   required>
                            <?php if (!empty($errors['code'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['code'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Location -->
                        <div class="col-12">
                            <label for="location" class="form-label fw-semibold">Location</label>
                            <textarea id="location"
                                      name="location"
                                      class="form-control"
                                      rows="2"><?= $val('location') ?></textarea>
                        </div>

                        <!-- Capacity -->
                        <div class="col-md-6">
                            <label for="capacity" class="form-label fw-semibold">Capacity</label>
                            <input type="number"
                                   id="capacity"
                                   name="capacity"
                                   class="form-control <?= !empty($errors['capacity']) ? 'is-invalid' : '' ?>"
                                   value="<?= $val('capacity') ?>"
                                   min="0"
                                   step="0.01">
                            <?php if (!empty($errors['capacity'])): ?>
                                <div class="invalid-feedback"><?= sanitize($errors['capacity'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Manager ID -->
                        <div class="col-md-6">
                            <label for="manager_id" class="form-label fw-semibold">Manager ID</label>
                            <input type="number"
                                   id="manager_id"
                                   name="manager_id"
                                   class="form-control"
                                   value="<?= $val('manager_id') ?>"
                                   min="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-sliders-h me-2 text-info"></i>Settings</h6>
                </div>
                <div class="card-body">
                    <!-- Status -->
                    <?php
                    $currentStatus = $old['status'] ?? $warehouse['status'] ?? 'active';
                    ?>
                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select id="status"
                                name="status"
                                class="form-select <?= !empty($errors['status']) ? 'is-invalid' : '' ?>">
                            <option value="active"   <?= $currentStatus === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <?php if (!empty($errors['status'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['status'][0]) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Is Default -->
                    <?php
                    $isDefault = isset($old['is_default'])
                        ? (bool) $old['is_default']
                        : (bool) ($warehouse['is_default'] ?? false);
                    ?>
                    <div class="form-check">
                        <input type="hidden" name="is_default" value="0">
                        <input class="form-check-input"
                               type="checkbox"
                               id="is_default"
                               name="is_default"
                               value="1"
                               <?= $isDefault ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_default">
                            Set as default warehouse
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update Warehouse
                </button>
                <a href="/inventory/warehouses/<?= (int) $warehouse['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
