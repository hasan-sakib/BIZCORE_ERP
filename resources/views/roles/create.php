<?php
$pageTitle = $pageTitle ?? 'Create Role';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>New Role</h6>
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

                <form method="POST" action="/roles">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize($old['name'] ?? '') ?>"
                                placeholder="e.g. Store Manager"
                                required
                                autofocus
                            >
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['name'])) ?></div>
                            <?php else: ?>
                                <div class="form-text">The slug will be generated automatically from the name.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                rows="3"
                                placeholder="Brief description of what this role can do…"
                            ><?= sanitize($old['description'] ?? '') ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['description'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/roles" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 d-flex gap-2">
            <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
            <div>
                After creating the role, you can assign specific permissions from the role's detail page.
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
