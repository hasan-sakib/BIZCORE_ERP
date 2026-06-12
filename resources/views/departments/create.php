<?php
$pageTitle = 'New Department';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-sitemap me-2"></i>New Department</h5>
            </div>
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $field => $msg): ?>
                                <li><?= sanitize($msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/hr/departments">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($old['name'] ?? '') ?>" maxlength="150" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label fw-semibold">Code</label>
                        <input type="text" id="code" name="code" class="form-control"
                               value="<?= sanitize($old['code'] ?? '') ?>" maxlength="20"
                               placeholder="e.g. ENG, HR, FIN">
                        <div class="form-text">Short identifier for the department.</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?= sanitize($old['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="active"   <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($old['status'] ?? '')         === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Department</button>
                        <a href="/hr/departments" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
