@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#2563eb,#1d4ed8);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 small opacity-75">Today's Revenue</p>
                    <h3 class="mb-0 fw-bold" id="widget-revenue">—</h3>
                </div>
                <i class="fa-solid fa-sack-dollar fa-xl opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#0891b2,#0e7490);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 small opacity-75">Pending Invoices</p>
                    <h3 class="mb-0 fw-bold" id="widget-invoices">—</h3>
                </div>
                <i class="fa-solid fa-file-invoice fa-xl opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#7c3aed,#6d28d9);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 small opacity-75">Low Stock Items</p>
                    <h3 class="mb-0 fw-bold" id="widget-stock">—</h3>
                </div>
                <i class="fa-solid fa-boxes-stacked fa-xl opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#059669,#047857);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 small opacity-75">Active Employees</p>
                    <h3 class="mb-0 fw-bold" id="widget-employees">—</h3>
                </div>
                <i class="fa-solid fa-users fa-xl opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Monthly Revenue</span>
                <span class="text-muted small" id="chart-period"></span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('sales.orders.create') }}" class="btn btn-outline-primary btn-sm text-start">
                        <i class="fa-solid fa-plus me-2"></i>New Sales Order
                    </a>
                    <a href="{{ route('sales.invoices.create') }}" class="btn btn-outline-primary btn-sm text-start">
                        <i class="fa-solid fa-file-invoice me-2"></i>Create Invoice
                    </a>
                    <a href="{{ route('purchasing.purchase-orders.create') }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="fa-solid fa-clipboard-list me-2"></i>Purchase Order
                    </a>
                    <a href="{{ route('hr.attendance.create') }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="fa-solid fa-clock me-2"></i>Mark Attendance
                    </a>
                    <a href="{{ route('expenses.create') }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="fa-solid fa-receipt me-2"></i>Log Expense
                    </a>
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-dark btn-sm text-start">
                        <i class="fa-solid fa-chart-bar me-2"></i>View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const headers = { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' };

    // Load stat widgets
    fetch('/dashboard/widgets/sales', { headers })
        .then(r => r.json()).then(d => {
            document.getElementById('widget-revenue').textContent = '৳ ' + (d.data?.today_revenue ?? 0).toLocaleString();
            document.getElementById('widget-invoices').textContent = d.data?.pending_invoices ?? 0;
        }).catch(() => {});

    fetch('/dashboard/widgets/inventory', { headers })
        .then(r => r.json()).then(d => {
            document.getElementById('widget-stock').textContent = d.data?.low_stock_count ?? 0;
        }).catch(() => {});

    fetch('/dashboard/widgets/hr', { headers })
        .then(r => r.json()).then(d => {
            document.getElementById('widget-employees').textContent = d.data?.active_employees ?? 0;
        }).catch(() => {});

    // Revenue chart
    fetch('/dashboard/widgets/revenue', { headers })
        .then(r => r.json()).then(d => {
            const months = d.data?.map(r => r.label) ?? [];
            const values = d.data?.map(r => r.revenue) ?? [];
            document.getElementById('chart-period').textContent = 'Last 12 months';
            new Chart(document.getElementById('revenueChart'), {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{ label: 'Revenue (৳)', data: values, backgroundColor: 'rgba(37,99,235,.7)', borderRadius: 6 }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        }).catch(() => {
            document.getElementById('chart-period').textContent = 'No data yet';
        });
});
</script>
@endpush
