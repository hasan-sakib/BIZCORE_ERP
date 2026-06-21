<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getDashboardMetrics(int $branchId): array
    {
        return Cache::remember("dashboard_metrics_{$branchId}", 300, function () use ($branchId) {
            $month    = (int) date('n');
            $year     = (int) date('Y');
            $lastMonth = $month === 1 ? 12 : $month - 1;
            $lastYear  = $month === 1 ? $year - 1 : $year;

            $currentRevenue = (float) DB::table('invoices')
                ->where('branch_id', $branchId)->where('status', '!=', 'cancelled')
                ->whereMonth('invoice_date', $month)->whereYear('invoice_date', $year)
                ->sum('total_amount');

            $lastRevenue = (float) DB::table('invoices')
                ->where('branch_id', $branchId)->where('status', '!=', 'cancelled')
                ->whereMonth('invoice_date', $lastMonth)->whereYear('invoice_date', $lastYear)
                ->sum('total_amount');

            $revenueGrowth = $lastRevenue > 0
                ? round((($currentRevenue - $lastRevenue) / $lastRevenue) * 100, 1)
                : 0;

            $totalOrders = DB::table('sales_orders')
                ->where('branch_id', $branchId)->whereNotIn('status', ['cancelled', 'draft'])
                ->whereMonth('order_date', $month)->whereYear('order_date', $year)
                ->count();

            $pendingOrders = DB::table('sales_orders')
                ->where('branch_id', $branchId)->whereIn('status', ['confirmed', 'processing'])
                ->count();

            $activeEmployees = DB::table('employees')
                ->where('branch_id', $branchId)->where('status', 'active')->whereNull('deleted_at')
                ->count();

            $overdueInvoices = DB::table('invoices')
                ->where('branch_id', $branchId)->whereIn('status', ['sent', 'partial'])
                ->where('due_date', '<', now()->toDateString())
                ->count();

            $outstandingReceivables = (float) DB::table('invoices')
                ->where('branch_id', $branchId)->whereNotIn('status', ['paid', 'cancelled'])
                ->sum('balance');

            $topProducts = DB::select(
                "SELECT p.name, p.sku, SUM(ii.quantity) AS qty_sold, SUM(ii.total) AS revenue
                 FROM invoice_items ii
                 JOIN products p ON p.id = ii.product_id
                 JOIN invoices i ON i.id = ii.invoice_id
                 WHERE i.branch_id = ? AND i.status != 'cancelled'
                 AND MONTH(i.invoice_date) = ? AND YEAR(i.invoice_date) = ?
                 GROUP BY p.id, p.name, p.sku
                 ORDER BY revenue DESC LIMIT 5",
                [$branchId, $month, $year]
            );

            $revenueChart = $this->getMonthlyRevenue($branchId, 12);

            $lowStock = DB::select(
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
        $results = DB::select(
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
            'label'         => date('M Y', (int) mktime(0, 0, 0, (int) $r->month, 1, (int) $r->year)),
            'revenue'       => (float) $r->revenue,
            'invoice_count' => (int) $r->invoice_count,
        ], $results);
    }

    public function getSalesReport(int $branchId, string $from, string $to): array
    {
        $summary = DB::table('invoices')
            ->where('branch_id', $branchId)
            ->whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COUNT(*) AS invoice_count, SUM(total_amount) AS gross_revenue,
                         SUM(vat_amount) AS total_vat, SUM(discount_amount) AS total_discount,
                         SUM(paid_amount) AS total_collected, SUM(balance) AS outstanding')
            ->first();

        $byProduct = DB::select(
            "SELECT p.name, p.sku, SUM(ii.quantity) AS qty, SUM(ii.total) AS revenue
             FROM invoice_items ii
             JOIN products p ON p.id = ii.product_id
             JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.branch_id = ? AND i.invoice_date BETWEEN ? AND ? AND i.status != 'cancelled'
             GROUP BY p.id, p.name, p.sku ORDER BY revenue DESC LIMIT 20",
            [$branchId, $from, $to]
        );

        $byCustomer = DB::select(
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
        $total = DB::table('expenses')
            ->where('branch_id', $branchId)
            ->whereBetween('date', [$from, $to])
            ->whereIn('status', ['approved', 'paid'])
            ->selectRaw('SUM(total_amount) AS total, COUNT(*) AS count')
            ->first();

        $byCategory = DB::select(
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
        return DB::table('payroll')
            ->where('branch_id', $branchId)
            ->where('year', $year)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('month, year, SUM(gross_salary) AS total_gross, SUM(net_salary) AS total_net,
                         SUM(tax_amount) AS total_tax, COUNT(*) AS employee_count')
            ->groupBy('month', 'year')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getInventoryReport(int $branchId): array
    {
        $value = DB::table('inventory')->where('branch_id', $branchId)
            ->selectRaw('COALESCE(SUM(quantity * avg_cost), 0) AS total_value, COUNT(DISTINCT product_id) AS product_count')
            ->first();

        $byCategory = DB::select(
            "SELECT c.name AS category, COUNT(p.id) AS products, SUM(i.quantity * i.avg_cost) AS value
             FROM inventory i
             JOIN products p ON p.id = i.product_id
             JOIN categories c ON c.id = p.category_id
             WHERE i.branch_id = ?
             GROUP BY c.id, c.name ORDER BY value DESC",
            [$branchId]
        );

        return ['summary' => $value, 'by_category' => $byCategory];
    }

    public function invalidateDashboard(int $branchId): void
    {
        Cache::forget("dashboard_metrics_{$branchId}");
    }
}
