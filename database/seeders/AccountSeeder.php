<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;

/**
 * AccountSeeder
 *
 * Seeds the `accounts` table with a standard Bangladesh Chart of Accounts.
 * Accounts are arranged in a parent-child hierarchy; parent rows are inserted
 * before their children because the upsert references parent IDs by code.
 *
 * Account types:   asset | liability | equity | revenue | expense
 * Normal balances: asset/expense → debit | liability/equity/revenue → credit
 */
final class AccountSeeder
{
    /**
     * Flat account definitions ordered so parents always appear before children.
     *
     * Keys:
     *   code        – unique ledger code (used as natural key for ON DUPLICATE KEY)
     *   name        – display name
     *   type        – asset | liability | equity | revenue | expense
     *   parent_code – code of the parent account (null for root accounts)
     *   is_system   – true for group/header accounts that should not be posted to directly
     *
     * @var list<array{code: string, name: string, type: string, parent_code: string|null, is_system: bool}>
     */
    private const ACCOUNTS = [
        // =====================================================================
        // ASSETS
        // =====================================================================
        ['code' => '1000', 'name' => 'Cash and Cash Equivalents',  'type' => 'asset', 'parent_code' => null,   'is_system' => true],
        ['code' => '1010', 'name' => 'Petty Cash',                  'type' => 'asset', 'parent_code' => '1000', 'is_system' => false],
        ['code' => '1020', 'name' => 'Cash in Hand',                'type' => 'asset', 'parent_code' => '1000', 'is_system' => false],
        ['code' => '1030', 'name' => 'Bank - Standard Chartered',   'type' => 'asset', 'parent_code' => '1000', 'is_system' => false],
        ['code' => '1100', 'name' => 'Accounts Receivable',         'type' => 'asset', 'parent_code' => null,   'is_system' => false],
        ['code' => '1200', 'name' => 'Inventory',                   'type' => 'asset', 'parent_code' => null,   'is_system' => false],
        ['code' => '1300', 'name' => 'Prepaid Expenses',            'type' => 'asset', 'parent_code' => null,   'is_system' => false],
        ['code' => '1500', 'name' => 'Property and Equipment',      'type' => 'asset', 'parent_code' => null,   'is_system' => true],
        ['code' => '1510', 'name' => 'Furniture and Fixtures',      'type' => 'asset', 'parent_code' => '1500', 'is_system' => false],
        ['code' => '1520', 'name' => 'Computer Equipment',          'type' => 'asset', 'parent_code' => '1500', 'is_system' => false],
        ['code' => '1600', 'name' => 'Accumulated Depreciation',    'type' => 'asset', 'parent_code' => null,   'is_system' => false],

        // =====================================================================
        // LIABILITIES
        // =====================================================================
        ['code' => '2000', 'name' => 'Accounts Payable',            'type' => 'liability', 'parent_code' => null, 'is_system' => false],
        ['code' => '2100', 'name' => 'Salaries Payable',            'type' => 'liability', 'parent_code' => null, 'is_system' => false],
        ['code' => '2200', 'name' => 'VAT Payable',                 'type' => 'liability', 'parent_code' => null, 'is_system' => false],
        ['code' => '2300', 'name' => 'Tax Payable',                 'type' => 'liability', 'parent_code' => null, 'is_system' => false],
        ['code' => '2400', 'name' => 'Short-term Loans',            'type' => 'liability', 'parent_code' => null, 'is_system' => false],
        ['code' => '2800', 'name' => 'Long-term Loans',             'type' => 'liability', 'parent_code' => null, 'is_system' => false],

        // =====================================================================
        // EQUITY
        // =====================================================================
        ['code' => '3000', 'name' => "Owner's Capital",             'type' => 'equity', 'parent_code' => null, 'is_system' => false],
        ['code' => '3100', 'name' => 'Retained Earnings',           'type' => 'equity', 'parent_code' => null, 'is_system' => false],
        ['code' => '3900', 'name' => 'Drawing',                     'type' => 'equity', 'parent_code' => null, 'is_system' => false],

        // =====================================================================
        // REVENUE
        // =====================================================================
        ['code' => '4000', 'name' => 'Sales Revenue',               'type' => 'revenue', 'parent_code' => null, 'is_system' => false],
        ['code' => '4100', 'name' => 'Service Revenue',             'type' => 'revenue', 'parent_code' => null, 'is_system' => false],
        ['code' => '4900', 'name' => 'Other Income',                'type' => 'revenue', 'parent_code' => null, 'is_system' => false],

        // =====================================================================
        // EXPENSES
        // =====================================================================
        ['code' => '5000', 'name' => 'Cost of Goods Sold',          'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5100', 'name' => 'Salaries and Wages',          'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5200', 'name' => 'Rent Expense',                'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5300', 'name' => 'Utilities Expense',           'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5400', 'name' => 'Marketing Expense',           'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5500', 'name' => 'Transport Expense',           'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5600', 'name' => 'Depreciation Expense',        'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5700', 'name' => 'Bank Charges',                'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5800', 'name' => 'VAT Expense',                 'type' => 'expense', 'parent_code' => null, 'is_system' => false],
        ['code' => '5900', 'name' => 'Other Expenses',              'type' => 'expense', 'parent_code' => null, 'is_system' => false],
    ];

    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // asset/expense accounts have a debit normal balance; others credit.
        $normalBalance = static function (string $type): string {
            return in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
        };

        $sql = <<<'SQL'
            INSERT INTO accounts
                (code, name, type, parent_id, normal_balance, is_system, balance, created_at, updated_at)
            VALUES
                (:code, :name, :type, :parent_id, :normal_balance, :is_system, 0.00, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                name           = VALUES(name),
                type           = VALUES(type),
                normal_balance = VALUES(normal_balance),
                is_system      = VALUES(is_system),
                updated_at     = VALUES(updated_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        // Cache code → id as we insert.
        $idByCode = [];

        foreach (self::ACCOUNTS as $account) {
            $parentId = null;

            if ($account['parent_code'] !== null) {
                // Parent must already be inserted.
                $parentId = $idByCode[$account['parent_code']] ?? $this->fetchIdByCode($account['parent_code']);
            }

            $stmt->execute([
                'code'           => $account['code'],
                'name'           => $account['name'],
                'type'           => $account['type'],
                'parent_id'      => $parentId,
                'normal_balance' => $normalBalance($account['type']),
                'is_system'      => $account['is_system'] ? 1 : 0,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            // Fetch back the real ID (handles both INSERT and UPDATE paths).
            $idByCode[$account['code']] = $this->fetchIdByCode($account['code']);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function fetchIdByCode(string $code): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM accounts WHERE code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? (int) $row['id'] : null;
    }
}
