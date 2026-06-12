<?php
$pageTitle = 'Edit Supplier';
ob_start();

$errors   = $errors   ?? [];
$old      = $old      ?? [];
$supplier = $supplier ?? [];
$id       = (int) ($supplier['id'] ?? 0);
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2 text-primary"></i>
                    Edit Supplier — <?= sanitize($supplier['name'] ?? '') ?>
                </h5>
            </div>
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Please fix the following errors:</strong>
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

                <form method="POST" action="/suppliers/<?= $id ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">
                                Supplier Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name"
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['name'] ?? $supplier['name'] ?? '') ?>"
                                   required maxlength="200">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(' ', $errors['name'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email"
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['email'] ?? $supplier['email'] ?? '') ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(' ', $errors['email'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Phone -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone"
                                   class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['phone'] ?? $supplier['phone'] ?? '') ?>"
                                   maxlength="30">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(' ', $errors['phone'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Tax Number -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Tax Number</label>
                            <input type="text" name="tax_number" class="form-control"
                                   value="<?= sanitize($old['tax_number'] ?? $supplier['tax_number'] ?? '') ?>">
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= sanitize($old['address'] ?? $supplier['address'] ?? '') ?></textarea>
                        </div>

                        <!-- City -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?= sanitize($old['city'] ?? $supplier['city'] ?? '') ?>">
                        </div>

                        <!-- Country -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Country</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?= sanitize($old['country'] ?? $supplier['country'] ?? '') ?>">
                        </div>

                        <!-- Credit Limit -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Credit Limit</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="credit_limit" class="form-control"
                                       value="<?= sanitize((string) ($old['credit_limit'] ?? $supplier['credit_limit'] ?? '0')) ?>"
                                       min="0" step="0.01">
                            </div>
                        </div>

                        <!-- Payment Terms -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control"
                                   value="<?= sanitize($old['payment_terms'] ?? $supplier['payment_terms'] ?? '') ?>"
                                   placeholder="e.g. Net 30, COD...">
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>">
                                <?php $currentStatus = $old['status'] ?? $supplier['status'] ?? 'active'; ?>
                                <option value="active"   <?= $currentStatus === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(' ', $errors['status'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Internal notes..."><?= sanitize($old['notes'] ?? $supplier['notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Supplier
                        </button>
                        <a href="/suppliers/<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
