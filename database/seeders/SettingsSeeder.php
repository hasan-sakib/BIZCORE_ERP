<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;

/**
 * SettingsSeeder
 *
 * Inserts default application settings into the `settings` table.
 * Each setting is identified by a unique key; existing keys are updated
 * only if the current value is NULL (first-run safe).
 */
final class SettingsSeeder
{
    /**
     * Default settings.
     *
     * 'type' controls how the UI renders and validates the value:
     *   string | integer | float | boolean | time | select
     *
     * @var list<array{key: string, value: string, type: string, group: string, description: string}>
     */
    private const DEFAULTS = [
        // -----------------------------------------------------------------
        // General
        // -----------------------------------------------------------------
        ['key' => 'app_name',            'value' => 'BizCore ERP', 'type' => 'string',  'group' => 'general',  'description' => 'Application display name'],
        ['key' => 'app_currency',        'value' => 'BDT',         'type' => 'string',  'group' => 'general',  'description' => 'ISO 4217 currency code'],
        ['key' => 'app_currency_symbol', 'value' => '৳',           'type' => 'string',  'group' => 'general',  'description' => 'Currency symbol shown in UI'],
        ['key' => 'app_date_format',     'value' => 'd/m/Y',       'type' => 'string',  'group' => 'general',  'description' => 'PHP date format string used site-wide'],

        // -----------------------------------------------------------------
        // HR / Attendance
        // -----------------------------------------------------------------
        ['key' => 'working_hours_start',      'value' => '09:00', 'type' => 'string',    'group' => 'hr', 'description' => 'Default shift start time (HH:MM, 24h)'],
        ['key' => 'working_hours_end',        'value' => '18:00', 'type' => 'string',    'group' => 'hr', 'description' => 'Default shift end time (HH:MM, 24h)'],
        ['key' => 'overtime_threshold_hours', 'value' => '8',     'type' => 'integer', 'group' => 'hr', 'description' => 'Daily hours after which overtime rate applies'],

        // -----------------------------------------------------------------
        // Tax / VAT
        // -----------------------------------------------------------------
        ['key' => 'vat_enabled',      'value' => 'true', 'type' => 'boolean', 'group' => 'tax', 'description' => 'Enable VAT calculation on invoices'],
        ['key' => 'default_vat_rate', 'value' => '15',   'type' => 'string',   'group' => 'tax', 'description' => 'Default VAT percentage (Bangladesh standard rate)'],

        // -----------------------------------------------------------------
        // Numbering prefixes
        // -----------------------------------------------------------------
        ['key' => 'invoice_prefix',  'value' => 'INV',  'type' => 'string', 'group' => 'numbering', 'description' => 'Prefix for invoice numbers'],
        ['key' => 'po_prefix',       'value' => 'PO',   'type' => 'string', 'group' => 'numbering', 'description' => 'Prefix for purchase order numbers'],
        ['key' => 'gr_prefix',       'value' => 'GRN',  'type' => 'string', 'group' => 'numbering', 'description' => 'Prefix for goods receipt notes'],
        ['key' => 'payment_prefix',  'value' => 'PAY',  'type' => 'string', 'group' => 'numbering', 'description' => 'Prefix for payment vouchers'],
        ['key' => 'expense_prefix',  'value' => 'EXP',  'type' => 'string', 'group' => 'numbering', 'description' => 'Prefix for expense claims'],
        ['key' => 'employee_prefix', 'value' => 'EMP',  'type' => 'string', 'group' => 'numbering', 'description' => 'Prefix for employee IDs'],
        ['key' => 'customer_prefix', 'value' => 'CUST', 'type' => 'string', 'group' => 'numbering', 'description' => 'Prefix for customer codes'],
        ['key' => 'supplier_prefix', 'value' => 'SUPP', 'type' => 'string', 'group' => 'numbering', 'description' => 'Prefix for supplier codes'],

        // -----------------------------------------------------------------
        // Inventory
        // -----------------------------------------------------------------
        ['key' => 'low_stock_threshold', 'value' => '10', 'type' => 'integer', 'group' => 'inventory', 'description' => 'Alert when stock-on-hand falls at or below this quantity'],

        // -----------------------------------------------------------------
        // Security
        // -----------------------------------------------------------------
        ['key' => 'password_min_length', 'value' => '8',   'type' => 'integer', 'group' => 'security', 'description' => 'Minimum characters required for user passwords'],
        ['key' => 'session_timeout',     'value' => '120', 'type' => 'integer', 'group' => 'security', 'description' => 'Idle session timeout in minutes'],
        ['key' => 'max_login_attempts',  'value' => '5',   'type' => 'integer', 'group' => 'security', 'description' => 'Consecutive failed logins before account lock'],
    ];

    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Insert only; never overwrite values an admin has already customised.
        $sql = <<<'SQL'
            INSERT INTO settings (`key`, `value`, `type`, `group`, `description`, created_at, updated_at)
            VALUES (:key, :value, :type, :group, :description, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                type        = VALUES(type),
                `group`     = VALUES(`group`),
                description = VALUES(description),
                updated_at  = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        foreach (self::DEFAULTS as $setting) {
            $stmt->execute([
                'key'         => $setting['key'],
                'value'       => $setting['value'],
                'type'        => $setting['type'],
                'group'       => $setting['group'],
                'description' => $setting['description'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }
}
