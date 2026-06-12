<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * ExpenseRepository
 *
 * All SQL related to expense_categories and expenses tables.
 */
final class ExpenseRepository extends BaseRepository
{
    // =========================================================================
    // Expense Categories
    // =========================================================================

    /**
     * Return all expense categories, optionally filtered by a search term.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allCategories(string $search = ''): array
    {
        if ($search !== '') {
            return $this->fetchAll(
                'SELECT * FROM expense_categories WHERE name LIKE :search ORDER BY name ASC',
                [':search' => '%' . $search . '%'],
            );
        }

        return $this->fetchAll(
            'SELECT * FROM expense_categories ORDER BY name ASC',
        );
    }

    /**
     * Find a single expense category by its primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findCategory(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM expense_categories WHERE id = :id LIMIT 1',
            [':id' => $id],
        );
    }

    /**
     * Insert a new expense category and return the new ID.
     *
     * @param array<string, mixed> $data
     */
    public function createCategory(array $data): int
    {
        $this->modify(
            <<<SQL
            INSERT INTO expense_categories (name, description, color, status, created_at, updated_at)
            VALUES (:name, :description, :color, :status, NOW(), NOW())
            SQL,
            [
                ':name'        => (string) ($data['name'] ?? ''),
                ':description' => (string) ($data['description'] ?? ''),
                ':color'       => (string) ($data['color'] ?? '#6c757d'),
                ':status'      => (string) ($data['status'] ?? 'active'),
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing expense category.
     *
     * @param array<string, mixed> $data
     */
    public function updateCategory(int $id, array $data): void
    {
        $this->modify(
            <<<SQL
            UPDATE expense_categories
               SET name        = :name,
                   description = :description,
                   color       = :color,
                   status      = :status,
                   updated_at  = NOW()
             WHERE id = :id
            SQL,
            [
                ':name'        => (string) ($data['name'] ?? ''),
                ':description' => (string) ($data['description'] ?? ''),
                ':color'       => (string) ($data['color'] ?? '#6c757d'),
                ':status'      => (string) ($data['status'] ?? 'active'),
                ':id'          => $id,
            ],
        );
    }

    /**
     * Hard-delete a category only when no expenses reference it.
     *
     * @throws \RuntimeException When expenses still reference this category.
     */
    public function deleteCategory(int $id): void
    {
        $count = $this->count(
            'SELECT COUNT(*) AS total FROM expenses WHERE category_id = :id',
            [':id' => $id],
        );

        if ($count > 0) {
            throw new \RuntimeException(
                'Cannot delete this category because it has ' . $count . ' expense(s) referencing it.'
            );
        }

        $this->modify(
            'DELETE FROM expense_categories WHERE id = :id',
            [':id' => $id],
        );
    }

    // =========================================================================
    // Expenses
    // =========================================================================

    /**
     * Return a paginated list of expenses with optional filters and category join.
     *
     * Supported filter keys: category_id, status, date_from, date_to, search.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, lastPage: int}
     */
    public function paginate(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = parent::limitOffset($page, $perPage);

        $clauses = [];
        $params  = [];

        if (!empty($filters['category_id'])) {
            $clauses[]             = 'e.category_id = :category_id';
            $params[':category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[]       = 'e.status = :status';
            $params[':status'] = (string) $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $clauses[]          = 'e.date >= :date_from';
            $params[':date_from'] = (string) $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[]        = 'e.date <= :date_to';
            $params[':date_to'] = (string) $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $clauses[]         = '(e.description LIKE :search OR e.expense_number LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $where = $clauses !== [] ? 'WHERE ' . implode(' AND ', $clauses) : '';

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM expenses e {$where}",
            $params,
        );

        $items = $this->fetchAll(
            <<<SQL
            SELECT
                e.*,
                ec.name  AS category_name,
                ec.color AS category_color
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            {$where}
            ORDER BY e.date DESC, e.id DESC
            LIMIT {$limit} OFFSET {$offset}
            SQL,
            $params,
        );

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $lastPage,
        ];
    }

    /**
     * Find a single expense by ID (includes category name).
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            <<<SQL
            SELECT
                e.*,
                ec.name  AS category_name,
                ec.color AS category_color
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            WHERE e.id = :id
            LIMIT 1
            SQL,
            [':id' => $id],
        );
    }

    /**
     * Insert a new expense and return its ID.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->modify(
            <<<SQL
            INSERT INTO expenses
                (expense_number, category_id, branch_id, amount, date, description,
                 receipt_path, status, created_by, created_at, updated_at)
            VALUES
                (:expense_number, :category_id, :branch_id, :amount, :date, :description,
                 :receipt_path, :status, :created_by, NOW(), NOW())
            SQL,
            [
                ':expense_number' => (string) ($data['expense_number'] ?? $this->generateRef()),
                ':category_id'  => isset($data['category_id']) ? (int) $data['category_id'] : null,
                ':branch_id'    => isset($data['branch_id']) && $data['branch_id'] !== ''
                                        ? (int) $data['branch_id'] : null,
                ':amount'       => (string) ($data['amount'] ?? '0.00'),
                ':date'         => (string) ($data['date'] ?? date('Y-m-d')),
                ':description'  => (string) ($data['description'] ?? ''),
                ':receipt_path' => isset($data['receipt_path']) && $data['receipt_path'] !== ''
                                        ? (string) $data['receipt_path'] : null,
                ':status'       => (string) ($data['status'] ?? 'draft'),
                ':created_by'   => (int) ($data['created_by'] ?? 0),
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing expense.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $this->modify(
            <<<SQL
            UPDATE expenses
               SET category_id  = :category_id,
                   branch_id    = :branch_id,
                   amount       = :amount,
                   date         = :date,
                   description  = :description,
                   receipt_path = :receipt_path,
                   updated_at   = NOW()
             WHERE id = :id
            SQL,
            [
                ':category_id'  => isset($data['category_id']) ? (int) $data['category_id'] : null,
                ':branch_id'    => isset($data['branch_id']) && $data['branch_id'] !== ''
                                        ? (int) $data['branch_id'] : null,
                ':amount'       => (string) ($data['amount'] ?? '0.00'),
                ':date'         => (string) ($data['date'] ?? date('Y-m-d')),
                ':description'  => (string) ($data['description'] ?? ''),
                ':receipt_path' => isset($data['receipt_path']) && $data['receipt_path'] !== ''
                                        ? (string) $data['receipt_path'] : null,
                ':id'           => $id,
            ],
        );
    }

    /**
     * Soft-delete an expense.
     */
    public function softDelete(int $id): void
    {
        $this->modify(
            'DELETE FROM expenses WHERE id = :id',
            [':id' => $id],
        );
    }

    /**
     * Approve an expense: set status = approved, record approver and timestamp.
     */
    public function approve(int $id, int $approvedBy): void
    {
        $this->modify(
            <<<SQL
            UPDATE expenses
               SET status      = 'approved',
                   approved_by = :approved_by,
                   updated_at  = NOW()
             WHERE id = :id
            SQL,
            [
                ':approved_by' => $approvedBy,
                ':id'          => $id,
            ],
        );
    }

    /**
     * Reject an expense: set status = rejected.
     */
    public function reject(int $id): void
    {
        $this->modify(
            <<<SQL
            UPDATE expenses
               SET status     = 'rejected',
                   updated_at = NOW()
             WHERE id = :id
            SQL,
            [':id' => $id],
        );
    }

    /**
     * Generate a unique reference number in the format EXP-YYYYNNNN.
     *
     * The sequence resets per year based on existing records for that year.
     */
    public function generateRef(): string
    {
        $year = date('Y');

        $row = $this->fetchOne(
            <<<SQL
            SELECT COUNT(*) AS total
              FROM expenses
             WHERE expense_number LIKE :prefix
            SQL,
            [':prefix' => 'EXP-' . $year . '%'],
        );

        $seq = (int) ($row['total'] ?? 0) + 1;

        return 'EXP-' . $year . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
