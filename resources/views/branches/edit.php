<?php
$pageTitle = $pageTitle ?? 'Edit Branch';
ob_start();

$errors = $errors ?? [];
$old    = $old    ?? [];

function branchEditVal(string $key, mixed $default, array $old, object $branch): mixed
{
    if (isset($old[$key])) {
        return $old[$key];
    }
    return match ($key) {
        'name'    => $branch->name,
        'code'    => $branch->code,
        'email'   => $branch->email ?? '',
        'phone'   => $branch->phone ?? '',
        'address' => $branch->formattedAddress(),
        'status'  => $branch->status,
        'is_head' => $branch->isHead ? '1' : '',
        default   => $default,
    };
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="fas fa-edit me-2 text-primary"></i>Edit Branch</h6>
                <a href="/branches/<?= (int) $branch->id ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body">

                <?php if ($branch->isHeadOffice()): ?>
                    <div class="alert alert-info d-flex gap-2 mb-3">
                        <i class="fas fa-star mt-1 flex-shrink-0 text-primary"></i>
                        <div>This is the <strong>Head Office</strong> branch.</div>
                    </div>
                <?php endif; ?>

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

                <form method="POST" action="/branches/<?= (int) $branch->id ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12 col-sm-8">
                            <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= sanitize(branchEditVal('name', '', $old, $branch)) ?>"
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
                                value="<?= sanitize(branchEditVal('code', '', $old, $branch)) ?>"
                                maxlength="10"
                                required
                            >
                            <?php if (isset($errors['code'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['code'])) ?></div>
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
                                value="<?= sanitize(branchEditVal('email', '', $old, $branch)) ?>"
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
                                value="<?= sanitize(branchEditVal('phone', '', $old, $branch)) ?>"
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
                            ><?= sanitize(branchEditVal('address', '', $old, $branch)) ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback"><?= sanitize(implode(', ', (array) $errors['address'])) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-sm-6">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="active"   <?= branchEditVal('status', 'active', $old, $branch) === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= branchEditVal('status', 'active', $old, $branch) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
                                    <?= branchEditVal('is_head', '', $old, $branch) ? 'checked' : '' ?>
                                >
                                <label for="is_head" class="form-check-label">
                                    Mark as Head Office
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/branches/<?= (int) $branch->id ?>" class="btn btn-outline-secondary">
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
