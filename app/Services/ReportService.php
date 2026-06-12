<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Cache;

class ReportService
{
    public function __construct(
        private readonly Database $db,
        private readonly Cache $cache
    ) {}

    public function getDashboardMetrics(int $branchId): array
    {
        return $this->cache->remember("dashboard_metrics_{$branchId}", 300, function () use ($branchId) {
            $currentMonth = (int) date('n');
            $currentYear  = (int) date('Y');
            $lastMonth    = $currentMonth === 1 ? 12 : $currentMonth - 1;
            $lastYear     = $currentMonth === 1 ? $currentYear - 1 : $currentYear;

            $currentRevenue = (float)$this->db->fetchColumn(
                "SELECT COALESCE(SUM(total_amount), 0) FROM invoices
                 WHERE branch_id = ? AND status != 'cancelled'
                 AND MONTH(invoice_date) = ? AND YEAR(invoice_date) = ?",
                [$branchId, $currentMonth, $currentYear]
            );

            $lastRevenue = (float)$this->db->fetchColumn(
                "SELECT COALESCE(SUM(total_amount), 0) FROM invoices
                 WHERE branch_id = ? AND status != 'cancelled'
                 AND MONTH(invoice_date) = ? AND YEAR(invoice_date) = ?",
                [$branchId, $lastMonth, $lastYear]
            );

            $revenueGrowth = $lastRevenue > 0
                ? round((($currentRevenue - $lastRevenue) / $lastRevenue) * 100, 1)
                : 0;

            $totalOrders = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM sales_orders WHERE branch_id = ? AND status NOT IN ('cancelled','draft')
                 AND MONTH(order_date) = ? AND YEAR(order_date) = ?",
                [$branchId, $currentMonth, $currentYear]
            );

            $pendingOrders = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM sales_orders WHERE branch_id = ? AND status IN ('confirmed','processing')",
                [$branchId]
            );

            $activeEmployees = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM employees WHERE branch_id = ? AND status = 'active' AND deleted_at IS NULL",
                [$branchId]
            );

