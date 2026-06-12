<?php ob_start(); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-edit me-2 text-primary"></i>Edit Account</h6></div>
            <div class="card-body">
                <form method="POST" action="/accounting/accounts/<?= (int) $account['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Account Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['code'] ?? $account['code']) ?>" <?= $account['is_system'] ? 'readonly' : '' ?> required>
                            <?php if (isset($errors['code'])): ?><div class="invalid-feedback"><?= sanitize($errors['code']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Account Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['name'] ?? $account['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= sanitize($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" <?= $account['is_system'] ? 'disabled' : '' ?> required>
                                <?php foreach (['asset', 'liability', 'equity', 'revenue', 'expense'] as $t): ?>
                                    <option value="<?= $t ?>" <?= ($old['type'] ?? $account['type']) === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($account['is_system']): ?><input type="hidden" name="type" value="<?= sanitize($account['type']) ?>"><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Normal Balance</label>
                            <select name="normal_balance" class="form-select">
                                <option value="debit" <?= ($old['normal_balance'] ?? $account['normal_balance']) === 'debit' ? 'selected' : '' ?>>Debit</option>
                                <option value="credit" <?= ($old['normal_balance'] ?? $account['normal_balance']) === 'credit' ? 'selected' : '' ?>>Credit</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sub-type</label>
                            <input type="text" name="subtype" class="form-control" value="<?= sanitize($old['subtype'] ?? $account['subtype'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?= ($old['is_active'] ?? $account['is_active']) ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= !($old['is_active'] ?? $account['is_active']) ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Parent Account</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($parents ?? [] as $p): ?>
                                    <?php if ((int) $p['id'] === (int) $account['id']) continue; ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= ((int) ($old['parent_id'] ?? $account['parent_id'] ?? 0)) === (int) $p['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($p['code'] . ' - ' . $p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= sanitize($old['description'] ?? $account['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                            <a href="/accounting/accounts/<?= (int) $account['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
