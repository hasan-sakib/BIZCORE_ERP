<?php
$pageTitle = $pageTitle ?? 'Create User';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];

function userOld(string $key, mixed $default, array $old): mixed
{
    return $old[$key] ?? $default;
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-user-plus me-2 text-primary"></i>New User</h6>
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

                <form method="POST" action="/users">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12 col-sm-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize(userOld('name', '', $old)) ?>"
                                required
                                autofocus
                            >
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['name'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-sm-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize(userOld('email', '', $old)) ?>"
                                required
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
                                value="<?= sanitize(userOld('phone', '', $old)) ?>"
                                placeholder="+880…"
                            >
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['phone'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Password -->
                        <div class="col-12 col-sm-6">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-icon"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['password'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="col-12 col-sm-6">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select
                                id="role_id"
                                name="role_id"
                                class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>"
                                required
                            >
                                <option value="">— Select Role —</option>
                                <?php foreach ($roles as $role): ?>
                                    <option
                                        value="<?= (int) $role->id ?>"
                                        <?= (string) userOld('role_id', '', $old) === (string) $role->id ? 'selected' : '' ?>
                                    >
                                        <?= sanitize($role->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['role_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['role_id'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Branch -->
                        <div class="col-12 col-sm-6">
                            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select
                                id="branch_id"
                                name="branch_id"
                                class="form-select <?= isset($errors['branch_id']) ? 'is-invalid' : '' ?>"
                                required
                            >
                                <option value="">— Select Branch —</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option
                                        value="<?= (int) $branch['id'] ?>"
                                        <?= (string) userOld('branch_id', '', $old) === (string) $branch['id'] ? 'selected' : '' ?>
                                    >
                                        <?= sanitize($branch['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['branch_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['branch_id'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-sm-6">
                            <label for="status" class="form-label">Status</label>
                            <select
                                id="status"
                                name="status"
                                class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>"
                            >
                                <option value="active"   <?= userOld('status', 'active', $old) === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= userOld('status', 'active', $old) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['status'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Welcome Email -->
                        <div class="col-12">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    id="send_welcome_email"
                                    name="send_welcome_email"
                                    value="1"
                                    class="form-check-input"
                                    <?= userOld('send_welcome_email', '1', $old) ? 'checked' : '' ?>
                                >
                                <label for="send_welcome_email" class="form-check-label">
                                    Send welcome email to the new user
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/users" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon  = document.getElementById(fieldId + '-icon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
