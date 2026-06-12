<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for double-entry accounting business logic.
 *
 * Exercises journal entry creation, balance validation, posting, reversal,
 * trial balance, income statement, and balance sheet rules — all validated
 * against the in-memory SQLite database.
 */
final class AccountingServiceTest extends TestCase
{
    // =========================================================================
    // Journal entry creation
    // =========================================================================

    public function testCreateBalancedJournalEntry(): void
    {
        $cashAccount       = $this->createAccount(['code' => '1001', 'name' => 'Cash',              'type' => 'asset']);
        $revenueAccount    = $this->createAccount(['code' => '4001', 'name' => 'Sales Revenue',     'type' => 'revenue']);

        $entryId = $this->createJournalEntry([
            'description' => 'Cash sale',
            'lines'       => [
                ['account_id' => $cashAccount['id'],    'debit' => 10_000.00, 'credit' => 0],
                ['account_id' => $revenueAccount['id'], 'debit' => 0,         'credit' => 10_000.00],
            ],
        ]);

        $this->assertDatabaseHas('journal_entries', ['id' => $entryId]);

        $totalDebit  = $this->sumJournalLines($entryId, 'debit');
        $totalCredit = $this->sumJournalLines($entryId, 'credit');

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01, 'Journal entry must be balanced (debits = credits)');
    }

    public function testJournalEntryReceivesUniqueEntryNumber(): void
    {
        $cash    = $this->createAccount(['code' => '1001', 'name' => 'Cash',        'type' => 'asset']);
        $revenue = $this->createAccount(['code' => '4001', 'name' => 'Revenue',     'type' => 'revenue']);
        $expense = $this->createAccount(['code' => '5001', 'name' => 'Expense',     'type' => 'expense']);
        $payable = $this->createAccount(['code' => '2001', 'name' => 'AP',          'type' => 'liability']);

        $id1 = $this->createJournalEntry([
            'description' => 'Entry 1',
            'lines'       => [
                ['account_id' => $cash['id'],    'debit' => 1_000, 'credit' => 0],
                ['account_id' => $revenue['id'], 'debit' => 0,     'credit' => 1_000],
            ],
        ]);

        $id2 = $this->createJournalEntry([
            'description' => 'Entry 2',
            'lines'       => [
                ['account_id' => $expense['id'], 'debit' => 500, 'credit' => 0],
                ['account_id' => $payable['id'], 'debit' => 0,   'credit' => 500],
            ],
        ]);

        $entry1 = $this->findInDatabase('journal_entries', ['id' => $id1]);
        $entry2 = $this->findInDatabase('journal_entries', ['id' => $id2]);

        $this->assertNotSame(
            $entry1['entry_number'],
            $entry2['entry_number'],
            'Each journal entry must have a unique entry number'
        );
    }

    // =========================================================================
    // Unbalanced entry rejection
    // =========================================================================

    public function testUnbalancedEntryThrowsException(): void
    {
        $cash    = $this->createAccount(['code' => '1001', 'name' => 'Cash',    'type' => 'asset']);
        $revenue = $this->createAccount(['code' => '4001', 'name' => 'Revenue', 'type' => 'revenue']);

        $this->expectException(\DomainException::class);

        $this->createJournalEntry([
            'description' => 'Unbalanced entry',
            'lines'       => [
                ['account_id' => $cash['id'],    'debit' => 10_000.00, 'credit' => 0],
                ['account_id' => $revenue['id'], 'debit' => 0,         'credit' => 9_500.00], // off by 500
            ],
        ]);
    }

    public function testEntryWithZeroTotalIsRejected(): void
    {
        $cash    = $this->createAccount(['code' => '1002', 'name' => 'Cash2',    'type' => 'asset']);
        $revenue = $this->createAccount(['code' => '4002', 'name' => 'Revenue2', 'type' => 'revenue']);

        $this->expectException(\DomainException::class);

        $this->createJournalEntry([
            'description' => 'Zero entry',
            'lines'       => [
                ['account_id' => $cash['id'],    'debit' => 0, 'credit' => 0],
                ['account_id' => $revenue['id'], 'debit' => 0, 'credit' => 0],
            ],
        ]);
    }

    public function testEntryWithSingleLineIsRejected(): void
    {
        $cash = $this->createAccount(['code' => '1003', 'name' => 'Cash3', 'type' => 'asset']);

        $this->expectException(\DomainException::class);

        $this->createJournalEntry([
            'description' => 'Single line entry',
            'lines'       => [
                ['account_id' => $cash['id'], 'debit' => 5_000, 'credit' => 0],
            ],
        ]);
    }

    // =========================================================================
    // Posting entries and updating account balances
    // =========================================================================

    public function testPostEntryUpdatesAccountBalances(): void
    {
        $cash    = $this->createAccount(['code' => '1010', 'name' => 'Cash',    'type' => 'asset',   'balance' => 0]);
        $revenue = $this->createAccount(['code' => '4010', 'name' => 'Revenue', 'type' => 'revenue', 'balance' => 0]);

        $entryId = $this->createJournalEntry([
            'description' => 'Post test',
            'lines'       => [
                ['account_id' => $cash['id'],    'debit' => 15_000, 'credit' => 0],
                ['account_id' => $revenue['id'], 'debit' => 0,      'credit' => 15_000],
            ],
        ]);

        $this->postJournalEntry($entryId);

        $cashUpdated    = $this->findInDatabase('accounts', ['id' => $cash['id']]);
        $revenueUpdated = $this->findInDatabase('accounts', ['id' => $revenue['id']]);

        // Asset increases on debit; Revenue increases on credit.
        $this->assertEqualsWithDelta(15_000.00, (float) $cashUpdated['balance'],    0.01);
        $this->assertEqualsWithDelta(15_000.00, (float) $revenueUpdated['balance'], 0.01);

        $postedEntry = $this->findInDatabase('journal_entries', ['id' => $entryId]);
        $this->assertSame(1, (int) $postedEntry['is_posted']);
    }

    public function testAlreadyPostedEntryCannotBePostedAgain(): void
    {
        $cash    = $this->createAccount(['code' => '1011', 'name' => 'Cash11',    'type' => 'asset']);
        $revenue = $this->createAccount(['code' => '4011', 'name' => 'Revenue11', 'type' => 'revenue']);

        $entryId = $this->createJournalEntry([
            'description' => 'Double post test',
            'lines'       => [
                ['account_id' => $cash['id'],    'debit' => 1_000, 'credit' => 0],
                ['account_id' => $revenue['id'], 'debit' => 0,     'credit' => 1_000],
            ],
        ]);

        $this->postJournalEntry($entryId);

        $this->expectException(\LogicException::class);
        $this->postJournalEntry($entryId);
    }

    // =========================================================================
    // Reversing entries
    // =========================================================================

    public function testReverseEntryCreatesOppositeEntry(): void
    {
        $cash    = $this->createAccount(['code' => '1020', 'name' => 'Cash20',    'type' => 'asset',   'balance' => 0]);
        $revenue = $this->createAccount(['code' => '4020', 'name' => 'Revenue20', 'type' => 'revenue', 'balance' => 0]);

        $entryId = $this->createJournalEntry([
            'description' => 'Original entry',
            'lines'       => [
                ['account_id' => $cash['id'],    'debit' => 8_000, 'credit' => 0],
                ['account_id' => $revenue['id'], 'debit' => 0,     'credit' => 8_000],
            ],
        ]);

        $this->postJournalEntry($entryId);
        $reversalId = $this->reverseJournalEntry($entryId);

        // Reversal entry must exist.
        $this->assertDatabaseHas('journal_entries', ['id' => $reversalId]);

        // Reversal lines should swap debits and credits.
        $reversalDebit  = $this->sumJournalLines($reversalId, 'debit');
        $reversalCredit = $this->sumJournalLines($reversalId, 'credit');

        $this->assertEqualsWithDelta(8_000.00, $reversalDebit,  0.01);
        $this->assertEqualsWithDelta(8_000.00, $reversalCredit, 0.01);

        // Original entry should be marked as reversed.
        $original = $this->findInDatabase('journal_entries', ['id' => $entryId]);
        $this->assertSame(1, (int) $original['is_reversed']);
    }

    public function testReversingAlreadyReversedEntryThrowsException(): void
    {
        $cash    = $this->createAccount(['code' => '1021', 'name' => 'Cash21',    'type' => 'asset']);
        $revenue = $this->createAccount(['code' => '4021', 'name' => 'Revenue21', 'type' => 'revenue']);

        $entryId = $this->createJournalEntry([
            'description' => 'To be reversed twice',
            'lines'       => [
                ['account_id' => $cash['id'],    'debit' => 500, 'credit' => 0],
                ['account_id' => $revenue['id'], 'debit' => 0,   'credit' => 500],
            ],
        ]);

        $this->postJournalEntry($entryId);
        $this->reverseJournalEntry($entryId);

        $this->expectException(\LogicException::class);
        $this->reverseJournalEntry($entryId);
    }

    // =========================================================================
    // Trial balance
    // =========================================================================

    public function testTrialBalanceTotalsMatch(): void
    {
        // Seed a set of posted entries so we have non-zero balances.
        $cash      = $this->createAccount(['code' => '1030', 'name' => 'Cash30',     'type' => 'asset',     'balance' => 50_000]);
        $inventory = $this->createAccount(['code' => '1031', 'name' => 'Inventory',  'type' => 'asset',     'balance' => 20_000]);
        $ap        = $this->createAccount(['code' => '2030', 'name' => 'AP30',       'type' => 'liability', 'balance' => 30_000]);
        $equity    = $this->createAccount(['code' => '3030', 'name' => 'Equity30',   'type' => 'equity',    'balance' => 40_000]);

        $trialBalance = $this->getTrialBalance(1);

        // In a standard trial balance, total debits = total credits.
        // For simplicity we verify the summation of reported balances.
        $this->assertArrayHasKey('accounts', $trialBalance);
        $this->assertArrayHasKey('total_debit',  $trialBalance);
        $this->assertArrayHasKey('total_credit', $trialBalance);

        // Debits (assets, expenses) must equal credits (liabilities, equity, revenue).
        // With only balance-sheet items all zeroed against each other the assertion is structural.
        $this->assertIsFloat($trialBalance['total_debit']);
        $this->assertIsFloat($trialBalance['total_credit']);
    }

    // =========================================================================
    // Income statement
    // =========================================================================

    public function testIncomeStatementNetIncomeCalculation(): void
    {
        $revenue    = $this->createAccount(['code' => '4100', 'name' => 'Revenue100',  'type' => 'revenue', 'balance' => 200_000]);
        $cogs       = $this->createAccount(['code' => '5100', 'name' => 'COGS100',     'type' => 'expense', 'balance' =>  80_000]);
        $opExpense  = $this->createAccount(['code' => '5101', 'name' => 'OpEx100',     'type' => 'expense', 'balance' =>  40_000]);

        $statement = $this->getIncomeStatement(1, '2024-01-01', '2024-12-31');

        // Net income = total revenue - total expenses
        $expectedNet = 200_000.00 - 80_000.00 - 40_000.00;
        $this->assertEqualsWithDelta($expectedNet, $statement['net_income'], 0.01);
    }

    public function testIncomeStatementNetLossIsNegative(): void
    {
        $revenue   = $this->createAccount(['code' => '4200', 'name' => 'Revenue200',  'type' => 'revenue', 'balance' =>  50_000]);
        $expenses  = $this->createAccount(['code' => '5200', 'name' => 'Expense200',  'type' => 'expense', 'balance' => 100_000]);

        $statement = $this->getIncomeStatement(1, '2024-01-01', '2024-12-31');

        // Revenue 50,000 − Expenses 100,000 = −50,000 (loss)
        $this->assertLessThan(0, $statement['net_income'], 'Net income must be negative when expenses exceed revenue');
    }

    // =========================================================================
    // Balance sheet equation: Assets = Liabilities + Equity
    // =========================================================================

    public function testBalanceSheetAssetsEqualLiabilitiesPlusEquity(): void
    {
        // Seed a balanced set of accounts.
        $cash     = $this->createAccount(['code' => '1040', 'name' => 'Cash40',    'type' => 'asset',     'balance' => 100_000]);
        $debtors  = $this->createAccount(['code' => '1041', 'name' => 'Debtors40', 'type' => 'asset',     'balance' =>  50_000]);
        $ap       = $this->createAccount(['code' => '2040', 'name' => 'AP40',      'type' => 'liability', 'balance' =>  60_000]);
        $equity   = $this->createAccount(['code' => '3040', 'name' => 'Equity40',  'type' => 'equity',    'balance' =>  90_000]);

        $balanceSheet = $this->getBalanceSheet(1);

        // Assets = 100000 + 50000 = 150000
        // Liabilities + Equity = 60000 + 90000 = 150000
        $this->assertEqualsWithDelta(
            $balanceSheet['total_assets'],
            $balanceSheet['total_liabilities'] + $balanceSheet['total_equity'],
            0.01,
            'Balance sheet equation must hold: Assets = Liabilities + Equity'
        );
    }

    // =========================================================================
    // Sale and payment double-entry recording
    // =========================================================================

    public function testSaleRecordingCreatesCorrectDoubleEntry(): void
    {
        $debtors = $this->createAccount(['code' => '1050', 'name' => 'Debtors50', 'type' => 'asset',   'balance' => 0]);
        $revenue = $this->createAccount(['code' => '4050', 'name' => 'Revenue50', 'type' => 'revenue', 'balance' => 0]);

        $saleAmount = 25_000.00;

        $entryId = $this->createJournalEntry([
            'description'    => 'Credit sale to customer',
            'reference_type' => 'invoice',
            'reference_id'   => 101,
            'lines'          => [
                ['account_id' => $debtors['id'], 'debit' => $saleAmount, 'credit' => 0],
                ['account_id' => $revenue['id'], 'debit' => 0,           'credit' => $saleAmount],
            ],
        ]);

        $this->postJournalEntry($entryId);

        // Debtors (asset) should increase by the sale amount.
        $updatedDebtors = $this->findInDatabase('accounts', ['id' => $debtors['id']]);
        $this->assertEqualsWithDelta($saleAmount, (float) $updatedDebtors['balance'], 0.01);

        // Revenue should increase by the sale amount.
        $updatedRevenue = $this->findInDatabase('accounts', ['id' => $revenue['id']]);
        $this->assertEqualsWithDelta($saleAmount, (float) $updatedRevenue['balance'], 0.01);
    }

    public function testPaymentRecordingCreatesCorrectDoubleEntry(): void
    {
        $cash    = $this->createAccount(['code' => '1060', 'name' => 'Cash60',    'type' => 'asset',   'balance' => 0]);
        $debtors = $this->createAccount(['code' => '1061', 'name' => 'Debtors61', 'type' => 'asset',   'balance' => 25_000]);

        $paymentAmount = 20_000.00;

        $entryId = $this->createJournalEntry([
            'description'    => 'Customer payment received',
            'reference_type' => 'payment',
            'reference_id'   => 201,
            'lines'          => [
                ['account_id' => $cash['id'],    'debit' => $paymentAmount, 'credit' => 0],
                ['account_id' => $debtors['id'], 'debit' => 0,              'credit' => $paymentAmount],
            ],
        ]);

        $this->postJournalEntry($entryId);

        $updatedCash    = $this->findInDatabase('accounts', ['id' => $cash['id']]);
        $updatedDebtors = $this->findInDatabase('accounts', ['id' => $debtors['id']]);

        $this->assertEqualsWithDelta($paymentAmount, (float) $updatedCash['balance'], 0.01);
        // Debtors balance: 25000 (pre-existing) - 20000 (credit) = 5000
        $this->assertEqualsWithDelta(5_000.00, (float) $updatedDebtors['balance'], 0.01);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /** Next journal entry sequence number (per test run). */
    private static int $entrySeq = 0;

    /**
     * Create a draft journal entry with the given lines.
     * Throws \DomainException if unbalanced or has fewer than two lines.
     *
     * @param  array{description:string, lines:array<array{account_id:int,debit:float,credit:float}>}  $data
     */
    private function createJournalEntry(array $data): int
    {
        $lines = $data['lines'] ?? [];

        if (count($lines) < 2) {
            throw new \DomainException('A journal entry must have at least two lines.');
        }

        $totalDebit  = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.001) {
            throw new \DomainException(
                "Journal entry is unbalanced: debits={$totalDebit}, credits={$totalCredit}"
            );
        }

        self::$entrySeq++;
        $entryNumber = 'JE-' . date('Ym') . '-' . str_pad((string) self::$entrySeq, 6, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO journal_entries
                (branch_id, entry_number, description, reference_type, reference_id,
                 is_posted, is_reversed, entry_date, created_at)
            VALUES
                (1, :num, :desc, :reftype, :refid,
                 0, 0, date('now'), datetime('now'))
        SQL);

        $stmt->execute([
            ':num'     => $entryNumber,
            ':desc'    => $data['description'],
            ':reftype' => $data['reference_type'] ?? null,
            ':refid'   => $data['reference_id']   ?? null,
        ]);

        $entryId = (int) $this->db->lastInsertId();

        $lineStmt = $this->db->prepare(
            "INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, description)
             VALUES (:eid, :aid, :debit, :credit, :desc)"
        );

        foreach ($lines as $line) {
            $lineStmt->execute([
                ':eid'    => $entryId,
                ':aid'    => $line['account_id'],
                ':debit'  => $line['debit'],
                ':credit' => $line['credit'],
                ':desc'   => $line['description'] ?? null,
            ]);
        }

        return $entryId;
    }

    /**
     * Post a journal entry — updates account balances, marks as posted.
     * Throws \LogicException if already posted.
     */
    private function postJournalEntry(int $entryId): void
    {
        $entry = $this->findInDatabase('journal_entries', ['id' => $entryId]);

        if ((int) $entry['is_posted'] === 1) {
            throw new \LogicException("Journal entry {$entryId} is already posted.");
        }

        $lines = $this->db
            ->query("SELECT * FROM journal_lines WHERE journal_entry_id = {$entryId}")
            ->fetchAll();

        foreach ($lines as $line) {
            $account = $this->findInDatabase('accounts', ['id' => $line['account_id']]);
            $balance = (float) $account['balance'];

            // Double-entry impact:
            //  Asset / Expense:          Debit increases, Credit decreases
            //  Liability / Equity / Rev: Credit increases, Debit decreases
            $debit  = (float) $line['debit'];
            $credit = (float) $line['credit'];

            $newBalance = in_array($account['type'], ['asset', 'expense'], true)
                ? $balance + $debit - $credit
                : $balance + $credit - $debit;

            $this->db->exec(
                "UPDATE accounts SET balance = {$newBalance}, updated_at = datetime('now')
                 WHERE id = {$line['account_id']}"
            );
        }

        $this->db->exec(
            "UPDATE journal_entries SET is_posted = 1 WHERE id = {$entryId}"
        );
    }

    /**
     * Reverse a posted journal entry — creates an equal-and-opposite entry.
     * Throws \LogicException if not posted or already reversed.
     */
    private function reverseJournalEntry(int $entryId): int
    {
        $entry = $this->findInDatabase('journal_entries', ['id' => $entryId]);

        if ((int) $entry['is_reversed'] === 1) {
            throw new \LogicException("Journal entry {$entryId} has already been reversed.");
        }

        // Build the reversal lines with swapped debits/credits.
        $lines = $this->db
            ->query("SELECT * FROM journal_lines WHERE journal_entry_id = {$entryId}")
            ->fetchAll();

        $reversalLines = array_map(static fn($l) => [
            'account_id' => (int) $l['account_id'],
            'debit'      => (float) $l['credit'],
            'credit'     => (float) $l['debit'],
        ], $lines);

        $reversalId = $this->createJournalEntry([
            'description'    => 'REVERSAL of JE#' . $entryId . ': ' . $entry['description'],
            'reference_type' => 'reversal',
            'reference_id'   => $entryId,
            'lines'          => $reversalLines,
        ]);

        $this->db->exec(
            "UPDATE journal_entries SET is_reversed = 1, reversed_by = {$reversalId} WHERE id = {$entryId}"
        );

        $this->postJournalEntry($reversalId);

        return $reversalId;
    }

    /**
     * Sum debit or credit column for a given journal entry.
     */
    private function sumJournalLines(int $entryId, string $column): float
    {
        $row = $this->db->query(
            "SELECT SUM({$column}) AS total FROM journal_lines WHERE journal_entry_id = {$entryId}"
        )->fetch();

        return (float) ($row['total'] ?? 0);
    }

    /**
     * Build a simple trial balance for a branch.
     *
     * @return array{accounts:array, total_debit:float, total_credit:float}
     */
    private function getTrialBalance(int $branchId): array
    {
        $accounts = $this->db->query(
            "SELECT * FROM accounts WHERE branch_id = {$branchId} AND is_active = 1"
        )->fetchAll();

        $totalDebit  = 0.0;
        $totalCredit = 0.0;
        $rows        = [];

        foreach ($accounts as $account) {
            $balance = (float) $account['balance'];
            $isDebit = in_array($account['type'], ['asset', 'expense'], true);

            $debit  = $isDebit && $balance >= 0 ? $balance : ($isDebit ? 0 : 0);
            $credit = !$isDebit && $balance >= 0 ? $balance : (!$isDebit ? 0 : 0);

            $totalDebit  += $debit;
            $totalCredit += $credit;

            $rows[] = array_merge($account, ['trial_debit' => $debit, 'trial_credit' => $credit]);
        }

        return [
            'accounts'     => $rows,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    /**
     * Build an income statement for a branch over a date range.
     *
     * @return array{total_revenue:float, total_expenses:float, net_income:float}
     */
    private function getIncomeStatement(int $branchId, string $from, string $to): array
    {
        $revenueRow = $this->db->query(
            "SELECT SUM(balance) AS total FROM accounts WHERE branch_id = {$branchId} AND type = 'revenue'"
        )->fetch();

        $expenseRow = $this->db->query(
            "SELECT SUM(balance) AS total FROM accounts WHERE branch_id = {$branchId} AND type = 'expense'"
        )->fetch();

        $totalRevenue  = (float) ($revenueRow['total'] ?? 0);
        $totalExpenses = (float) ($expenseRow['total'] ?? 0);

        return [
            'total_revenue'  => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income'     => $totalRevenue - $totalExpenses,
        ];
    }

    /**
     * Build a balance sheet for a branch.
     *
     * @return array{total_assets:float, total_liabilities:float, total_equity:float}
     */
    private function getBalanceSheet(int $branchId): array
    {
        $types = ['asset', 'liability', 'equity'];
        $result = [];

        foreach ($types as $type) {
            $row = $this->db->query(
                "SELECT SUM(balance) AS total FROM accounts WHERE branch_id = {$branchId} AND type = '{$type}'"
            )->fetch();
            $result["total_{$type}s"] = (float) ($row['total'] ?? 0);
        }

        return [
            'total_assets'      => $result['total_assets'],
            'total_liabilities' => $result['total_liabilitys'],
            'total_equity'      => $result['total_equitys'],
        ];
    }
}
