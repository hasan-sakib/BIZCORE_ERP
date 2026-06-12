<?php
$pageTitle = $pageTitle ?? 'General Settings';
$settings  = $settings  ?? [];
$activeTab = $activeTab ?? 'general';
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
                <h6 class="mb-0"><i class="fas fa-sliders-h me-2 text-primary"></i>General Settings</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/settings/general" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row g-3">

                        <!-- App Name -->
                        <div class="col-12 col-md-6">
                            <label for="app_name" class="form-label fw-semibold">Application Name</label>
                            <input type="text"
                                   id="app_name"
                                   name="app_name"
                                   class="form-control"
                                   value="<?= sanitize($settings['app_name'] ?? 'BizCore ERP') ?>"
                                   maxlength="100">
                        </div>

                        <!-- Timezone -->
                        <div class="col-12 col-md-6">
                            <label for="app_timezone" class="form-label fw-semibold">Timezone</label>
                            <select id="app_timezone" name="app_timezone" class="form-select">
                                <?php
                                $timezones = [
                                    'Asia/Dhaka'        => 'Asia/Dhaka (UTC+6)',
                                    'Asia/Kolkata'      => 'Asia/Kolkata (UTC+5:30)',
                                    'Asia/Karachi'      => 'Asia/Karachi (UTC+5)',
                                    'Asia/Dubai'        => 'Asia/Dubai (UTC+4)',
                                    'Asia/Singapore'    => 'Asia/Singapore (UTC+8)',
                                    'Asia/Tokyo'        => 'Asia/Tokyo (UTC+9)',
                                    'Asia/Shanghai'     => 'Asia/Shanghai (UTC+8)',
                                    'Europe/London'     => 'Europe/London (UTC+0/+1)',
                                    'Europe/Paris'      => 'Europe/Paris (UTC+1/+2)',
                                    'Europe/Berlin'     => 'Europe/Berlin (UTC+1/+2)',
                                    'America/New_York'  => 'America/New_York (UTC-5/-4)',
                                    'America/Chicago'   => 'America/Chicago (UTC-6/-5)',
                                    'America/Denver'    => 'America/Denver (UTC-7/-6)',
                                    'America/Los_Angeles'=> 'America/Los_Angeles (UTC-8/-7)',
                                    'UTC'               => 'UTC',
                                    'Pacific/Auckland'  => 'Pacific/Auckland (UTC+12/+13)',
                                    'Australia/Sydney'  => 'Australia/Sydney (UTC+10/+11)',
                                ];
                                $currentTz = $settings['app_timezone'] ?? 'Asia/Dhaka';
                                foreach ($timezones as $tz => $label):
                                ?>
                                    <option value="<?= sanitize($tz) ?>" <?= $currentTz === $tz ? 'selected' : '' ?>>
                                        <?= sanitize($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Format -->
                        <div class="col-12 col-md-6">
                            <label for="date_format" class="form-label fw-semibold">Date Format</label>
                            <select id="date_format" name="date_format" class="form-select">
                                <?php
                                $formats = [
                                    'd/m/Y' => 'DD/MM/YYYY  (e.g. 09/06/2026)',
                                    'm/d/Y' => 'MM/DD/YYYY  (e.g. 06/09/2026)',
                                    'Y-m-d' => 'YYYY-MM-DD  (e.g. 2026-06-09)',
                                    'd-m-Y' => 'DD-MM-YYYY  (e.g. 09-06-2026)',
                                    'd M Y' => 'DD Mon YYYY (e.g. 09 Jun 2026)',
                                ];
                                $currentFmt = $settings['date_format'] ?? 'd/m/Y';
                                foreach ($formats as $fmt => $label):
                                ?>
                                    <option value="<?= sanitize($fmt) ?>" <?= $currentFmt === $fmt ? 'selected' : '' ?>>
                                        <?= sanitize($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Currency -->
                        <div class="col-12 col-md-3">
                            <label for="currency" class="form-label fw-semibold">Currency Code</label>
                            <input type="text"
                                   id="currency"
                                   name="currency"
                                   class="form-control"
                                   value="<?= sanitize($settings['currency'] ?? 'BDT') ?>"
                                   maxlength="10"
                                   placeholder="BDT">
                        </div>

                        <!-- Currency Symbol -->
                        <div class="col-12 col-md-3">
                            <label for="currency_symbol" class="form-label fw-semibold">Currency Symbol</label>
                            <input type="text"
                                   id="currency_symbol"
                                   name="currency_symbol"
                                   class="form-control"
                                   value="<?= sanitize($settings['currency_symbol'] ?? '৳') ?>"
                                   maxlength="5"
                                   placeholder="৳">
                        </div>

                        <!-- Pagination Limit -->
                        <div class="col-12 col-md-6">
                            <label for="pagination_limit" class="form-label fw-semibold">Default Pagination Limit</label>
                            <input type="number"
                                   id="pagination_limit"
                                   name="pagination_limit"
                                   class="form-control"
                                   value="<?= (int) ($settings['pagination_limit'] ?? 20) ?>"
                                   min="5"
                                   max="500">
                            <div class="form-text">Number of records per page (5–500).</div>
                        </div>

                    </div><!-- /row -->

                    <hr class="my-4">

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save General Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
