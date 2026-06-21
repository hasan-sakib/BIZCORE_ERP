<?php

use App\Http\Controllers\Api\V1\AccountingApiController;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\HrApiController;
use App\Http\Controllers\Api\V1\InventoryApiController;
use App\Http\Controllers\Api\V1\NotificationApiController;
use App\Http\Controllers\Api\V1\PayrollApiController;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\V1\PurchasingApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use App\Http\Controllers\Api\V1\SalesApiController;
use App\Http\Controllers\Api\V1\UserApiController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Health check (public)
    Route::get('/ping', fn () => response()->json(['status' => 'ok', 'version' => '1.0']))->name('ping');

    // ─── Auth (public) ───────────────────────────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('login', [AuthApiController::class, 'login'])->name('login');
        Route::post('forgot-password', [AuthApiController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [AuthApiController::class, 'resetPassword'])->name('reset-password');
    });

    // ─── Webhook callbacks (public, verified by signature) ───────────────────
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::post('bkash', [WebhookController::class, 'bkash'])->name('bkash');
        Route::post('nagad', [WebhookController::class, 'nagad'])->name('nagad');
    });

    // ─── Authenticated (JWT) ──────────────────────────────────────────────────
    Route::middleware(['jwt.auth', 'api.json'])->group(function () {

        // Auth: token lifecycle + profile
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('logout', [AuthApiController::class, 'logout'])->name('logout');
            Route::post('refresh', [AuthApiController::class, 'refresh'])->name('refresh');
            Route::get('me', [AuthApiController::class, 'me'])->name('me');
            Route::put('me', [AuthApiController::class, 'updateProfile'])->name('me.update');
            Route::post('me/password', [AuthApiController::class, 'changePassword'])->name('me.password');
        });

        // Users
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserApiController::class, 'index'])->name('index');
            Route::post('/', [UserApiController::class, 'store'])->name('store');
            Route::get('{id}', [UserApiController::class, 'show'])->name('show');
            Route::put('{id}', [UserApiController::class, 'update'])->name('update');
            Route::delete('{id}', [UserApiController::class, 'destroy'])->name('destroy');
            Route::post('{id}/activate', [UserApiController::class, 'activate'])->name('activate');
            Route::post('{id}/deactivate', [UserApiController::class, 'deactivate'])->name('deactivate');
        });

        // Products
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductApiController::class, 'index'])->name('index');
            Route::post('/', [ProductApiController::class, 'store'])->name('store');
            Route::get('search', [ProductApiController::class, 'search'])->name('search');
            Route::get('{id}', [ProductApiController::class, 'show'])->name('show');
            Route::put('{id}', [ProductApiController::class, 'update'])->name('update');
            Route::delete('{id}', [ProductApiController::class, 'destroy'])->name('destroy');
            Route::get('{id}/stock', [ProductApiController::class, 'stockLevels'])->name('stock');
        });

        // Inventory
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('stock-levels', [InventoryApiController::class, 'stockLevels'])->name('stock-levels');
            Route::post('stock-in', [InventoryApiController::class, 'stockIn'])->name('stock-in');
            Route::post('stock-out', [InventoryApiController::class, 'stockOut'])->name('stock-out');
            Route::post('adjust', [InventoryApiController::class, 'adjust'])->name('adjust');
            Route::get('movements', [InventoryApiController::class, 'movements'])->name('movements');
            Route::get('low-stock', [InventoryApiController::class, 'lowStock'])->name('low-stock');
            Route::get('valuation', [InventoryApiController::class, 'valuation'])->name('valuation');
        });

        // Customers
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomerApiController::class, 'index'])->name('index');
            Route::post('/', [CustomerApiController::class, 'store'])->name('store');
            Route::get('{id}', [CustomerApiController::class, 'show'])->name('show');
            Route::put('{id}', [CustomerApiController::class, 'update'])->name('update');
            Route::delete('{id}', [CustomerApiController::class, 'destroy'])->name('destroy');
            Route::get('{id}/ledger', [CustomerApiController::class, 'ledger'])->name('ledger');
            Route::get('{id}/invoices', [CustomerApiController::class, 'invoices'])->name('invoices');
        });

        // Sales
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('orders', [SalesApiController::class, 'orders'])->name('orders');
            Route::post('orders', [SalesApiController::class, 'createOrder'])->name('orders.store');
            Route::get('orders/{id}', [SalesApiController::class, 'showOrder'])->name('orders.show');
            Route::post('orders/{id}/confirm', [SalesApiController::class, 'confirmOrder'])->name('orders.confirm');
            Route::post('orders/{id}/cancel', [SalesApiController::class, 'cancelOrder'])->name('orders.cancel');

            Route::get('invoices', [SalesApiController::class, 'invoices'])->name('invoices');
            Route::post('invoices', [SalesApiController::class, 'createInvoice'])->name('invoices.store');
            Route::get('invoices/{id}', [SalesApiController::class, 'showInvoice'])->name('invoices.show');
            Route::post('invoices/{id}/cancel', [SalesApiController::class, 'cancelInvoice'])->name('invoices.cancel');

            Route::get('quotations', [SalesApiController::class, 'quotations'])->name('quotations');
            Route::post('quotations', [SalesApiController::class, 'createQuotation'])->name('quotations.store');
            Route::get('quotations/{id}', [SalesApiController::class, 'showQuotation'])->name('quotations.show');
            Route::post('quotations/{id}/convert', [SalesApiController::class, 'convertQuotation'])->name('quotations.convert');

            Route::post('payments', [SalesApiController::class, 'receivePayment'])->name('payments.store');
            Route::get('payments', [SalesApiController::class, 'payments'])->name('payments');
        });

        // Purchasing
        Route::prefix('purchasing')->name('purchasing.')->group(function () {
            Route::get('orders', [PurchasingApiController::class, 'orders'])->name('orders');
            Route::post('orders', [PurchasingApiController::class, 'createOrder'])->name('orders.store');
            Route::get('orders/{id}', [PurchasingApiController::class, 'showOrder'])->name('orders.show');
            Route::post('orders/{id}/submit', [PurchasingApiController::class, 'submitOrder'])->name('orders.submit');
            Route::post('orders/{id}/approve', [PurchasingApiController::class, 'approveOrder'])->name('orders.approve');
            Route::post('orders/{id}/cancel', [PurchasingApiController::class, 'cancelOrder'])->name('orders.cancel');

            Route::get('grn', [PurchasingApiController::class, 'goodsReceipts'])->name('grn');
            Route::post('grn', [PurchasingApiController::class, 'createGoodsReceipt'])->name('grn.store');
            Route::get('grn/{id}', [PurchasingApiController::class, 'showGoodsReceipt'])->name('grn.show');
        });

        // HR
        Route::prefix('hr')->name('hr.')->group(function () {
            Route::get('employees', [HrApiController::class, 'employees'])->name('employees');
            Route::post('employees', [HrApiController::class, 'createEmployee'])->name('employees.store');
            Route::get('employees/{id}', [HrApiController::class, 'showEmployee'])->name('employees.show');
            Route::put('employees/{id}', [HrApiController::class, 'updateEmployee'])->name('employees.update');

            Route::get('attendance', [HrApiController::class, 'attendance'])->name('attendance');
            Route::post('attendance', [HrApiController::class, 'recordAttendance'])->name('attendance.store');
            Route::put('attendance/{id}', [HrApiController::class, 'updateAttendance'])->name('attendance.update');

            Route::get('departments', [HrApiController::class, 'departments'])->name('departments');
            Route::get('designations', [HrApiController::class, 'designations'])->name('designations');
        });

        // Payroll
        Route::prefix('payroll')->name('payroll.')->group(function () {
            Route::get('/', [PayrollApiController::class, 'index'])->name('index');
            Route::post('process', [PayrollApiController::class, 'process'])->name('process');
            Route::get('{id}', [PayrollApiController::class, 'show'])->name('show');
            Route::post('{id}/approve', [PayrollApiController::class, 'approve'])->name('approve');
            Route::post('{id}/disburse', [PayrollApiController::class, 'disburse'])->name('disburse');
            Route::get('{id}/payslip', [PayrollApiController::class, 'payslip'])->name('payslip');
        });

        // Accounting
        Route::prefix('accounting')->name('accounting.')->group(function () {
            Route::get('accounts', [AccountingApiController::class, 'accounts'])->name('accounts');
            Route::post('accounts', [AccountingApiController::class, 'createAccount'])->name('accounts.store');
            Route::get('accounts/{id}', [AccountingApiController::class, 'showAccount'])->name('accounts.show');
            Route::put('accounts/{id}', [AccountingApiController::class, 'updateAccount'])->name('accounts.update');

            Route::get('journals', [AccountingApiController::class, 'journals'])->name('journals');
            Route::post('journals', [AccountingApiController::class, 'createJournal'])->name('journals.store');
            Route::get('journals/{id}', [AccountingApiController::class, 'showJournal'])->name('journals.show');
            Route::post('journals/{id}/post', [AccountingApiController::class, 'postJournal'])->name('journals.post');
            Route::post('journals/{id}/reverse', [AccountingApiController::class, 'reverseJournal'])->name('journals.reverse');

            Route::get('trial-balance', [AccountingApiController::class, 'trialBalance'])->name('trial-balance');
            Route::get('income-statement', [AccountingApiController::class, 'incomeStatement'])->name('income-statement');
            Route::get('balance-sheet', [AccountingApiController::class, 'balanceSheet'])->name('balance-sheet');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('dashboard', [ReportApiController::class, 'dashboard'])->name('dashboard');
            Route::get('sales', [ReportApiController::class, 'sales'])->name('sales');
            Route::get('purchasing', [ReportApiController::class, 'purchasing'])->name('purchasing');
            Route::get('inventory', [ReportApiController::class, 'inventory'])->name('inventory');
            Route::get('payroll', [ReportApiController::class, 'payroll'])->name('payroll');
            Route::get('expenses', [ReportApiController::class, 'expenses'])->name('expenses');
            Route::get('vat', [ReportApiController::class, 'vat'])->name('vat');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationApiController::class, 'index'])->name('index');
            Route::get('unread-count', [NotificationApiController::class, 'unreadCount'])->name('unread-count');
            Route::post('{id}/read', [NotificationApiController::class, 'markRead'])->name('read');
            Route::post('read-all', [NotificationApiController::class, 'markAllRead'])->name('read-all');
            Route::delete('{id}', [NotificationApiController::class, 'destroy'])->name('destroy');
        });
    });
});
