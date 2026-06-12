<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

/**
 * EmailController
 *
 * Manages SMTP / email settings stored in the `settings` table under the
 * 'email' group.
 */
final class EmailController extends BaseController
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

    public function edit(): Response
    {
        $raw = $this->getGroup('email');

        $settings = [
            'smtp_host'       => $raw['smtp_host']       ?? '',
            'smtp_port'       => $raw['smtp_port']       ?? '587',
            'smtp_username'   => $raw['smtp_username']   ?? '',
            'smtp_password'   => $raw['smtp_password']   ?? '',
            'from_address'    => $raw['from_address']    ?? '',
            'from_name'       => $raw['from_name']       ?? '',
            'smtp_encryption' => $raw['smtp_encryption'] ?? 'tls',
        ];

        return $this->render('settings/email', [
            'pageTitle'   => 'Email Settings',
            'breadcrumbs' => ['Settings' => '/settings', 'Email' => null],
            'settings'    => $settings,
            'activeTab'   => 'email',
        ]);
    }

    public function update(Request $request): Response
    {
        $data = $request->except(['_token', '_method']);

        $map = [
            'smtp_host', 'smtp_port', 'smtp_username',
            'smtp_password', 'from_address', 'from_name', 'smtp_encryption',
        ];

        foreach ($map as $key) {
            if (array_key_exists($key, $data)) {
                $this->saveSetting('email', $key, $data[$key]);
            }
        }

        $this->success('Email settings saved successfully.');
        return $this->redirect('/settings/email');
    }

    public function sendTest(Request $request): Response
    {
        // Test email sending is intentionally a stub.
        $this->error('Test email functionality is not yet configured.');
        return $this->redirect('/settings/email');
    }
}
