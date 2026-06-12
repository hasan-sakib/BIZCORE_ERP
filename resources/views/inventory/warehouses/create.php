<?php
$pageTitle   = 'Add Warehouse';
$breadcrumbs = ['Inventory' => null, 'Warehouses' => '/inventory/warehouses', 'Add' => null];
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

<form method="POST" action="/inventory/warehouses">
    <?= csrf_field() ?>

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
                                   value="<?= sanitize($old['name'] ?? '') ?>"
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
                                   value="<?= sanitize($old['code'] ?? '') ?>"
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
                                      rows="2"
                                      placeholder="Address or description of location..."><?= sanitize($old['location'] ?? '') ?></textarea>
                        </div>

                        <!-- Capacity -->
                        <div class="col-md-6">
                            <label for="capacity" class="form-label fw-semibold">Capacity</label>
                            <input type="number"
                                   id="capacity"
                                   name="capacity"
                                   class="form-control <?= !empty($errors['capacity']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['capacity'] ?? '') ?>"
                                   min="0"
                                   step="0.01"
                                   placeholder="Optional maximum capacity">
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
                                   value="<?= sanitize($old['manager_id'] ?? '') ?>"
                                   min="1"
                                   placeholder="Optional user ID">
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
                    <div class="mb-3">
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

                    <!-- Is Default -->
                    <div class="form-check">
                        <input type="hidden" name="is_default" value="0">
                        <input class="form-check-input"
                               type="checkbox"
                               id="is_default"
                               name="is_default"
                               value="1"
                               <?= !empty($old['is_default']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_default">
                            Set as default warehouse
                        </label>
                        <div class="form-text">The default warehouse is pre-selected on stock forms.</div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Save Warehouse
                </button>
                <a href="/inventory/warehouses" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
