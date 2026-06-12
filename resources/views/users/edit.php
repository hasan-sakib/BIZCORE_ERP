<?php
$pageTitle = $pageTitle ?? 'Edit User';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];

function userEditOld(string $key, mixed $default, array $old, object $user): mixed
{
    if (isset($old[$key])) {
        return $old[$key];
    }
    return match ($key) {
        'name'      => $user->name,
        'email'     => $user->email,
        'phone'     => $user->phone ?? '',
        'role_id'   => $user->roleId,
        'branch_id' => $user->branchId,
        'status'    => $user->status->value,
        default     => $default,
    };
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="fas fa-user-edit me-2 text-primary"></i>Edit User</h6>
                <a href="/users/<?= (int) $user->id ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
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

                <form method="POST" action="/users/<?= (int) $user->id ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12 col-sm-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize(userEditOld('name', '', $old, $user)) ?>"
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
                                value="<?= sanitize(userEditOld('email', '', $old, $user)) ?>"
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
                                value="<?= sanitize(userEditOld('phone', '', $old, $user)) ?>"
                                placeholder="+880…"
                            >
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['phone'])) ?></div>
                            <?php endif; ?>
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
                                        <?= (string) userEditOld('role_id', '', $old, $user) === (string) $role->id ? 'selected' : '' ?>
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
                                        <?= (string) userEditOld('branch_id', '', $old, $user) === (string) $branch['id'] ? 'selected' : '' ?>
                                    >
                                        <?= sanitize($branch['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['branch_id'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['branch_id'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Status (read-only here; use toggle-status endpoint) -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label">Status</label>
                            <?php
                            $statusClass = match ($user->status->value) {
                                'active'   => 'bg-success',
                                'inactive' => 'bg-secondary',
                                'locked'   => 'bg-danger',
                                default    => 'bg-secondary',
                            };
                            ?>
                            <div class="form-control-plaintext">
                                <span class="badge <?= $statusClass ?> fs-6">
                                    <?= sanitize($user->status->label()) ?>
                                </span>
                                <small class="text-muted ms-2">
                                    Use the status toggle on the user profile page to change.
                                </small>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/users/<?= (int) $user->id ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
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
