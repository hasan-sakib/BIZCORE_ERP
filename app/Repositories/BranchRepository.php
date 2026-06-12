<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Branch;

/**
 * BranchRepository
 *
 * All SQL queries related to the `branches` table and its aggregated data
 * (employee counts, revenue totals, performance metrics) live here.
 * Business logic MUST NOT appear in this class.
 */
final class BranchRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Reads
    // -------------------------------------------------------------------------

    /**
     * Find a branch by primary key.
     */
    public function findById(int $id): ?Branch
    {
        $row = $this->fetchOne(
            'SELECT * FROM branches WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id],
        );

        return $row !== null ? Branch::fromArray($row) : null;
    }

    /**
     * Find a branch by its unique short code (e.g. 'HQ', 'CTG').
     */
    public function findByCode(string $code): ?Branch
    {
        $row = $this->fetchOne(
            'SELECT * FROM branches WHERE UPPER(code) = :code AND deleted_at IS NULL LIMIT 1',
            [':code' => strtoupper(trim($code))],
        );

        return $row !== null ? Branch::fromArray($row) : null;
    }

    /**
     * Return all active branches ordered by name.
     *
     * @return Branch[]
     */
    public function findActive(): array
    {
        $rows = $this->fetchAll(
            "SELECT * FROM branches WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC",
        );

        return array_map(static fn (array $row) => Branch::fromArray($row), $rows);
    }

    /**
     * Return all branches (including inactive) ordered by head-office first, then name.
     *
     * @return Branch[]
     */
    public function findAll(): array
    {
        $rows = $this->fetchAll(
            'SELECT * FROM branches WHERE deleted_at IS NULL ORDER BY is_head DESC, name ASC',
        );

        return array_map(static fn (array $row) => Branch::fromArray($row), $rows);
    }

    /**
     * Return a single branch merged with aggregate stats:
     *   - employee_count  : active employees assigned to this branch
     *   - revenue_total   : sum of paid invoice totals (all time)
     *   - pending_orders  : sales orders with status = 'pending'
     *
     * @return array<string, mixed>  Branch fields + stat keys, or empty array when not found.
     */
    public function findWithStats(int $id): array
    {
        $branch = $this->findById($id);

        if ($branch === null) {
            return [];
        }

        $stats = $this->fetchOne(
            <<<SQL
            SELECT
                (
                    SELECT COUNT(*)
                    FROM employees
                    WHERE branch_id = :branch_id
                      AND status    = 'active'
                      AND deleted_at IS NULL
                ) AS employee_count,
                (
                    SELECT COALESCE(SUM(i.total_amount), 0)
                    FROM invoices i
                    WHERE i.branch_id  = :branch_id
                      AND i.status     = 'paid'
                      AND i.deleted_at IS NULL
                ) AS revenue_total,
                (
                    SELECT COUNT(*)
                    FROM sales_orders so
                    WHERE so.branch_id = :branch_id
                      AND so.status    = 'pending'
                      AND so.deleted_at IS NULL
                ) AS pending_orders
            SQL,
            [':branch_id' => $id],
        );

        return array_merge($branch->toArray(), [
            'employee_count' => (int) ($stats['employee_count'] ?? 0),
            'revenue_total'  => (float) ($stats['revenue_total'] ?? 0.0),
            'pending_orders' => (int) ($stats['pending_orders'] ?? 0),
        ]);
    }

    /**
     * Return performance metrics for a branch over a given period.
     *
     * $period values: 'today' | 'week' | 'month' | 'quarter' | 'year'
     *
     * @return array{
     *   period: string,
     *   revenue: float,
     *   orders_count: int,
     *   invoices_count: int,
     *   average_order_value: float,
     *   top_products: list<array{product_name: string, quantity: int, revenue: float}>,
     *   daily_revenue: list<array{date: string, revenue: float}>
     * }
     */
    public function getPerformanceMetrics(int $branchId, string $period): array
    {
        [$fromDate, $toDate] = $this->resolvePeriodDates($period);

        // Aggregated totals.
        $totals = $this->fetchOne(
            <<<SQL
            SELECT
                COALESCE(SUM(i.total_amount), 0) AS revenue,
                COUNT(DISTINCT so.id)            AS orders_count,
                COUNT(DISTINCT i.id)             AS invoices_count
            FROM sales_orders so
            LEFT JOIN invoices i ON i.sales_order_id = so.id
                                  AND i.status        = 'paid'
                                  AND i.deleted_at    IS NULL
            WHERE so.branch_id  = :branch_id
              AND so.deleted_at IS NULL
              AND so.created_at BETWEEN :from AND :to
            SQL,
            [
                ':branch_id' => $branchId,
                ':from'      => $fromDate,
                ':to'        => $toDate,
            ],
        );

        $revenue     = (float) ($totals['revenue']       ?? 0.0);
        $ordersCount = (int)   ($totals['orders_count']  ?? 0);

        // Top 5 products by revenue in the period.
        $topProducts = $this->fetchAll(
            <<<SQL
            SELECT
                p.name                        AS product_name,
                SUM(oi.quantity)              AS quantity,
                SUM(oi.quantity * oi.unit_price) AS revenue
            FROM order_items oi
            INNER JOIN products p     ON p.id  = oi.product_id
            INNER JOIN sales_orders so ON so.id = oi.sales_order_id
            WHERE so.branch_id  = :branch_id
              AND so.deleted_at IS NULL
              AND so.created_at BETWEEN :from AND :to
            GROUP BY p.id, p.name
            ORDER BY revenue DESC
            LIMIT 5
            SQL,
            [
                ':branch_id' => $branchId,
                ':from'      => $fromDate,
                ':to'        => $toDate,
            ],
        );

        // Daily revenue within the period.
        $dailyRevenue = $this->fetchAll(
            <<<SQL
            SELECT
                DATE(i.created_at)          AS date,
                COALESCE(SUM(i.total_amount), 0) AS revenue
            FROM invoices i
            INNER JOIN sales_orders so ON so.id = i.sales_order_id
            WHERE so.branch_id = :branch_id
              AND i.status     = 'paid'
              AND i.deleted_at IS NULL
              AND i.created_at BETWEEN :from AND :to
            GROUP BY DATE(i.created_at)
            ORDER BY date ASC
            SQL,
            [
                ':branch_id' => $branchId,
                ':from'      => $fromDate,
                ':to'        => $toDate,
            ],
        );

        return [
            'period'              => $period,
            'from_date'           => $fromDate,
            'to_date'             => $toDate,
            'revenue'             => $revenue,
            'orders_count'        => $ordersCount,
            'invoices_count'      => (int) ($totals['invoices_count'] ?? 0),
            'average_order_value' => $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0.0,
            'top_products'        => array_map(
                static fn (array $row) => [
                    'product_name' => $row['product_name'],
                    'quantity'     => (int) $row['quantity'],
                    'revenue'      => (float) $row['revenue'],
                ],
                $topProducts,
            ),
            'daily_revenue'       => array_map(
                static fn (array $row) => [
                    'date'    => $row['date'],
                    'revenue' => (float) $row['revenue'],
                ],
                $dailyRevenue,
            ),
        ];
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Insert a new branch row and return the generated ID.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            <<<SQL
            INSERT INTO branches
                (name, code, address, phone, email, manager_id, status, settings, is_head, created_at, updated_at)
            VALUES
                (:name, :code, :address, :phone, :email, :manager_id, :status, :settings, :is_head, NOW(), NOW())
            SQL,
            [
                ':name'       => $data['name'],
                ':code'       => strtoupper(trim($data['code'])),
                ':address'    => json_encode($data['address'] ?? [], JSON_UNESCAPED_UNICODE),
                ':phone'      => $data['phone']      ?? null,
                ':email'      => $data['email']      ?? null,
                ':manager_id' => $data['manager_id'] ?? null,
                ':status'     => $data['status']     ?? 'active',
                ':settings'   => json_encode($data['settings'] ?? [], JSON_UNESCAPED_UNICODE),
                ':is_head'    => (int) ($data['is_head'] ?? 0),
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing branch row.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params     = [':id' => $id];

        $scalar = ['name', 'phone', 'email', 'manager_id', 'status', 'is_head'];

        foreach ($scalar as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[]         = "{$field} = :{$field}";
                $params[":{$field}"]  = $data[$field];
            }
        }

        if (array_key_exists('code', $data)) {
            $setClauses[]   = 'code = :code';
            $params[':code'] = strtoupper(trim((string) $data['code']));
        }

        if (array_key_exists('address', $data)) {
            $setClauses[]      = 'address = :address';
            $params[':address'] = json_encode($data['address'], JSON_UNESCAPED_UNICODE);
        }

        if (array_key_exists('settings', $data)) {
            $setClauses[]       = 'settings = :settings';
            $params[':settings'] = json_encode($data['settings'], JSON_UNESCAPED_UNICODE);
        }

        if ($setClauses === []) {
            return false;
        }

        $setClauses[] = 'updated_at = NOW()';
        $setSQL       = implode(', ', $setClauses);

        return $this->modify(
            "UPDATE branches SET {$setSQL} WHERE id = :id AND deleted_at IS NULL",
            $params,
        ) > 0;
    }

    /**
     * Soft-delete a branch by setting deleted_at.
     */
    public function delete(int $id): bool
    {
        return $this->modify(
            "UPDATE branches SET deleted_at = NOW(), status = 'inactive', updated_at = NOW() WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id],
        ) > 0;
    }

    /**
     * Count active transactions (sales orders + purchase orders) for a branch.
     * Used by BranchService::disable() to block disabling a branch that is in use.
     */
    public function countActiveTransactions(int $id): int
    {
        $row = $this->fetchOne(
            <<<SQL
            SELECT
            (
                SELECT COUNT(*)
                FROM sales_orders
                WHERE branch_id  = :bid1
                  AND status NOT IN ('completed', 'cancelled')
                  AND deleted_at IS NULL
            ) +
            (
                SELECT COUNT(*)
                FROM purchase_orders
                WHERE branch_id  = :bid2
                  AND status NOT IN ('received', 'cancelled')
                  AND deleted_at IS NULL
            ) AS total
            SQL,
            [':bid1' => $id, ':bid2' => $id],
        );

        return (int) ($row['total'] ?? 0);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a named period to a [fromDate, toDate] tuple (MySQL DATETIME strings).
     *
     * @return array{0: string, 1: string}
     */
    private function resolvePeriodDates(string $period): array
    {
        $now = new \DateTime();
        $to  = $now->format('Y-m-d 23:59:59');

        $from = match ($period) {
            'today'   => $now->format('Y-m-d 00:00:00'),
            'week'    => (clone $now)->modify('monday this week')->format('Y-m-d 00:00:00'),
            'quarter' => (function () use ($now): string {
                $month   = (int) $now->format('n');
                $qStart  = ((int) ceil($month / 3) - 1) * 3 + 1;
                $date    = clone $now;
                $date->setDate((int) $now->format('Y'), $qStart, 1);
                return $date->format('Y-m-d 00:00:00');
            })(),
            'year'    => $now->format('Y-01-01 00:00:00'),
            default   => $now->format('Y-m-01 00:00:00'), // 'month'
        };

        return [$from, $to];
    }
}
