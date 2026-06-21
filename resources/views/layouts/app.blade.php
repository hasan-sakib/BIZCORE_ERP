<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — BizCore ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-w: 260px; --primary: #2563eb; }
        body { background: #f0f2f5; }
        /* Sidebar */
        #sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-w);
            background: #1e293b; overflow-y: auto; z-index: 1000; transition: transform .25s;
        }
        #sidebar .brand { padding: 1.25rem 1.5rem; font-size: 1.1rem; font-weight: 700; color: #fff; border-bottom: 1px solid rgba(255,255,255,.08); }
        #sidebar .brand span { color: #60a5fa; }
        #sidebar .nav-section { padding: .5rem 1rem .2rem; font-size: .65rem; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 600; }
        #sidebar .nav-link { color: #94a3b8; padding: .5rem 1.5rem; font-size: .875rem; display: flex; align-items: center; gap: .6rem; border-radius: 6px; margin: 1px 8px; transition: all .15s; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { background: rgba(37,99,235,.2); color: #fff; }
        #sidebar .nav-link i { width: 16px; text-align: center; }
        /* Main */
        #main { margin-left: var(--sidebar-w); }
        #topbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: .75rem 1.5rem; position: sticky; top: 0; z-index: 999; display: flex; align-items: center; justify-content: space-between; }
        #content { padding: 1.5rem; }
        /* Cards */
        .card { border: none; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.06); }
        .card-header { background: #fff; border-bottom: 1px solid #f3f4f6; font-weight: 600; padding: 1rem 1.25rem; border-radius: 12px 12px 0 0 !important; }
        .stat-card { border-radius: 12px; color: #fff; padding: 1.25rem; }
        /* Responsive */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #main { margin-left: 0; }
        }
        /* Table */
        .table { font-size: .875rem; }
        .table thead th { font-weight: 600; border-bottom: 2px solid #e5e7eb; background: #f9fafb; }
        .badge { font-size: .7rem; }
        /* Buttons */
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: #1d4ed8; }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<nav id="sidebar">
    <div class="brand"><i class="fa-solid fa-building-columns me-2"></i>Biz<span>Core</span></div>

    <div class="mt-2">
        <div class="nav-section">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <div class="nav-section">Human Resources</div>
        <a href="{{ route('hr.employees.index') }}" class="nav-link {{ request()->routeIs('hr.employees.*') ? 'active' : '' }}">
            <i class="fa-solid fa-users"></i> Employees
        </a>
        <a href="{{ route('hr.departments.index') }}" class="nav-link {{ request()->routeIs('hr.departments.*') ? 'active' : '' }}">
            <i class="fa-solid fa-sitemap"></i> Departments
        </a>
        <a href="{{ route('hr.attendance.index') }}" class="nav-link {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
            <i class="fa-solid fa-clock"></i> Attendance
        </a>

        <div class="nav-section">Payroll</div>
        <a href="{{ route('payroll.process.index') }}" class="nav-link {{ request()->routeIs('payroll.*') ? 'active' : '' }}">
            <i class="fa-solid fa-money-bill-wave"></i> Payroll
        </a>
        <a href="{{ route('payroll.payslips.index') }}" class="nav-link {{ request()->routeIs('payroll.payslips.*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-invoice-dollar"></i> Payslips
        </a>

        <div class="nav-section">Inventory</div>
        <a href="{{ route('inventory.products.index') }}" class="nav-link {{ request()->routeIs('inventory.products.*') ? 'active' : '' }}">
            <i class="fa-solid fa-box"></i> Products
        </a>
        <a href="{{ route('inventory.stock-in.index') }}" class="nav-link {{ request()->routeIs('inventory.stock-in.*') ? 'active' : '' }}">
            <i class="fa-solid fa-arrow-down-to-bracket"></i> Stock In
        </a>
        <a href="{{ route('inventory.stock-out.index') }}" class="nav-link {{ request()->routeIs('inventory.stock-out.*') ? 'active' : '' }}">
            <i class="fa-solid fa-arrow-up-from-bracket"></i> Stock Out
        </a>
        <a href="{{ route('inventory.warehouses.index') }}" class="nav-link {{ request()->routeIs('inventory.warehouses.*') ? 'active' : '' }}">
            <i class="fa-solid fa-warehouse"></i> Warehouses
        </a>

        <div class="nav-section">Sales</div>
        <a href="{{ route('sales.customers.index') }}" class="nav-link {{ request()->routeIs('sales.customers.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-tie"></i> Customers
        </a>
        <a href="{{ route('sales.orders.index') }}" class="nav-link {{ request()->routeIs('sales.orders.*') ? 'active' : '' }}">
            <i class="fa-solid fa-cart-shopping"></i> Sales Orders
        </a>
        <a href="{{ route('sales.invoices.index') }}" class="nav-link {{ request()->routeIs('sales.invoices.*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-invoice"></i> Invoices
        </a>
        <a href="{{ route('sales.payments.index') }}" class="nav-link {{ request()->routeIs('sales.payments.*') ? 'active' : '' }}">
            <i class="fa-solid fa-credit-card"></i> Payments
        </a>

        <div class="nav-section">Purchasing</div>
        <a href="{{ route('purchasing.suppliers.index') }}" class="nav-link {{ request()->routeIs('purchasing.suppliers.*') ? 'active' : '' }}">
            <i class="fa-solid fa-truck"></i> Suppliers
        </a>
        <a href="{{ route('purchasing.purchase-orders.index') }}" class="nav-link {{ request()->routeIs('purchasing.purchase-orders.*') ? 'active' : '' }}">
            <i class="fa-solid fa-clipboard-list"></i> Purchase Orders
        </a>

        <div class="nav-section">Accounting</div>
        <a href="{{ route('accounting.accounts.index') }}" class="nav-link {{ request()->routeIs('accounting.*') ? 'active' : '' }}">
            <i class="fa-solid fa-book"></i> Chart of Accounts
        </a>
        <a href="{{ route('accounting.journals.index') }}" class="nav-link {{ request()->routeIs('accounting.journals.*') ? 'active' : '' }}">
            <i class="fa-solid fa-journal-whills"></i> Journals
        </a>

        <div class="nav-section">Expenses</div>
        <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
            <i class="fa-solid fa-receipt"></i> Expenses
        </a>

        <div class="nav-section">Reports</div>
        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-bar"></i> Reports
        </a>

        <div class="nav-section">Admin</div>
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-gear"></i> Users
        </a>
        <a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
            <i class="fa-solid fa-code-branch"></i> Branches
        </a>
        <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="fa-solid fa-gear"></i> Settings
        </a>
    </div>
</nav>

{{-- Main content --}}
<div id="main">
    <div id="topbar">
        <button class="btn btn-sm btn-light d-md-none" onclick="document.getElementById('sidebar').classList.toggle('open')">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="fw-semibold text-secondary">@yield('title', 'Dashboard')</div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('notifications.index') }}" class="text-secondary position-relative">
                <i class="fa-solid fa-bell"></i>
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-circle-user me-1"></i>
                    {{ auth()->user()?->name ?? 'User' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div id="content">
        @include('components.flash-messages')
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
