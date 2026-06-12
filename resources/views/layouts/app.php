<!DOCTYPE html>
<html lang="<?= $locale ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="description" content="BizCore ERP - Enterprise Resource Planning">
    <title><?= sanitize($pageTitle ?? 'Dashboard') ?> — BizCore ERP</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom -->
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="<?= isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1' ? 'dark-mode' : '' ?>">

<div class="wrapper d-flex">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <a href="/dashboard" class="brand">
                <i class="fas fa-cubes me-2"></i>
                <span>BizCore ERP</span>
            </a>
            <button class="sidebar-toggle btn btn-sm" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <?php if (isset($currentUser)): ?>
        <div class="sidebar-user">
            <div class="avatar-wrapper">
                <?php if ($currentUser->avatar): ?>
                    <img src="<?= sanitize($currentUser->avatarUrl()) ?>" alt="" class="avatar-img">
                <?php else: ?>
                    <div class="avatar-initials"><?= strtoupper(substr($currentUser->name, 0, 2)) ?></div>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= sanitize($currentUser->name) ?></div>
                <div class="user-role">
                    <span class="badge bg-primary-soft text-primary"><?= sanitize($currentUser->roleName ?? 'User') ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="/dashboard" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>
            </li>

            <!-- HR Management -->
            <li class="nav-section"><span>HR Management</span></li>
            <li class="nav-item has-submenu">
                <a href="#hrm-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-users"></i><span>Employees</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="hrm-menu">
                    <li><a href="/hr/employees" class="nav-link"><i class="fas fa-user-tie"></i> All Employees</a></li>
                    <li><a href="/hr/employees/create" class="nav-link"><i class="fas fa-user-plus"></i> Add Employee</a></li>
                    <li><a href="/hr/departments" class="nav-link"><i class="fas fa-sitemap"></i> Departments</a></li>
                    <li><a href="/hr/designations" class="nav-link"><i class="fas fa-briefcase"></i> Designations</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="/attendance" class="nav-link"><i class="fas fa-clock"></i><span>Attendance</span></a>
            </li>
            <li class="nav-item has-submenu">
                <a href="#payroll-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-money-check-alt"></i><span>Payroll</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="payroll-menu">
                    <li><a href="/payroll/salary-structures" class="nav-link">Salary Structures</a></li>
                    <li><a href="/payroll/process" class="nav-link">Process Payroll</a></li>
                    <li><a href="/payroll/payslips" class="nav-link">Payslips</a></li>
                    <li><a href="/payroll/reports/summary" class="nav-link">Payroll Reports</a></li>
                </ul>
            </li>

            <!-- Inventory -->
            <li class="nav-section"><span>Inventory</span></li>
            <li class="nav-item has-submenu">
                <a href="#products-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-box"></i><span>Products</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="products-menu">
                    <li><a href="/products" class="nav-link">All Products</a></li>
                    <li><a href="/products/create" class="nav-link">Add Product</a></li>
                    <li><a href="/products/categories" class="nav-link">Categories</a></li>
                    <li><a href="/products/brands" class="nav-link">Brands</a></li>
                    <li><a href="/products/units" class="nav-link">Units</a></li>
                </ul>
            </li>
            <li class="nav-item has-submenu">
                <a href="#inventory-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-warehouse"></i><span>Inventory</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="inventory-menu">
                    <li><a href="/inventory/warehouses" class="nav-link">Stock Levels</a></li>
                    <li><a href="/inventory/stock-in" class="nav-link">Stock In</a></li>
                    <li><a href="/inventory/stock-out" class="nav-link">Stock Out</a></li>
                    <li><a href="/inventory/transfers" class="nav-link">Transfers</a></li>
                    <li><a href="/inventory/adjustments" class="nav-link">Adjustments</a></li>
                    <li><a href="/inventory/warehouses" class="nav-link">Warehouses</a></li>
                </ul>
            </li>

            <!-- Procurement -->
            <li class="nav-section"><span>Procurement</span></li>
            <li class="nav-item has-submenu">
                <a href="#purchase-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-shopping-cart"></i><span>Purchase</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="purchase-menu">
                    <li><a href="/purchasing/orders" class="nav-link">Purchase Orders</a></li>
                    <li><a href="/purchasing/grn" class="nav-link">Goods Receipts</a></li>
                    <li><a href="/suppliers" class="nav-link">Suppliers</a></li>
                </ul>
            </li>

            <!-- Sales -->
            <li class="nav-section"><span>Sales & CRM</span></li>
            <li class="nav-item has-submenu">
                <a href="#sales-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-chart-line"></i><span>Sales</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="sales-menu">
                    <li><a href="/sales/quotations" class="nav-link">Quotations</a></li>
                    <li><a href="/sales/orders" class="nav-link">Sales Orders</a></li>
                    <li><a href="/sales/invoices" class="nav-link">Invoices</a></li>
                    <li><a href="/sales/payments" class="nav-link">Payments</a></li>
                    <li><a href="/customers" class="nav-link">Customers</a></li>
                </ul>
            </li>
            <li class="nav-item has-submenu">
                <a href="#expenses-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-receipt"></i><span>Expenses</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="expenses-menu">
                    <li><a href="/expenses" class="nav-link">All Expenses</a></li>
                    <li><a href="/expenses/create" class="nav-link">Add Expense</a></li>
                    <li><a href="/expenses/categories" class="nav-link">Categories</a></li>
                </ul>
            </li>

            <!-- Accounting -->
            <li class="nav-section"><span>Accounting</span></li>
            <li class="nav-item has-submenu">
                <a href="#accounting-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-calculator"></i><span>Accounting</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="accounting-menu">
                    <li><a href="/accounting/accounts" class="nav-link">Chart of Accounts</a></li>
                    <li><a href="/accounting/journals" class="nav-link">Journal Entries</a></li>
                    <li><a href="/accounting/trial-balance" class="nav-link">Trial Balance</a></li>
                    <li><a href="/accounting/income-statement" class="nav-link">Income Statement</a></li>
                    <li><a href="/accounting/balance-sheet" class="nav-link">Balance Sheet</a></li>
                </ul>
            </li>

            <!-- Reports -->
            <li class="nav-section"><span>Reports</span></li>
            <li class="nav-item has-submenu">
                <a href="#reports-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-chart-bar"></i><span>Reports</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="reports-menu">
                    <li><a href="/reports" class="nav-link">All Reports</a></li>
                    <li><a href="/reports/sales" class="nav-link">Sales Report</a></li>
                    <li><a href="/reports/inventory" class="nav-link">Inventory Report</a></li>
                    <li><a href="/reports/financial" class="nav-link">Financial Report</a></li>
                    <li><a href="/reports/vat-mushak" class="nav-link">VAT Return</a></li>
                </ul>
            </li>

            <!-- Settings -->
            <li class="nav-section"><span>Administration</span></li>
            <li class="nav-item has-submenu">
                <a href="#settings-menu" class="nav-link" data-bs-toggle="collapse">
                    <i class="fas fa-cog"></i><span>Settings</span><i class="fas fa-chevron-right ms-auto arrow"></i>
                </a>
                <ul class="collapse submenu" id="settings-menu">
                    <li><a href="/settings" class="nav-link">General</a></li>
                    <li><a href="/users" class="nav-link">Users</a></li>
                    <li><a href="/roles" class="nav-link">Roles</a></li>
                    <li><a href="/branches" class="nav-link">Branches</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <!-- Top Navbar -->
        <nav class="top-navbar navbar navbar-expand-lg">
            <div class="container-fluid">
                <button class="btn btn-sm sidebar-toggle-mobile" id="sidebarToggleMobile">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Branch Selector -->
                <div class="branch-selector ms-3 d-none d-md-flex align-items-center">
                    <i class="fas fa-building me-2 text-muted"></i>
                    <select class="form-select form-select-sm border-0 bg-transparent" id="branchSelector">
                        <option>Main Branch</option>
                    </select>
                </div>

                <div class="ms-auto d-flex align-items-center gap-3">
                    <!-- Dark Mode Toggle -->
                    <button class="btn btn-sm icon-btn" id="darkModeToggle" title="Toggle Dark Mode">
                        <i class="fas fa-moon"></i>
                    </button>

                    <!-- Notifications -->
                    <div class="dropdown">
                        <button class="btn btn-sm icon-btn position-relative" data-bs-toggle="dropdown" id="notificationBtn">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger notification-badge" id="notificationCount" style="display:none">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width:320px">
                            <div class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                                <h6 class="mb-0">Notifications</h6>
                                <a href="#" class="text-muted small" onclick="markAllRead(event)">Mark all read</a>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="text-center text-muted p-3 small">No new notifications</div>
                            </div>
                            <div class="dropdown-footer text-center p-2">
                                <a href="/notifications" class="text-primary small">View all</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="dropdown">
                        <button class="btn btn-sm d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <div class="avatar-sm" style="width:32px;height:32px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--bs-primary);color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0;">
                                <?php if (!empty($currentUser->avatar)): ?>
                                    <img src="<?= sanitize($currentUser->avatarUrl()) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <?= strtoupper(substr($currentUser->name ?? 'U', 0, 2)) ?>
                                <?php endif; ?>
                            </div>
                            <span class="d-none d-md-inline"><?= sanitize($currentUser->name ?? '') ?></span>
                            <i class="fas fa-chevron-down small"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="/logout" method="POST">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <div class="container-fluid px-4 pt-3">
            <?php if ($session->hasFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= sanitize($session->getFlash('success')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($session->hasFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= sanitize($session->getFlash('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Page Content -->
        <div class="container-fluid px-4 pb-4">
            <?php if (isset($pageTitle)): ?>
            <div class="page-header d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="page-title mb-1"><?= sanitize($pageTitle) ?></h4>
                    <?php if (isset($breadcrumbs)): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                            <?php foreach ($breadcrumbs as $label => $url): ?>
                                <?php if (is_string($label) && $url): ?>
                                    <li class="breadcrumb-item"><a href="<?= $url ?>"><?= sanitize($label) ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?= sanitize(is_int($label) ? $url : $label) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                    <?php endif; ?>
                </div>
                <?php if (isset($headerActions)): ?>
                    <div class="header-actions"><?= $headerActions ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- Custom JS -->
<script src="/assets/js/app.js"></script>
</body>
</html>
