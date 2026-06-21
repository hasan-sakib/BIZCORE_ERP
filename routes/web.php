<?php

use App\Http\Controllers\Accounting\ChartOfAccountsController;
use App\Http\Controllers\Accounting\FinancialStatementController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\LedgerController;
use App\Http\Controllers\Accounting\ReconciliationController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use App\Http\Controllers\Branches\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Expenses\ExpenseCategoryController;
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\HR\AttendanceController;
use App\Http\Controllers\HR\DepartmentController;
use App\Http\Controllers\HR\DesignationController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\Inventory\BrandController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockInController;
use App\Http\Controllers\Inventory\StockOutController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\UnitController;
use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Payroll\AllowanceController;
use App\Http\Controllers\Payroll\DeductionController;
use App\Http\Controllers\Payroll\PayrollProcessingController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Http\Controllers\Payroll\SalaryStructureController;
use App\Http\Controllers\Purchasing\GoodsReceiptController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\SupplierController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Reports\VatReportController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\PaymentController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Settings\BackupController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Users\RoleController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// ─── Authenticated & MFA-verified ─────────────────────────────────────────────
Route::middleware(['auth', 'mfa'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/widgets/sales', [DashboardController::class, 'salesWidget'])->name('dashboard.sales');
    Route::get('/dashboard/widgets/inventory', [DashboardController::class, 'inventoryWidget'])->name('dashboard.inventory');
    Route::get('/dashboard/widgets/hr', [DashboardController::class, 'hrWidget'])->name('dashboard.hr');
    Route::get('/dashboard/widgets/finance', [DashboardController::class, 'financeWidget'])->name('dashboard.finance');
    Route::get('/dashboard/widgets/revenue', [DashboardController::class, 'revenueWidget'])->name('dashboard.revenue');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // ─── HR ─────────────────────────────────────────────────────────────────────
    Route::prefix('hr')->name('hr.')->middleware('module:hr')->group(function () {

        Route::middleware('permission:hr.employees.access')->group(function () {
            Route::resource('employees', EmployeeController::class);
            Route::get('employees/{employee}/timeline', [EmployeeController::class, 'timeline'])->name('employees.timeline');
            Route::post('employees/{employee}/transfer', [EmployeeController::class, 'transfer'])->name('employees.transfer');
            Route::post('employees/{employee}/activate', [EmployeeController::class, 'activate'])->name('employees.activate');
            Route::post('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])->name('employees.deactivate');
        });

        Route::resource('departments', DepartmentController::class)->middleware('permission:hr.departments.access');
        Route::resource('designations', DesignationController::class)->middleware('permission:hr.designations.access');

        Route::middleware('permission:hr.attendance.access')->group(function () {
            Route::resource('attendance', AttendanceController::class);
            Route::get('attendance/report/monthly', [AttendanceController::class, 'monthlyReport'])->name('attendance.monthly-report');
            Route::get('attendance/my-records', [AttendanceController::class, 'myRecords'])->name('attendance.my-records');
        });
    });

    // ─── Payroll ─────────────────────────────────────────────────────────────────
    Route::prefix('payroll')->name('payroll.')->middleware(['module:payroll', 'permission:payroll.access'])->group(function () {
        Route::resource('salary-structures', SalaryStructureController::class);
        Route::resource('allowances', AllowanceController::class);
        Route::resource('deductions', DeductionController::class);

        Route::get('process', [PayrollProcessingController::class, 'index'])->name('process.index');
        Route::post('process/run', [PayrollProcessingController::class, 'run'])->name('process.run');
        Route::post('process/{payroll}/approve', [PayrollProcessingController::class, 'approve'])->name('process.approve');
        Route::post('process/{payroll}/disburse', [PayrollProcessingController::class, 'disburse'])->name('process.disburse');

        Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payroll}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payroll}/pdf', [PayslipController::class, 'pdf'])->name('payslips.pdf');

        Route::get('reports', [PayrollProcessingController::class, 'reports'])->name('reports');
    });

    // ─── Inventory / Products ─────────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->middleware('module:inventory')->group(function () {

        Route::resource('products', ProductController::class)->middleware('permission:inventory.products.access');
        Route::get('products/{product}/variants', [ProductController::class, 'variants'])->name('products.variants');
        Route::middleware('permission:inventory.categories.access')->group(function () {
            Route::resource('categories', CategoryController::class);
        });
        Route::resource('brands', BrandController::class)->middleware('permission:inventory.access');
        Route::resource('units', UnitController::class)->middleware('permission:inventory.access');

        Route::middleware('permission:inventory.warehouses.access')->group(function () {
            Route::resource('warehouses', WarehouseController::class);
        });

        Route::middleware('permission:inventory.stock.access')->group(function () {
            Route::resource('stock-in', StockInController::class)->except(['edit', 'update', 'destroy']);
            Route::resource('stock-out', StockOutController::class)->except(['edit', 'update', 'destroy']);
            Route::resource('stock-transfers', StockTransferController::class)->except(['edit', 'update']);
            Route::post('stock-transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->name('stock-transfers.approve');
            Route::post('stock-transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
            Route::resource('stock-adjustments', StockAdjustmentController::class)->except(['edit', 'update', 'destroy']);
        });

        Route::get('reports/stock-ledger', [ProductController::class, 'stockLedger'])->name('reports.stock-ledger')->middleware('permission:inventory.reports.access');
        Route::get('reports/valuation', [ProductController::class, 'valuation'])->name('reports.valuation')->middleware('permission:inventory.reports.access');
        Route::get('reports/low-stock', [ProductController::class, 'lowStock'])->name('reports.low-stock')->middleware('permission:inventory.reports.access');
    });

    // ─── Sales ───────────────────────────────────────────────────────────────────
    Route::prefix('sales')->name('sales.')->middleware('module:sales')->group(function () {

        Route::middleware('permission:sales.customers.access')->group(function () {
            Route::resource('customers', CustomerController::class);
            Route::get('customers/{customer}/ledger', [CustomerController::class, 'ledger'])->name('customers.ledger');
            Route::get('customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
        });

        Route::resource('quotations', QuotationController::class)->middleware('permission:sales.quotations.access');
        Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToOrder'])->name('quotations.convert')->middleware('permission:sales.quotations.access');

        Route::middleware('permission:sales.orders.access')->group(function () {
            Route::resource('orders', SalesOrderController::class);
            Route::post('orders/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('orders.confirm');
            Route::post('orders/{order}/cancel', [SalesOrderController::class, 'cancel'])->name('orders.cancel');
        });

        Route::middleware('permission:sales.invoices.access')->group(function () {
            Route::resource('invoices', InvoiceController::class);
            Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
            Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
            Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->name('invoices.send');
        });

        Route::middleware('permission:sales.payments.access')->group(function () {
            Route::resource('payments', PaymentController::class)->except(['edit', 'update', 'destroy']);
            Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        });

        Route::get('reports/summary', [ReportController::class, 'sales'])->name('reports.summary')->middleware('permission:sales.reports.access');
        Route::get('reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding')->middleware('permission:sales.reports.access');
        Route::get('reports/aging', [ReportController::class, 'aging'])->name('reports.aging')->middleware('permission:sales.reports.access');
    });

    // ─── Purchasing ───────────────────────────────────────────────────────────────
    Route::prefix('purchasing')->name('purchasing.')->middleware('module:purchasing')->group(function () {

        Route::middleware('permission:purchasing.suppliers.access')->group(function () {
            Route::resource('suppliers', SupplierController::class);
            Route::get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger'])->name('suppliers.ledger');
        });

        Route::middleware('permission:purchasing.orders.access')->group(function () {
            Route::resource('purchase-orders', PurchaseOrderController::class);
            Route::post('purchase-orders/{po}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
            Route::post('purchase-orders/{po}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
            Route::post('purchase-orders/{po}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
            Route::get('purchase-orders/{po}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');
        });

        Route::middleware('permission:purchasing.grn.access')->group(function () {
            Route::resource('goods-receipts', GoodsReceiptController::class)->except(['edit', 'update', 'destroy']);
        });

        Route::get('reports', [ReportController::class, 'purchasing'])->name('reports')->middleware('permission:purchasing.reports.access');
    });

    // ─── Accounting ───────────────────────────────────────────────────────────────
    Route::prefix('accounting')->name('accounting.')->middleware(['module:accounting', 'permission:accounting.access'])->group(function () {

        Route::resource('accounts', ChartOfAccountsController::class);
        Route::get('accounts/{account}/ledger', [LedgerController::class, 'account'])->name('accounts.ledger');

        Route::resource('journals', JournalController::class);
        Route::post('journals/{entry}/post', [JournalController::class, 'post'])->name('journals.post');
        Route::post('journals/{entry}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse');
        Route::get('journals/{entry}/pdf', [JournalController::class, 'pdf'])->name('journals.pdf');

        Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance');
        Route::get('income-statement', [FinancialStatementController::class, 'incomeStatement'])->name('income-statement');
        Route::get('balance-sheet', [FinancialStatementController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation');
        Route::post('reconciliation', [ReconciliationController::class, 'store'])->name('reconciliation.store');
    });

    // ─── Expenses ─────────────────────────────────────────────────────────────────
    Route::prefix('expenses')->name('expenses.')->middleware('module:expenses')->group(function () {
        Route::resource('categories', ExpenseCategoryController::class)->middleware('permission:expenses.categories.access');

        Route::middleware('permission:expenses.access')->group(function () {
            Route::resource('/', ExpenseController::class)->names([
                'index'   => 'index',   'create'  => 'create',
                'store'   => 'store',   'show'    => 'show',
                'edit'    => 'edit',    'update'  => 'update',
                'destroy' => 'destroy',
            ]);
            Route::post('{expense}/approve', [ExpenseController::class, 'approve'])->name('approve');
            Route::post('{expense}/reject', [ExpenseController::class, 'reject'])->name('reject');
            Route::post('{expense}/pay', [ExpenseController::class, 'markPaid'])->name('pay');
            Route::get('report', [ExpenseController::class, 'report'])->name('report');
        });
    });

    // ─── Reports ──────────────────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.access')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('hr', [ReportController::class, 'hr'])->name('hr');
        Route::get('financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('branch-comparison', [ReportController::class, 'branchComparison'])->name('branch-comparison');
        Route::get('vat', [VatReportController::class, 'index'])->name('vat');
        Route::post('vat/file', [VatReportController::class, 'file'])->name('vat.file');
        Route::get('vat/{record}/pdf', [VatReportController::class, 'pdf'])->name('vat.pdf');
    });

    // ─── Users & Roles ────────────────────────────────────────────────────────────
    Route::middleware('permission:users.access')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/lock', [UserController::class, 'lock'])->name('users.lock');
    });

    Route::resource('roles', RoleController::class)->middleware('permission:roles.access');
    Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions')->middleware('permission:roles.access');

    // ─── Branches ─────────────────────────────────────────────────────────────────
    Route::middleware('permission:branches.access')->group(function () {
        Route::resource('branches', BranchController::class);
        Route::post('branches/{branch}/enable', [BranchController::class, 'enable'])->name('branches.enable');
        Route::post('branches/{branch}/disable', [BranchController::class, 'disable'])->name('branches.disable');
    });

    // ─── Settings ─────────────────────────────────────────────────────────────────
    Route::prefix('settings')->name('settings.')->middleware('permission:settings.access')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('general', [SettingsController::class, 'updateGeneral'])->name('general');
        Route::put('company', [SettingsController::class, 'updateCompany'])->name('company');
        Route::put('email', [SettingsController::class, 'updateEmail'])->name('email');
        Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('backup/create', [BackupController::class, 'create'])->name('backup.create');
        Route::post('backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
        Route::put('numbering', [SettingsController::class, 'updateNumbering'])->name('numbering');
        Route::put('tax', [SettingsController::class, 'updateTax'])->name('tax');
        Route::put('modules', [SettingsController::class, 'updateModules'])->name('modules');
        Route::get('audit-log', [SettingsController::class, 'auditLog'])->name('audit-log');
    });
});
