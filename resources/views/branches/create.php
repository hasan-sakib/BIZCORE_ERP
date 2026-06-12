<?php
$pageTitle = $pageTitle ?? 'Create Branch';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-building me-2 text-primary"></i>New Branch</h6>
            </div>
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($errors as $field => $messages): ?>
                                <?php foreach ((array) $messages as $msg): ?>
                                    <li><?= sanitize($msg) ?></li>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/branches">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12 col-sm-8">
                            <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize($old['name'] ?? '') ?>"
                                placeholder="e.g. Dhaka Main Branch"
                                required
                                autofocus
                            >
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['name'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Code -->
                        <div class="col-12 col-sm-4">
                            <label for="code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                class="form-control text-uppercase <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize($old['code'] ?? '') ?>"
                                placeholder="e.g. DHK01"
                                maxlength="10"
                                required
                            >
                            <?php if (isset($errors['code'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['code'])) ?></div>
                            <?php else: ?>
                                <div class="form-text">2–10 alphanumeric characters.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-sm-6">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize($old['email'] ?? '') ?>"
                                placeholder="branch@example.com"
                            >
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['email'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Phone -->
                        <div class="col-12 col-sm-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize($old['phone'] ?? '') ?>"
                                placeholder="+880…"
                            >
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['phone'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea
                                id="address"
                                name="address"
                                class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                                rows="3"
                                placeholder="Street, City, Country"
                            ><?= sanitize($old['address'] ?? '') ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['address'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-sm-6">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active"   <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($old['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <!-- Head Office -->
                        <div class="col-12 col-sm-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input
                                    type="checkbox"
                                    id="is_head"
                                    name="is_head"
                                    value="1"
                                    class="form-check-input"
                                    <?= !empty($old['is_head']) ? 'checked' : '' ?>
                                >
                                <label for="is_head" class="form-check-label">
                                    Mark as Head Office
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/branches" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
