<?php
$pageTitle = $pageTitle ?? 'Tax & VAT Settings';
$settings  = $settings  ?? [];
$activeTab = $activeTab ?? 'tax';
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
                <h6 class="mb-0"><i class="fas fa-percent me-2 text-primary"></i>Tax &amp; VAT Settings</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/settings/tax" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <!-- VAT Enabled -->
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="vat_enabled"
                                       name="vat_enabled"
                                       value="1"
                                       <?= !empty($settings['vat_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="vat_enabled">
                                    Enable VAT
                                </label>
                            </div>
                            <div class="form-text">When enabled, VAT will be applied to applicable transactions.</div>
                        </div>

                        <!-- Default VAT Rate -->
                        <div class="col-12 col-md-4">
                            <label for="default_vat_rate" class="form-label fw-semibold">Default VAT Rate (%)</label>
                            <div class="input-group">
                                <input type="number"
                                       id="default_vat_rate"
                                       name="default_vat_rate"
                                       class="form-control"
                                       value="<?= sanitize($settings['default_vat_rate'] ?? '0') ?>"
                                       min="0" max="100" step="0.01"
                                       placeholder="0.00">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Default VAT percentage applied to new transactions.</div>
                        </div>

                        <!-- VAT Registration Number -->
                        <div class="col-12 col-md-6">
                            <label for="vat_registration_number" class="form-label fw-semibold">VAT Registration Number</label>
                            <input type="text"
                                   id="vat_registration_number"
                                   name="vat_registration_number"
                                   class="form-control"
                                   value="<?= sanitize($settings['vat_registration_number'] ?? '') ?>"
                                   placeholder="e.g. BIN-123456789"
                                   maxlength="50">
                            <div class="form-text">This will appear on invoices and receipts.</div>
                        </div>

                        <!-- Tax Year Start Month -->
                        <div class="col-12 col-md-4">
                            <label for="tax_year_start" class="form-label fw-semibold">Tax Year Start Month</label>
                            <select id="tax_year_start" name="tax_year_start" class="form-select">
                                <?php
                                $months = [
                                    1  => 'January',   2  => 'February', 3  => 'March',
                                    4  => 'April',     5  => 'May',      6  => 'June',
                                    7  => 'July',      8  => 'August',   9  => 'September',
                                    10 => 'October',   11 => 'November', 12 => 'December',
                                ];
                                $currentMonth = (int) ($settings['tax_year_start'] ?? 1);
                                foreach ($months as $num => $name):
                                ?>
                                    <option value="<?= $num ?>" <?= $currentMonth === $num ? 'selected' : '' ?>>
                                        <?= sanitize($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">The month your financial/tax year begins.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Tax Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
