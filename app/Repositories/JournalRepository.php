<?php

declare(strict_types=1);

namespace App\Repositories;

final class JournalRepository extends BaseRepository
{
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]      = '(j.entry_number LIKE :s OR j.description LIKE :s)';
            $params[':s'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[]            = 'j.status = :status';
            $params[':status']  = $filters['status'];
        }

        $w      = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $totalRow = $this->fetchOne(
            "SELECT COUNT(*) as c FROM journal_entries j WHERE {$w}",
            $params,
        );
        $total = $totalRow ? (int) $totalRow['c'] : 0;

        $rows = $this->fetchAll(
            "SELECT j.*, u.name AS created_by_name
             FROM journal_entries j
             LEFT JOIN users u ON u.id = j.created_by
             WHERE {$w}
             ORDER BY j.date DESC, j.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params,
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public function findById(int $id): ?array
    {
        $entry = $this->fetchOne(
            'SELECT j.*, u.name AS created_by_name FROM journal_entries j
             LEFT JOIN users u ON u.id = j.created_by
             WHERE j.id = :id LIMIT 1',
            [':id' => $id],
        );
        if ($entry === null) {
            return null;
        }
        $entry['lines'] = $this->fetchAll(
            'SELECT l.*, a.code AS account_code, a.name AS account_name
             FROM journal_entry_lines l
             JOIN accounts a ON a.id = l.account_id
             WHERE l.journal_entry_id = :id
             ORDER BY l.id ASC',
            [':id' => $id],
        );
        return $entry;
    }

    public function nextEntryNumber(): string
    {
        $row = $this->fetchOne(
            "SELECT entry_number FROM journal_entries ORDER BY id DESC LIMIT 1",
        );
        if ($row === null) {
            return 'JE-0001';
        }
        $num = (int) substr($row['entry_number'], 3);
        return 'JE-' . str_pad((string) ($num + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $lines): int
    {
        $this->pdo->beginTransaction();
        try {
            $this->modify(
                'INSERT INTO journal_entries (branch_id, entry_number, date, description, total_debit, total_credit, status, created_by)
                 VALUES (:branch_id, :entry_number, :date, :description, :total_debit, :total_credit, "draft", :created_by)',
                [
                    ':branch_id'    => $data['branch_id'] ?? 1,
                    ':entry_number' => $this->nextEntryNumber(),
                    ':date'         => $data['date'],
                    ':description'  => $data['description'] ?? null,
                    ':total_debit'  => array_sum(array_column($lines, 'debit')),
                    ':total_credit' => array_sum(array_column($lines, 'credit')),
                    ':created_by'   => $data['created_by'],
                ],
            );
            $journalId = (int) $this->pdo->lastInsertId();
            foreach ($lines as $line) {
                $this->modify(
                    'INSERT INTO journal_entry_lines (journal_entry_id, account_id, description, debit, credit)
                     VALUES (:jid, :aid, :desc, :debit, :credit)',
                    [
                        ':jid'   => $journalId,
                        ':aid'   => $line['account_id'],
                        ':desc'  => $line['description'] ?? null,
                        ':debit' => $line['debit'] ?? 0,
                        ':credit'=> $line['credit'] ?? 0,
                    ],
                );
            }
            $this->pdo->commit();
            return $journalId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function post(int $id, int $postedBy): void
    {
        $this->modify(
            "UPDATE journal_entries SET status='posted', posted_by=:pb, posted_at=NOW() WHERE id=:id AND status='draft'",
            [':pb' => $postedBy, ':id' => $id],
        );
    }

    public function void(int $id, int $reversedBy): void
    {
        $this->modify(
            "UPDATE journal_entries SET status='reversed', reversed_by=:rb, reversed_at=NOW() WHERE id=:id",
            [':rb' => $reversedBy, ':id' => $id],
        );
    }
}
