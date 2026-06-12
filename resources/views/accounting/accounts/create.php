<?php ob_start(); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0 fw-semibold"><i class="fas fa-plus-circle me-2 text-primary"></i>New Account</h6></div>
            <div class="card-body">
                <form method="POST" action="/accounting/accounts">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Account Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['code'] ?? '') ?>" required>
                            <?php if (isset($errors['code'])): ?><div class="invalid-feedback"><?= sanitize($errors['code']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Account Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   value="<?= sanitize($old['name'] ?? '') ?>" required>
                            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= sanitize($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Select type...</option>
                                <?php foreach (['asset', 'liability', 'equity', 'revenue', 'expense'] as $t): ?>
                                    <option value="<?= $t ?>" <?= ($old['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['type'])): ?><div class="invalid-feedback"><?= sanitize($errors['type']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Normal Balance</label>
                            <select name="normal_balance" class="form-select">
                                <option value="debit" <?= ($old['normal_balance'] ?? 'debit') === 'debit' ? 'selected' : '' ?>>Debit</option>
                                <option value="credit" <?= ($old['normal_balance'] ?? '') === 'credit' ? 'selected' : '' ?>>Credit</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sub-type</label>
                            <input type="text" name="subtype" class="form-control" value="<?= sanitize($old['subtype'] ?? '') ?>" placeholder="e.g. current, fixed">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Parent Account</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None (root account)</option>
                                <?php foreach ($parents ?? [] as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= ((int) ($old['parent_id'] ?? 0)) === (int) $p['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($p['code'] . ' - ' . $p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= sanitize($old['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Account</button>
                            <a href="/accounting/accounts" class="btn btn-outline-secondary">Cancel</a>
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
