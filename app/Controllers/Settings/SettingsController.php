<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

/**
 * SettingsController
 *
 * Handles general application settings stored in the `settings` table.
 */
final class SettingsController extends BaseController
{
    // =========================================================================
    // Settings helpers
    // =========================================================================

    /**
     * Read a single setting value from the database.
     */
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

    /**
     * Upsert a single setting value into the database.
     */
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

    /**
     * Read all settings for a given group into a flat key => value array.
     *
     * @return array<string, string>
     */
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
    // Index (redirect to general)
    // =========================================================================

    public function index(): Response
    {
        return $this->redirect('/settings/general');
    }

    // =========================================================================
    // General Settings
    // =========================================================================

    public function general(): Response
    {
        $raw = $this->getGroup('general');

        $settings = [
            'app_name'         => $raw['app_name']         ?? 'BizCore ERP',
            'app_timezone'     => $raw['app_timezone']     ?? 'Asia/Dhaka',
            'date_format'      => $raw['date_format']      ?? 'd/m/Y',
            'currency'         => $raw['currency']         ?? 'BDT',
            'currency_symbol'  => $raw['currency_symbol']  ?? '৳',
            'pagination_limit' => $raw['pagination_limit'] ?? '20',
        ];

        return $this->render('settings/general', [
            'pageTitle'   => 'General Settings',
            'breadcrumbs' => ['Settings' => '/settings', 'General' => null],
            'settings'    => $settings,
            'activeTab'   => 'general',
        ]);
    }

    public function updateGeneral(Request $request): Response
    {
        $data = $request->except(['_token', '_method']);

        $map = [
            'app_name', 'app_timezone', 'date_format',
            'currency', 'currency_symbol', 'pagination_limit',
        ];

        foreach ($map as $key) {
            if (isset($data[$key])) {
                $this->saveSetting('general', $key, $data[$key]);
            }
        }

        $this->success('General settings saved successfully.');
        return $this->redirect('/settings/general');
    }
}
