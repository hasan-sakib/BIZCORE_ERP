<?php
$pageTitle = $pageTitle ?? 'Company Settings';
$settings  = $settings  ?? [];
$activeTab = $activeTab ?? 'company';
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
                <h6 class="mb-0"><i class="fas fa-building me-2 text-primary"></i>Company Information</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/settings/company" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row g-3">

                        <!-- Company Name -->
                        <div class="col-12 col-md-6">
                            <label for="company_name" class="form-label fw-semibold">Company Name</label>
                            <input type="text"
                                   id="company_name"
                                   name="company_name"
                                   class="form-control"
                                   value="<?= sanitize($settings['company_name'] ?? '') ?>"
                                   maxlength="200"
                                   placeholder="Acme Corporation">
                        </div>

                        <!-- Company Email -->
                        <div class="col-12 col-md-6">
                            <label for="company_email" class="form-label fw-semibold">Company Email</label>
                            <input type="email"
                                   id="company_email"
                                   name="company_email"
                                   class="form-control"
                                   value="<?= sanitize($settings['company_email'] ?? '') ?>"
                                   placeholder="info@company.com">
                        </div>

                        <!-- Company Phone -->
                        <div class="col-12 col-md-6">
                            <label for="company_phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="text"
                                   id="company_phone"
                                   name="company_phone"
                                   class="form-control"
                                   value="<?= sanitize($settings['company_phone'] ?? '') ?>"
                                   placeholder="+880 1700-000000">
                        </div>

                        <!-- Company Website -->
                        <div class="col-12 col-md-6">
                            <label for="company_website" class="form-label fw-semibold">Website</label>
                            <input type="url"
                                   id="company_website"
                                   name="company_website"
                                   class="form-control"
                                   value="<?= sanitize($settings['company_website'] ?? '') ?>"
                                   placeholder="https://company.com">
                        </div>

                        <!-- Company Address -->
                        <div class="col-12">
                            <label for="company_address" class="form-label fw-semibold">Address</label>
                            <textarea id="company_address"
                                      name="company_address"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Street, City, Country"><?= sanitize($settings['company_address'] ?? '') ?></textarea>
                        </div>

                        <!-- Tax Number / VAT Number -->
                        <div class="col-12 col-md-6">
                            <label for="tax_number" class="form-label fw-semibold">Tax / VAT Number</label>
                            <input type="text"
                                   id="tax_number"
                                   name="tax_number"
                                   class="form-control"
                                   value="<?= sanitize($settings['tax_number'] ?? '') ?>"
                                   placeholder="BIN / VAT registration number">
                        </div>

                        <!-- Trade License -->
                        <div class="col-12 col-md-6">
                            <label for="trade_license" class="form-label fw-semibold">Trade License No.</label>
                            <input type="text"
                                   id="trade_license"
                                   name="trade_license"
                                   class="form-control"
                                   value="<?= sanitize($settings['trade_license'] ?? '') ?>"
                                   placeholder="Trade license number">
                        </div>

                    </div><!-- /row -->

                    <hr class="my-4">

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Company Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
