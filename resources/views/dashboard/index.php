<?php
$layout    = 'app';
$pageTitle = 'Dashboard';
ob_start();

$revenueGrowth = $metrics['revenue_growth'] ?? 0;
$growthClass   = $revenueGrowth >= 0 ? 'text-success' : 'text-danger';
$growthIcon    = $revenueGrowth >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
?>

<!-- KPI Cards -->
<div class="row g-4 mb-4">
    <!-- Revenue -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-primary-soft">
                <i class="fas fa-chart-line text-primary"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">This Month Revenue</div>
                <div class="kpi-value">৳<?= number_format($metrics['current_revenue'] ?? 0, 2) ?></div>
                <div class="kpi-trend <?= $growthClass ?>">
                    <i class="fas <?= $growthIcon ?> small"></i>
                    <?= abs($revenueGrowth) ?>% vs last month
                </div>
            </div>
        </div>
    </div>

    <!-- Orders -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-success-soft">
                <i class="fas fa-shopping-bag text-success"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-value"><?= number_format($metrics['total_orders'] ?? 0) ?></div>
                <div class="kpi-trend text-warning">
                    <i class="fas fa-clock small"></i>
                    <?= $metrics['pending_orders'] ?? 0 ?> pending
                </div>
            </div>
        </div>
    </div>

    <!-- Employees -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-icon bg-info-soft">
                <i class="fas fa-users text-info"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Active Employees</div>
                <div class="kpi-value"><?= number_format($metrics['active_employees'] ?? 0) ?></div>
                <div class="kpi-trend text-muted">
                    <i class="fas fa-building small"></i>
                    Across all branches
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Invoices -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card <?= ($metrics['overdue_invoices'] ?? 0) > 0 ? 'border-danger-subtle' : '' ?>">
            <div class="kpi-icon bg-danger-soft">
                <i class="fas fa-exclamation-triangle text-danger"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Overdue Invoices</div>
                <div class="kpi-value text-danger"><?= number_format($metrics['overdue_invoices'] ?? 0) ?></div>
                <div class="kpi-trend text-danger">
                    ৳<?= number_format($metrics['outstanding_receivables'] ?? 0, 2) ?> outstanding
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Revenue Chart -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0"><i class="fas fa-chart-area me-2 text-primary"></i>Revenue Trend (12 Months)</h6>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="280"></canvas>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0"><i class="fas fa-exclamation-circle me-2 text-warning"></i>Low Stock Alerts</h6>
                <a href="/inventory?low_stock=1" class="btn btn-sm btn-outline-warning py-0">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($metrics['low_stock_alerts'])): ?>
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                        All stock levels are healthy
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($metrics['low_stock_alerts'], 0, 8) as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <div class="fw-semibold small"><?= sanitize($item['name']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem"><?= sanitize($item['sku']) ?></div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger"><?= (float)$item['current_stock'] ?></span>
                                    <div class="text-muted" style="font-size:.7rem">min: <?= (float)$item['reorder_point'] ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="row g-4">
    <!-- Top Products -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0"><i class="fas fa-star me-2 text-warning"></i>Top Products This Month</h6>
                <a href="/reports/sales" class="btn btn-sm btn-outline-secondary py-0">Full Report</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($metrics['top_products'])): ?>
                    <div class="text-center text-muted p-4">No sales data for this month</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($metrics['top_products'] as $i => $product): ?>
                                    <tr>
                                        <td><span class="badge bg-primary-soft text-primary"><?= $i + 1 ?></span></td>
                                        <td>
                                            <div class="fw-semibold small"><?= sanitize($product['name']) ?></div>
                                            <div class="text-muted" style="font-size:.7rem"><?= sanitize($product['sku']) ?></div>
                                        </td>
                                        <td class="text-end"><?= number_format((float)$product['qty_sold'], 2) ?></td>
                                        <td class="text-end fw-semibold">৳<?= number_format((float)$product['revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-bolt me-2 text-info"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="/invoices/create" class="quick-action-btn">
                            <i class="fas fa-file-invoice text-primary"></i>
                            <span>New Invoice</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/sales-orders/create" class="quick-action-btn">
                            <i class="fas fa-shopping-cart text-success"></i>
                            <span>New Order</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/inventory/stock-in" class="quick-action-btn">
                            <i class="fas fa-arrow-circle-down text-info"></i>
                            <span>Stock In</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/employees/create" class="quick-action-btn">
                            <i class="fas fa-user-plus text-warning"></i>
                            <span>Add Employee</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/payments" class="quick-action-btn">
                            <i class="fas fa-money-bill-wave text-success"></i>
                            <span>Payments</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/reports/trial-balance" class="quick-action-btn">
                            <i class="fas fa-balance-scale text-secondary"></i>
                            <span>Trial Balance</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chartData = <?= json_encode($metrics['revenue_chart'] ?? []) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels:   chartData.map(d => d.label),
            datasets: [{
                label:           'Revenue (৳)',
                data:            chartData.map(d => d.revenue),
                borderColor:     '#2563eb',
                backgroundColor: 'rgba(37,99,235,.1)',
                fill:            true,
                tension:         .4,
                pointRadius:     4,
                pointHoverRadius:6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => '৳' + ctx.parsed.y.toLocaleString('en-BD', {minimumFractionDigits: 2})
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '৳' + v.toLocaleString() }
                }
            }
        }
    });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
