<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Entities\JournalEntry;

class AccountingService
{
    public function __construct(private readonly Database $db) {}

    public function createEntry(array $data): JournalEntry
    {
        $lines = $data['lines'] ?? [];
        $totalDebit  = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \RuntimeException(
                sprintf('Unbalanced journal entry: Debit %.2f ≠ Credit %.2f', $totalDebit, $totalCredit)
            );
        }

        return $this->db->transaction(function () use ($data, $lines, $totalDebit, $totalCredit) {
            $entryNumber = $this->generateEntryNumber($data['branch_id']);

            $entryId = $this->db->table('journal_entries')->insert([
                'branch_id'      => (int)$data['branch_id'],
                'entry_number'   => $entryNumber,
                'date'           => $data['date'] ?? date('Y-m-d'),
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id'   => $data['reference_id'] ?? null,
                'description'    => $data['description'] ?? null,
                'total_debit'    => round($totalDebit, 2),
                'total_credit'   => round($totalCredit, 2),
                'status'         => 'draft',
                'created_by'     => (int)$data['created_by'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($lines as $line) {
                $this->db->table('journal_entry_lines')->insert([
                    'journal_entry_id' => $entryId,
                    'account_id'       => (int)$line['account_id'],
                    'debit'            => (float)($line['debit'] ?? 0),
                    'credit'           => (float)($line['credit'] ?? 0),
                    'description'      => $line['description'] ?? null,
                    'branch_id'        => (int)$data['branch_id'],
                ]);
            }

            return $this->findEntryById($entryId);
        });
    }

    public function postEntry(int $entryId, int $postedBy): void
    {
        $entry = $this->findEntryById($entryId);
        if (!$entry || $entry->status !== 'draft') {
            throw new \RuntimeException("Entry must be in draft status to post.");
        }

        $this->db->transaction(function () use ($entry, $entryId, $postedBy) {
            $lines = $this->db->fetchAll(
                "SELECT * FROM journal_entry_lines WHERE journal_entry_id = ?",
                [$entryId]
            );

            foreach ($lines as $line) {
                $account = $this->db->table('accounts')->where('id', (int)$line['account_id'])->first();
                if (!$account) continue;

                $isDebitNormal = $account['normal_balance'] === 'debit';
                $netChange = $isDebitNormal
                    ? (float)$line['debit'] - (float)$line['credit']
                    : (float)$line['credit'] - (float)$line['debit'];

                $this->db->execute(
                    "UPDATE accounts SET balance = balance + ?, updated_at = ? WHERE id = ?",
                    [$netChange, now(), (int)$line['account_id']]
                );
            }

            $this->db->table('journal_entries')->where('id', $entryId)->update([
                'status'    => 'posted',
                'posted_by' => $postedBy,
                'posted_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function reverseEntry(int $entryId, int $reversedBy, string $reason): JournalEntry
    {
        $original = $this->findEntryById($entryId);
        if (!$original || $original->status !== 'posted') {
            throw new \RuntimeException("Only posted entries can be reversed.");
        }

        $lines = $this->db->fetchAll(
            "SELECT * FROM journal_entry_lines WHERE journal_entry_id = ?",
            [$entryId]
        );

        $reversalLines = array_map(fn($l) => [
            'account_id'  => (int)$l['account_id'],
            'debit'       => (float)$l['credit'],
            'credit'      => (float)$l['debit'],
            'description' => "Reversal: " . ($l['description'] ?? ''),
        ], $lines);

        $reversal = $this->createEntry([
            'branch_id'      => $original->branchId,
            'date'           => date('Y-m-d'),
            'reference_type' => 'reversal',
            'reference_id'   => $entryId,
            'description'    => "Reversal of {$original->entryNumber}: {$reason}",
            'lines'          => $reversalLines,
            'created_by'     => $reversedBy,
        ]);

        $this->postEntry($reversal->id, $reversedBy);

        $this->db->table('journal_entries')->where('id', $entryId)->update([
            'status'      => 'reversed',
            'reversed_by' => $reversedBy,
            'reversed_at' => now(),
            'updated_at'  => now(),
        ]);

        return $reversal;
    }

    public function getTrialBalance(string $asOfDate, ?int $branchId = null): array
    {
        $rows = $this->db->fetchAll(
            "SELECT a.code, a.name, a.type, a.normal_balance,
                    COALESCE(SUM(jel.debit), 0) AS total_debit,
                    COALESCE(SUM(jel.credit), 0) AS total_credit,
                    (COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0)) AS net
             FROM accounts a
             LEFT JOIN journal_entry_lines jel ON jel.account_id = a.id
             LEFT JOIN journal_entries je ON je.id = jel.journal_entry_id
                 AND je.status = 'posted'
                 AND je.date <= ?
             WHERE a.is_active = 1
             GROUP BY a.id, a.code, a.name, a.type, a.normal_balance
             HAVING total_debit > 0 OR total_credit > 0
             ORDER BY a.code ASC",
            [$asOfDate]
        );

        $totalDebit  = array_sum(array_column($rows, 'total_debit'));
        $totalCredit = array_sum(array_column($rows, 'total_credit'));

        return [
            'accounts'     => $rows,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced'  => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    public function getIncomeStatement(string $fromDate, string $toDate, ?int $branchId = null): array
    {
        $revenues = $this->getAccountBalances(['revenue'], $fromDate, $toDate);
        $expenses = $this->getAccountBalances(['expense'], $fromDate, $toDate);

        $totalRevenue = array_sum(array_column($revenues, 'net_change'));
        $totalExpense = array_sum(array_column($expenses, 'net_change'));
        $netIncome    = $totalRevenue - $totalExpense;

        return [
            'revenues'      => $revenues,
            'expenses'      => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income'    => $netIncome,
            'period'        => ['from' => $fromDate, 'to' => $toDate],
        ];
    }

    public function getBalanceSheet(string $asOfDate, ?int $branchId = null): array
    {
        $assets      = $this->getAccountBalances(['asset'], '1900-01-01', $asOfDate);
        $liabilities = $this->getAccountBalances(['liability'], '1900-01-01', $asOfDate);
        $equity      = $this->getAccountBalances(['equity'], '1900-01-01', $asOfDate);

        $totalAssets      = array_sum(array_column($assets, 'net_change'));
        $totalLiabilities = array_sum(array_column($liabilities, 'net_change'));
        $totalEquity      = array_sum(array_column($equity, 'net_change'));

        return [
            'assets'           => $assets,
            'liabilities'      => $liabilities,
            'equity'           => $equity,
            'total_assets'     => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity'     => $totalEquity,
            'is_balanced'      => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
            'as_of'            => $asOfDate,
        ];
    }

    private function getAccountBalances(array $types, string $fromDate, string $toDate): array
    {
        $typePlaceholders = implode(',', array_fill(0, count($types), '?'));
        return $this->db->fetchAll(
            "SELECT a.code, a.name, a.type, a.normal_balance,
                    COALESCE(SUM(jel.debit), 0) AS total_debit,
                    COALESCE(SUM(jel.credit), 0) AS total_credit,
                    CASE WHEN a.normal_balance = 'debit'
                         THEN COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0)
                         ELSE COALESCE(SUM(jel.credit), 0) - COALESCE(SUM(jel.debit), 0)
                    END AS net_change
             FROM accounts a
             LEFT JOIN journal_entry_lines jel ON jel.account_id = a.id
             LEFT JOIN journal_entries je ON je.id = jel.journal_entry_id
                 AND je.status = 'posted' AND je.date BETWEEN ? AND ?
             WHERE a.type IN ({$typePlaceholders}) AND a.is_active = 1
             GROUP BY a.id, a.code, a.name, a.type, a.normal_balance
             ORDER BY a.code ASC",
            array_merge([$fromDate, $toDate], $types)
        );
    }

    private function findEntryById(int $id): ?JournalEntry
    {
        $row = $this->db->table('journal_entries')->where('id', $id)->first();
        return $row ? JournalEntry::fromArray($row) : null;
    }

    private function generateEntryNumber(int $branchId): string
    {
        $year  = date('Y');
        $count = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM journal_entries WHERE branch_id = ? AND YEAR(date) = ?",
            [$branchId, $year]
        );
        return "JE-{$year}-" . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
