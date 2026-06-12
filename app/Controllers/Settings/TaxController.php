<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

/**
 * TaxController
 *
 * Manages VAT/tax settings stored in the `settings` table under the 'tax' group.
 */
final class TaxController extends BaseController
{
    // =========================================================================
    // Settings helpers
    // =========================================================================

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

    public function index(): Response
    {
        $raw = $this->getGroup('tax');

        $settings = [
            'vat_enabled'          => $raw['vat_enabled']          ?? '0',
            'default_vat_rate'     => $raw['default_vat_rate']     ?? '0',
            'vat_registration_no'  => $raw['vat_registration_no']  ?? '',
            'tax_year_start_month' => $raw['tax_year_start_month'] ?? '1',
        ];

        return $this->render('settings/tax', [
            'pageTitle'   => 'Tax & VAT Settings',
            'breadcrumbs' => ['Settings' => '/settings', 'Tax' => null],
            'settings'    => $settings,
            'activeTab'   => 'tax',
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $request->except(['_token', '_method']);

        $map = [
            'vat_enabled', 'default_vat_rate',
            'vat_registration_no', 'tax_year_start_month',
        ];

        // Checkbox: if not submitted the key will be absent; treat absence as '0'.
        $data['vat_enabled'] = isset($data['vat_enabled']) ? '1' : '0';

        foreach ($map as $key) {
            $this->saveSetting('tax', $key, $data[$key] ?? '');
        }

        $this->success('Tax settings saved successfully.');
        return $this->redirect('/settings/tax');
    }

    // Stubs for routes that are registered but not needed for the forms above.
    public function update(Request $request, int $id): Response
    {
        return $this->redirect('/settings/tax');
    }

    public function destroy(int $id): Response
    {
        return $this->redirect('/settings/tax');
    }
}