            $overdueInvoices = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM invoices WHERE branch_id = ? AND status IN ('sent','partial') AND due_date < CURDATE()",
                [$branchId]
            );

            $outstandingReceivables = (float)$this->db->fetchColumn(
                "SELECT COALESCE(SUM(balance), 0) FROM invoices WHERE branch_id = ? AND status NOT IN ('paid','cancelled')",
                [$branchId]
            );

            $topProducts = $this->db->fetchAll(
                "SELECT p.name, p.sku, SUM(ii.quantity) AS qty_sold, SUM(ii.total) AS revenue
                 FROM invoice_items ii
                 JOIN products p ON p.id = ii.product_id
                 JOIN invoices i ON i.id = ii.invoice_id
                 WHERE i.branch_id = ? AND i.status != 'cancelled'
                 AND MONTH(i.invoice_date) = ? AND YEAR(i.invoice_date) = ?
                 GROUP BY p.id, p.name, p.sku
                 ORDER BY revenue DESC LIMIT 5",
                [$branchId, $currentMonth, $currentYear]
            );

            $revenueChart = $this->getMonthlyRevenue($branchId, 12);

            $lowStock = $this->db->fetchAll(
                "SELECT p.name, p.sku, p.reorder_point,
                        COALESCE(SUM(inv.quantity), 0) AS current_stock
                 FROM products p
                 LEFT JOIN inventory inv ON inv.product_id = p.id
                 LEFT JOIN warehouses w ON w.id = inv.warehouse_id AND w.branch_id = ?
                 WHERE p.is_active = 1 AND p.deleted_at IS NULL
                 GROUP BY p.id, p.name, p.sku, p.reorder_point
                 HAVING current_stock <= p.reorder_point
                 ORDER BY current_stock ASC LIMIT 10",
                [$branchId]
            );

            return [
                'current_revenue'         => $currentRevenue,
                'last_revenue'            => $lastRevenue,
                'revenue_growth'          => $revenueGrowth,
                'total_orders'            => $totalOrders,
                'pending_orders'          => $pendingOrders,
                'active_employees'        => $activeEmployees,
                'overdue_invoices'        => $overdueInvoices,
                'outstanding_receivables' => $outstandingReceivables,
                'top_products'            => $topProducts,
                'revenue_chart'           => $revenueChart,
                'low_stock_alerts'        => $lowStock,
            ];
        });
    }

    public function getMonthlyRevenue(int $branchId, int $months = 12): array
    {
        $results = $this->db->fetchAll(
            "SELECT YEAR(invoice_date) AS year, MONTH(invoice_date) AS month,
                    SUM(total_amount) AS revenue, COUNT(*) AS invoice_count
             FROM invoices
             WHERE branch_id = ? AND status != 'cancelled'
             AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY YEAR(invoice_date), MONTH(invoice_date)
             ORDER BY year ASC, month ASC",
            [$branchId, $months]
        );

        return array_map(fn($r) => [
            'label'         => date('M Y', (int) mktime(0, 0, 0, (int)$r['month'], 1, (int)$r['year'])),
            'revenue'       => (float)$r['revenue'],
            'invoice_count' => (int)$r['invoice_count'],
        ], $results);
    }

    public function getSalesReport(int $branchId, string $from, string $to): array
    {
        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS invoice_count, SUM(total_amount) AS gross_revenue,
                    SUM(vat_amount) AS total_vat, SUM(discount_amount) AS total_discount,
                    SUM(paid_amount) AS total_collected, SUM(balance) AS outstanding
             FROM invoices WHERE branch_id = ? AND invoice_date BETWEEN ? AND ? AND status != 'cancelled'",
            [$branchId, $from, $to]
        );

        $byProduct = $this->db->fetchAll(
            "SELECT p.name, p.sku, SUM(ii.quantity) AS qty, SUM(ii.total) AS revenue
             FROM invoice_items ii
             JOIN products p ON p.id = ii.product_id
             JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.branch_id = ? AND i.invoice_date BETWEEN ? AND ? AND i.status != 'cancelled'
             GROUP BY p.id, p.name, p.sku ORDER BY revenue DESC LIMIT 20",
            [$branchId, $from, $to]
        );

        $byCustomer = $this->db->fetchAll(
            "SELECT c.name, c.customer_code, COUNT(i.id) AS invoice_count, SUM(i.total_amount) AS revenue
             FROM invoices i JOIN customers c ON c.id = i.customer_id
             WHERE i.branch_id = ? AND i.invoice_date BETWEEN ? AND ? AND i.status != 'cancelled'
             GROUP BY c.id, c.name, c.customer_code ORDER BY revenue DESC LIMIT 20",
            [$branchId, $from, $to]
        );

        return compact('summary', 'byProduct', 'byCustomer');
    }

    public function getExpenseReport(int $branchId, string $from, string $to): array
    {
        $total = $this->db->fetchOne(
            "SELECT SUM(total_amount) AS total, COUNT(*) AS count
             FROM expenses WHERE branch_id = ? AND date BETWEEN ? AND ? AND status IN ('approved','paid')",
            [$branchId, $from, $to]
        );

        $byCategory = $this->db->fetchAll(
            "SELECT ec.name AS category, SUM(e.total_amount) AS amount, COUNT(*) AS count
             FROM expenses e JOIN expense_categories ec ON ec.id = e.category_id
             WHERE e.branch_id = ? AND e.date BETWEEN ? AND ? AND e.status IN ('approved','paid')
             GROUP BY ec.id, ec.name ORDER BY amount DESC",
            [$branchId, $from, $to]
        );

        return ['total' => $total, 'by_category' => $byCategory];
    }

    public function getPayrollReport(int $branchId, int $year): array
    {
        return $this->db->fetchAll(
            "SELECT month, year,
                    SUM(gross_salary) AS total_gross, SUM(net_salary) AS total_net,
                    SUM(tax_amount) AS total_tax, COUNT(*) AS employee_count
             FROM payroll
             WHERE branch_id = ? AND year = ? AND status != 'cancelled'
             GROUP BY month, year ORDER BY month ASC",
            [$branchId, $year]
        );
    }

    public function getInventoryReport(int $branchId): array
    {
        $value = $this->db->fetchOne(
            "SELECT COALESCE(SUM(i.quantity * i.avg_cost), 0) AS total_value,
                    COUNT(DISTINCT i.product_id) AS product_count
             FROM inventory i WHERE i.branch_id = ?",
            [$branchId]
        );

        $byCategory = $this->db->fetchAll(
            "SELECT c.name AS category, COUNT(p.id) AS products,
                    SUM(i.quantity * i.avg_cost) AS value
             FROM inventory i
             JOIN products p ON p.id = i.product_id
             JOIN categories c ON c.id = p.category_id
             WHERE i.branch_id = ?
             GROUP BY c.id, c.name ORDER BY value DESC",
            [$branchId]
        );

        return ['summary' => $value, 'by_category' => $byCategory];
    }
}
