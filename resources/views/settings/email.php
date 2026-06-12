<?php
$pageTitle = $pageTitle ?? 'Email / SMTP Settings';
$settings  = $settings  ?? [];
$activeTab = $activeTab ?? 'email';
ob_start();
?>

<div class="row">
    <!-- Settings Sidebar Nav -->
    <div class="col-12 col-md-3 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Settings</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="/settings/general"
                   class="list-group-item list-group-item-action <?= $activeTab === 'general' ? 'active' : '' ?>">
                    <i class="fas fa-sliders-h me-2"></i>General
                </a>
                <a href="/settings/company"
                   class="list-group-item list-group-item-action <?= $activeTab === 'company' ? 'active' : '' ?>">
                    <i class="fas fa-building me-2"></i>Company
                </a>
                <a href="/settings/tax"
                   class="list-group-item list-group-item-action <?= $activeTab === 'tax' ? 'active' : '' ?>">
                    <i class="fas fa-percent me-2"></i>Tax &amp; VAT
                </a>
                <a href="/settings/email"
                   class="list-group-item list-group-item-action <?= $activeTab === 'email' ? 'active' : '' ?>">
                    <i class="fas fa-envelope me-2"></i>Email / SMTP
                </a>
                <a href="/settings/audit-logs"
                   class="list-group-item list-group-item-action <?= $activeTab === 'audit-logs' ? 'active' : '' ?>">
                    <i class="fas fa-history me-2"></i>Audit Logs
                </a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="col-12 col-md-9">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Email / SMTP Settings</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info d-flex gap-2 py-2 mb-4">
                    <i class="fas fa-info-circle mt-1"></i>
                    <div class="small">These settings configure outgoing email via SMTP. Passwords are stored securely.
                    Test the connection after saving.</div>
                </div>

                <form method="POST" action="/settings/email" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <!-- SMTP Host -->
                        <div class="col-12 col-md-6">
                            <label for="smtp_host" class="form-label fw-semibold">SMTP Host</label>
                            <input type="text"
                                   id="smtp_host"
                                   name="smtp_host"
                                   class="form-control"
                                   value="<?= sanitize($settings['smtp_host'] ?? '') ?>"
                                   placeholder="e.g. smtp.gmail.com"
                                   maxlength="255">
                        </div>

                        <!-- SMTP Port -->
                        <div class="col-12 col-md-3">
                            <label for="smtp_port" class="form-label fw-semibold">SMTP Port</label>
                            <input type="number"
                                   id="smtp_port"
                                   name="smtp_port"
                                   class="form-control"
                                   value="<?= sanitize($settings['smtp_port'] ?? '587') ?>"
                                   min="1" max="65535"
                                   placeholder="587">
                        </div>

                        <!-- Encryption -->
                        <div class="col-12 col-md-3">
                            <label for="mail_encryption" class="form-label fw-semibold">Encryption</label>
                            <select id="mail_encryption" name="mail_encryption" class="form-select">
                                <?php
                                $encOptions = ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'];
                                $currentEnc = $settings['mail_encryption'] ?? 'tls';
                                foreach ($encOptions as $val => $label):
                                ?>
                                    <option value="<?= sanitize($val) ?>" <?= $currentEnc === $val ? 'selected' : '' ?>>
                                        <?= sanitize($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- SMTP Username -->
                        <div class="col-12 col-md-6">
                            <label for="smtp_username" class="form-label fw-semibold">SMTP Username</label>
                            <input type="text"
                                   id="smtp_username"
                                   name="smtp_username"
                                   class="form-control"
                                   value="<?= sanitize($settings['smtp_username'] ?? '') ?>"
                                   placeholder="your@email.com"
                                   autocomplete="username"
                                   maxlength="255">
                        </div>

                        <!-- SMTP Password -->
                        <div class="col-12 col-md-6">
                            <label for="smtp_password" class="form-label fw-semibold">SMTP Password</label>
                            <div class="input-group">
                                <input type="password"
                                       id="smtp_password"
                                       name="smtp_password"
                                       class="form-control"
                                       value="<?= sanitize($settings['smtp_password'] ?? '') ?>"
                                       placeholder="Leave blank to keep current"
                                       autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                        onclick="const i=document.getElementById('smtp_password');i.type=i.type==='password'?'text':'password'">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <hr class="col-12 my-1">

                        <!-- From Address -->
                        <div class="col-12 col-md-6">
                            <label for="mail_from_address" class="form-label fw-semibold">From Address</label>
                            <input type="email"
                                   id="mail_from_address"
                                   name="mail_from_address"
                                   class="form-control"
                                   value="<?= sanitize($settings['mail_from_address'] ?? '') ?>"
                                   placeholder="noreply@yourcompany.com"
                                   maxlength="255">
                            <div class="form-text">Email address that will appear in the "From" field.</div>
                        </div>

                        <!-- From Name -->
                        <div class="col-12 col-md-6">
                            <label for="mail_from_name" class="form-label fw-semibold">From Name</label>
                            <input type="text"
                                   id="mail_from_name"
                                   name="mail_from_name"
                                   class="form-control"
                                   value="<?= sanitize($settings['mail_from_name'] ?? '') ?>"
                                   placeholder="BizCore ERP"
                                   maxlength="100">
                            <div class="form-text">Display name shown alongside the from address.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Email Settings
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
