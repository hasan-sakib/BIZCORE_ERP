<?php ob_start(); ?>

<div class="row g-4">
    <!-- Sales Reports -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Sales Reports</h6></div>
            <div class="list-group list-group-flush">
                <a href="/reports/sales" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Sales Report</a>
                <a href="/reports/customer-aging" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Customer Aging</a>
            </div>
        </div>
    </div>

    <!-- Purchase Reports -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-warning text-dark"><h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Procurement Reports</h6></div>
            <div class="list-group list-group-flush">
                <a href="/reports/purchases" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Purchase Report</a>
                <a href="/reports/supplier-aging" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Supplier Aging</a>
            </div>
        </div>
    </div>

    <!-- Inventory Reports -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-white"><h6 class="mb-0"><i class="fas fa-warehouse me-2"></i>Inventory Reports</h6></div>
            <div class="list-group list-group-flush">
                <a href="/reports/inventory" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Stock Summary</a>
            </div>
        </div>
    </div>

    <!-- Financial Reports -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Financial Reports</h6></div>
            <div class="list-group list-group-flush">
                <a href="/accounting/income-statement" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Income Statement</a>
                <a href="/accounting/balance-sheet" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Balance Sheet</a>
                <a href="/accounting/trial-balance" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Trial Balance</a>
                <a href="/reports/profit-loss" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Profit & Loss</a>
            </div>
        </div>
    </div>

    <!-- HR Reports -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-secondary text-white"><h6 class="mb-0"><i class="fas fa-users me-2"></i>HR Reports</h6></div>
            <div class="list-group list-group-flush">
                <a href="/reports/hr" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>HR Summary</a>
                <a href="/payroll/reports/summary" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>Payroll Report</a>
            </div>
        </div>
    </div>

    <!-- VAT Reports -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-danger text-white"><h6 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Tax & VAT Reports</h6></div>
            <div class="list-group list-group-flush">
                <a href="/reports/vat-mushak" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>VAT Mushak</a>
                <a href="/reports/vat-return" class="list-group-item list-group-item-action"><i class="fas fa-angle-right me-2 text-muted"></i>VAT Return</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
