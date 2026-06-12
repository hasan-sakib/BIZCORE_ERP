<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

/**
 * CompanyController
 *
 * Manages company profile settings stored in the `settings` table under the
 * 'company' group.
 */
final class CompanyController extends BaseController
{
    // =========================================================================
    // Settings helpers
    // =========================================================================

    private function getSetting(string $group, string $key, mixed $default = ''): mixed
    {
        $pdo  = app(\PDO::class);
        $stmt = $pdo->prepare(
            'SELECT value FROM settings WHERE `group` = :g AND `key` = :k LIMIT 1'
        );
        $stmt->execute([':g' => $group, ':k' => $key]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row !== false ? $row['value'] : $default;
    }

    private function saveSetting(string $group, string $key, mixed $value): void
    {
        $pdo  = app(\PDO::class);
        $stmt = $pdo->prepare(
            'INSERT INTO settings (`group`, `key`, value)
             VALUES (:g, :k, :v)
             ON DUPLICATE KEY UPDATE value = :v2'
        );
        $stmt->execute([
            ':g'  => $group,
            ':k'  => $key,
            ':v'  => (string) $value,
            ':v2' => (string) $value,
        ]);
    }

    private function getGroup(string $group): array
    {
        $pdo  = app(\PDO::class);
        $stmt = $pdo->prepare(
            'SELECT `key`, value FROM settings WHERE `group` = :g'
        );
        $stmt->execute([':g' => $group]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }

        return $out;
    }

    // =========================================================================
    // Actions
    // =========================================================================

    public function edit(): Response
    {
        $raw = $this->getGroup('company');

        $settings = [
            'company_name'     => $raw['company_name']     ?? '',
            'company_email'    => $raw['company_email']    ?? '',
            'company_phone'    => $raw['company_phone']    ?? '',
            'company_address'  => $raw['company_address']  ?? '',
            'company_website'  => $raw['company_website']  ?? '',
            'tax_number'       => $raw['tax_number']       ?? '',
            'trade_license'    => $raw['trade_license']    ?? '',
        ];

        return $this->render('settings/company', [
            'pageTitle'   => 'Company Settings',
            'breadcrumbs' => ['Settings' => '/settings', 'Company' => null],
            'settings'    => $settings,
            'activeTab'   => 'company',
        ]);
    }

    public function update(Request $request): Response
    {
        $data = $request->except(['_token', '_method']);

        $map = [
            'company_name', 'company_email', 'company_phone',
            'company_address', 'company_website', 'tax_number', 'trade_license',
        ];

        foreach ($map as $key) {
            if (array_key_exists($key, $data)) {
                $this->saveSetting('company', $key, $data[$key]);
            }
        }

        $this->success('Company settings saved successfully.');
        return $this->redirect('/settings/company');
    }

    public function uploadLogo(Request $request): Response
    {
        // Logo upload is intentionally a stub — file handling is outside scope.
        $this->error('Logo upload is not yet configured.');
        return $this->redirect('/settings/company');
    }
}
