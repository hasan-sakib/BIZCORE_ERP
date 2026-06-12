<?php
$pageTitle = 'Edit Designation';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];

$val = fn(string $key) => $old[$key] ?? $designation[$key] ?? '';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-edit me-2"></i>Edit Designation</h5>
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

                <form method="POST" action="/hr/designations/<?= (int) $designation['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                               class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               value="<?= sanitize($val('name')) ?>" maxlength="150" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="department_id" class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select id="department_id" name="department_id"
                                class="form-select <?= isset($errors['department_id']) ? 'is-invalid' : '' ?>" required>
                            <option value="">-- Select Department --</option>
                            <?php
                            $selDeptId = (int) $val('department_id');
                            foreach ($departments as $dept):
                            ?>
                                <option value="<?= (int) $dept['id'] ?>"
                                    <?= $selDeptId === (int) $dept['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['department_id'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['department_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?= sanitize($val('description')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select id="status" name="status" class="form-select">
                            <?php $curStatus = $val('status'); ?>
                            <option value="active"   <?= $curStatus === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $curStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                        <a href="/hr/designations/<?= (int) $designation['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
