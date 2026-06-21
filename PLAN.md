# BizCore ERP — Laravel Migration Plan

## Context

The current BizCore ERP is a custom PHP 8.3 framework with raw PDO, a hand-rolled DI container, PHP template views, and a custom router. A full audit found:

- **12 missing DB tables** and **9 missing columns** causing runtime SQL failures
- **11 missing service classes** bound in the DI container — any route that needs them throws `EntryNotFoundException`
- **46 GET routes** (of 403 total web routes) reference controller classes that don't exist
- **16 stub methods** explicitly return "not yet implemented" errors

Migrating to **Laravel 11** solves all of these structurally: Eloquent migrations enforce schema integrity, the service container auto-wires, Artisan scaffolds missing controllers, and Laravel's auth/mail/PDF ecosystem eliminates entire categories of stub work.

**Scope confirmed by audit:**
- 46 DB tables → Eloquent models
- 403 web routes + 127 API routes → Laravel route files
- 162 PHP template views → Blade conversion
- 13 existing services (10 keep, 3 refactor)
- All middleware recreated in Laravel format
- Infrastructure (MySQL 8.0, Redis 7, Docker Compose, MailHog) stays unchanged

---

## Phase 1 — Project Setup & Infrastructure (Week 1)

### 1.1 — Bootstrap Laravel 11

```bash
composer create-project laravel/laravel bizcore-laravel
cd bizcore-laravel
```

Install key packages:
```bash
composer require firebase/php-jwt:^6.10          # Keep existing JWT (API)
composer require predis/predis:^2.2              # Redis client
composer require dompdf/dompdf:^2.0              # PDF generation
composer require phpoffice/phpspreadsheet:^2.0   # Excel export
composer require league/oauth2-google:^5.0       # Google OAuth
composer require intervention/image:^3.0         # Image processing
composer require ramsey/uuid:^4.7                # UUID generation

# Dev tools
composer require --dev laravel/telescope         # Debug toolbar
composer require --dev phpstan/phpstan           # Static analysis
composer require --dev laravel/pint              # Code style (replaces PHP_CS)
```

### 1.2 — Docker Configuration

Update `docker/php/Dockerfile` — keep PHP 8.3-FPM base, add:
- `composer` (already present)
- `php8.3-redis` extension
- `php8.3-gd` for image processing

Update `docker/nginx/default.conf` — change document root to `/var/www/html/public` (Laravel standard). The existing config likely already does this.

`docker-compose.yml` — no service changes needed (MySQL 8.0, Redis 7, MailHog, PHPMyAdmin all stay).

### 1.3 — Environment Variables

Copy `.env.example` and rename/add these Laravel-specific keys:
```
# Renames
MAIL_MAILER=smtp                    # was MAIL_DRIVER
FILESYSTEM_DISK=local               # was STORAGE_DRIVER
QUEUE_CONNECTION=redis              # was QUEUE_DRIVER

# New Laravel-required
SESSION_DRIVER=redis
SESSION_LIFETIME=120
CACHE_STORE=redis
BROADCAST_CONNECTION=log

# Keep all existing:
# APP_*, DB_*, REDIS_*, JWT_*, BKASH_*, NAGAD_*, VAT_*, LOG_*, BCRYPT_ROUNDS
```

Create custom config files:
- `config/modules.php` — which ERP modules are enabled (HR, Payroll, Inventory, etc.)
- `config/vat.php` — `VAT_ENABLED`, `DEFAULT_VAT_RATE`, `VAT_REGISTRATION_NUMBER`
- `config/payment.php` — bKash + Nagad credentials
- `config/fiscal.php` — fiscal year start, currency settings
- `config/jwt.php` — copy from existing (TTL, algo, blacklist, refresh TTL)

### 1.4 — Point at Existing Database

Do **not** recreate the MySQL database. Set `DB_DATABASE=bizcore_erp` and point at the running MySQL container. Run `php artisan migrate:status` to confirm connectivity.

---

## Phase 2 — Database Layer: Migrations + Eloquent Models (Week 1–2)

### 2.1 — Migration Strategy

Run existing SQL schema through `php artisan make:migration` or write migrations manually. The existing `docker/mysql/init.sql` is the source of truth. Create one migration file per table group.

**Tables with `SoftDeletes` (have `deleted_at`):**
`branches`, `users`, `employees`, `departments`, `designations`, `categories`, `products`, `purchase_orders`, `sales_orders`, `invoices`, `customers`, `suppliers`

**Tables without soft deletes** (use hard delete or no delete):
`brands`, `units`, `warehouses`, `roles`, `settings`, `attendance`, `payroll`, `salary_structures`, `salary_components`, `accounts`, `journal_entries`, `journal_entry_lines`, `payments`, `quotations`, `goods_receipts`, and all `*_items` tables

### 2.2 — Fix Missing Schema (incorporate from prior audit)

Add these in a single migration `2024_01_02_000000_fix_missing_schema.php`:

**Missing columns:**
```php
Schema::table('warehouses', function (Blueprint $table) {
    $table->string('location')->nullable()->after('name');
    $table->unsignedInteger('manager_id')->nullable();
    $table->unsignedInteger('capacity')->default(0)->nullable();
    $table->boolean('is_default')->default(false);
    $table->softDeletes();
});

Schema::table('branches', function (Blueprint $table) {
    $table->boolean('is_head')->default(false);
});

Schema::table('expense_categories', function (Blueprint $table) {
    $table->string('color', 20)->nullable()->default('#6c757d');
    $table->enum('status', ['active', 'inactive'])->default('active');
});
```

**Missing tables:** `stock_levels`, `stock_adjustments`, `stock_adjustment_items`, `stock_in_orders`, `stock_in_items`, `stock_out_orders`, `stock_out_items`, `password_reset_tokens`, `activity_log`

### 2.3 — Eloquent Models

Create one model per domain entity. Full list (46 models):

**System:** `Branch`, `Role`, `User`, `Setting`
**Audit:** `LoginHistory`, `AuditLog`, `Notification`, `UserSession`, `ActivityLog`
**HR:** `Department`, `Designation`, `Employee`, `EmployeeTransfer`, `LeaveType`, `LeaveRequest`, `Attendance`
**Payroll:** `SalaryStructure`, `SalaryComponent`, `Payroll`
**CRM:** `Customer`, `Supplier`
**Catalog:** `Category`, `Brand`, `Unit`, `Product`, `ProductVariant`
**Inventory:** `Warehouse`, `Inventory`, `StockMovement`, `StockTransfer`, `StockTransferItem`, `StockLevel`, `StockAdjustment`, `StockAdjustmentItem`, `StockInOrder`, `StockInItem`, `StockOutOrder`, `StockOutItem`
**Purchasing:** `PurchaseOrder`, `PurchaseOrderItem`, `GoodsReceipt`, `GoodsReceiptItem`, `Expense`, `ExpenseCategory`
**Sales:** `Quotation`, `QuotationItem`, `SalesOrder`, `SalesOrderItem`, `Invoice`, `InvoiceItem`, `Payment`, `PaymentAllocation`
**Accounting:** `Account`, `JournalEntry`, `JournalEntryLine`, `TaxRecord`
**Utilities:** `FileUpload`

**Key model patterns to apply consistently:**

```php
// app/Models/User.php
class User extends Authenticatable {
    use SoftDeletes, HasFactory;

    protected $casts = [
        'permissions'      => 'array',    // roles.permissions JSON column
        'status'           => UserStatus::class,
        'last_login_at'    => 'datetime',
        'locked_until'     => 'datetime',
    ];

    public function role(): BelongsTo    { return $this->belongsTo(Role::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function employee(): HasOne   { return $this->hasOne(Employee::class); }

    // Scopes
    public function scopeActive($q)           { return $q->where('status', UserStatus::Active); }
    public function scopeByBranch($q, $id)    { return $q->where('branch_id', $id); }

    // Permission helpers (preserve existing logic)
    public function hasPermission(string $perm): bool { ... }
}

// app/Models/Role.php
class Role extends Model {
    protected $casts = ['permissions' => 'array', 'is_system' => 'boolean'];
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function hasPermission(string $perm): bool { ... }
}

// app/Models/Invoice.php
class Invoice extends Model {
    use SoftDeletes;
    protected $casts = ['invoice_date' => 'date', 'due_date' => 'date'];
    public function items(): HasMany    { return $this->hasMany(InvoiceItem::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function scopeUnpaid($q)    { return $q->whereNotIn('status', ['paid','cancelled']); }
    public function scopeOverdue($q)   { return $q->where('due_date','<', now())->unpaid(); }
}
```

**PHP Enums** to create (replacing current `UserStatus` entity):
- `App\Enums\UserStatus` — Active, Inactive, Locked
- `App\Enums\InvoiceStatus` — Draft, Sent, Partial, Paid, Cancelled
- `App\Enums\PurchaseOrderStatus` — Draft, Submitted, Approved, Received, Cancelled
- `App\Enums\AttendanceStatus`, `App\Enums\ExpenseStatus`, `App\Enums\PayrollStatus`

---

## Phase 3 — Authentication & Authorization (Week 2)

### 3.1 — Guards

Define two guards in `config/auth.php`:

```php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'jwt',     'provider' => 'users'],   // custom JWT driver
],
```

Create `app/Auth/JwtGuard.php` — a custom guard implementing `Illuminate\Contracts\Auth\Guard`. It reads the `Authorization: Bearer <token>` header, validates via `firebase/php-jwt`, checks the Redis blacklist, and returns the authenticated `User` model. Port logic directly from existing `app/Middleware/JwtMiddleware.php`.

### 3.2 — Middleware

Create in `app/Http/Middleware/`:

| File | Ported From | Key Logic |
|---|---|---|
| `PermissionMiddleware.php` | `app/Core/Middleware/PermissionMiddleware.php` | **Copy the 3-part `checkModulePermission()` logic exactly** — `.access` suffix, `hr.` namespace strip, sub-module fallback |
| `ModuleMiddleware.php` | `app/Core/Middleware/ModuleMiddleware.php` | Check `config('modules.enabled')` array |
| `MfaMiddleware.php` | `app/Core/Middleware/MfaMiddleware.php` | Check `session('mfa_verified')` |
| `EnsureApiJson.php` | `app/Core/Middleware/` (API variant) | Set `Accept: application/json` header |

Register aliases in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => PermissionMiddleware::class,
        'module'     => ModuleMiddleware::class,
        'mfa'        => MfaMiddleware::class,
        'jwt.auth'   => JwtAuthMiddleware::class,
    ]);
})
```

### 3.3 — Auth Controllers

Create `app/Http/Controllers/Auth/`:

| Controller | Action |
|---|---|
| `LoginController` | Port from existing `AuthController`; use `Auth::attempt()` |
| `RegisterController` | Port from existing; validate + `User::create()` |
| `PasswordResetController` | Use `Password::sendResetLink()` + `Password::reset()` (Laravel built-in) |
| `EmailVerificationController` | Use `MustVerifyEmail` interface + `VerificationController` |
| `MfaController` | Port TOTP logic; use `session('mfa_verified', true)` |
| `OAuthController` | Port Google OAuth using `league/oauth2-google` (same package) |
| `ProfileController` | Port from existing |

### 3.4 — View Data Sharing

In `App\Providers\AppServiceProvider::boot()`:
```php
View::composer('*', function ($view) {
    $view->with('currentUser', Auth::user());
    $view->with('locale', app()->getLocale());
});
```

---

## Phase 4 — Service Layer (Week 2–3)

### 4.1 — Keep These Services (port to use Eloquent)

Each service injects Eloquent models instead of Repository classes. Preserve all business logic.

| Service | Primary Model(s) | Notes |
|---|---|---|
| `EmployeeService` | `Employee`, `Department`, `Designation` | Keep domain logic |
| `BranchService` | `Branch`, `User` | Multi-branch scoping |
| `AccountingService` | `Account`, `JournalEntry`, `JournalEntryLine` | Double-entry logic, keep exactly |
| `InventoryService` | `StockLevel`, `StockMovement`, `Warehouse` | Weighted avg costing, keep exactly |
| `SalesService` | `Invoice`, `SalesOrder`, `Payment`, `PaymentAllocation` | FIFO payment allocation, keep exactly |
| `PayrollService` | `Payroll`, `SalaryStructure`, `SalaryComponent` | Tax slab calculations, keep exactly |
| `ReportService` | Multiple models | Port queries to Eloquent |

### 4.2 — Create These Missing Services

These were bound in the old container but their files didn't exist. Implement them now:

| Service File | Implementation |
|---|---|
| `AttendanceService` | Wrap `Attendance` model queries; check-in/out logic |
| `CustomerService` | Wrap `Customer` model; credit limit checks |
| `ProductService` | Wrap `Product` model; SKU generation, pricing |
| `StockTransferService` | Use `StockTransfer` + `StockLevel`; decrement source, increment destination |
| `PurchaseService` | Wrap `PurchaseOrder` + `GoodsReceipt`; update stock levels on receipt |
| `InvoiceService` | Wrap `Invoice`; generate invoice number, calculate VAT |
| `ExpenseService` | Wrap `Expense`; approval workflow |
| `ReportingService` | Aggregate queries across multiple models |
| `NotificationService` | `Notification::create()` + broadcast |
| `PdfService` | Thin wrapper around DomPDF: `generate(string $view, array $data): Response` |
| `ExcelService` | Thin wrapper around PhpSpreadsheet |

### 4.3 — Refactor These Services

| Service | Refactor |
|---|---|
| `AuthService` | Remove JWT logic (moved to `JwtGuard`); keep login lockout, password history |
| `UserService` | Remove auth concerns; keep user CRUD, avatar upload |
| `RoleService` | Keep RBAC logic; remove manual permission string management |
| `MailService` | Replace PHPMailer calls with `Mail::to()->send(new SomeMailable())` |

---

## Phase 5 — Routes (Week 3)

Copy the existing `routes/web.php` and `routes/api.php` structure verbatim — the route group prefixes and middleware stacks are correct, only handler strings change from the old namespace.

**Handler format change:**
```php
// Old (custom router)
$router->get('/products', 'Inventory\ProductController@index', 'products.index', ['auth']);

// New (Laravel)
Route::get('/products', [ProductController::class, 'index'])->name('products.index')->middleware('auth');
```

**Route file structure:**
```
routes/
  web.php       — all 403 browser routes, grouped by module with middleware stacks
  api.php       — all 127 API routes under prefix('v1')->middleware('jwt.auth')
  auth.php      — guest-only auth routes (login, register, password reset, OAuth)
  channels.php  — broadcast channels (empty for now)
```

Preserve the exact middleware stacks per group. Example for HR module:
```php
Route::prefix('hr')->middleware(['auth', 'verified', 'mfa', 'module:hr'])->group(function () {
    Route::resource('employees', EmployeeController::class)->middleware('permission:hr.employees.access');
    Route::resource('departments', DepartmentController::class)->middleware('permission:hr.departments.access');
    Route::resource('designations', DesignationController::class)->middleware('permission:hr.designations.access');
});
```

---

## Phase 6 — Controllers (Week 3–4)

Create all controllers. Use `php artisan make:controller` then port logic.

**Web controllers:** organized by module under `app/Http/Controllers/`

```
Auth/           LoginController, RegisterController, PasswordResetController,
                EmailVerificationController, MfaController, OAuthController, ProfileController
HR/             EmployeeController, DepartmentController, DesignationController, AttendanceController
                AttendanceReportController (NEW)
Payroll/        SalaryStructureController, AllowanceController, DeductionController,
                PayrollProcessingController (implement run/approve/disburse), PayslipController (PDF)
Inventory/      ProductController, CategoryController, BrandController, UnitController,
                WarehouseController, StockInController, StockOutController,
                StockTransferController, StockAdjustmentController,
                InventoryReportController (NEW)
Sales/          InvoiceController, QuotationController, SalesOrderController, PaymentController,
                SalesReportController (NEW), SalesReturnController (NEW)
Purchasing/     PurchaseOrderController, GoodsReceiptController, SupplierController,
                PurchaseReportController (NEW), PurchaseReturnController (NEW),
                SupplierInvoiceController (NEW)
CRM/            CustomerController
Accounting/     ChartOfAccountsController, JournalController (add PDF), LedgerController,
                TrialBalanceController, FinancialStatementController, ReconciliationController
Expenses/       ExpenseController, ExpenseCategoryController, ExpenseReportController (NEW)
Reports/        ReportController, VatReportController
Users/          UserController, RoleController, PermissionController (NEW)
Branches/       BranchController
Settings/       SettingsController, BackupController (implement create/restore),
                CurrencyController, NumberingController, ModuleController, PaymentMethodController
NotificationController
DashboardController (implement 4 widget JSON methods)
```

**API controllers:** under `app/Http/Controllers/Api/V1/`

```
AuthApiController   — login, logout, refresh, me, forgot-password, reset-password
UserApiController   — CRUD
ProductApiController — CRUD + search
InventoryApiController — stock levels, movements
CustomerApiController — CRUD + ledger
SalesApiController  — invoices, orders, quotations
PurchasingApiController — POs, GRNs
HrApiController     — employees, attendance
PayrollApiController — payroll, payslips
AccountingApiController — accounts, journals
ReportApiController — all report endpoints
NotificationApiController — list, mark-read
WebhookController   — bKash, Nagad callbacks
```

**`BaseController` pattern for web:**
```php
abstract class BaseController extends Controller {
    protected function success(string $message): void {
        session()->flash('success', $message);
    }
    protected function error(string $message): void {
        session()->flash('error', $message);
    }
    protected function withErrors(array $errors): void {
        session()->flash('errors', $errors);
    }
}
```

**`BaseApiController` pattern:**
```php
abstract class BaseApiController extends Controller {
    protected function success(mixed $data, int $status = 200): JsonResponse {
        return response()->json(['success' => true, 'data' => $data], $status);
    }
    protected function error(string $message, int $status = 400): JsonResponse {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
    protected function currentUser(): ?User {
        return Auth::guard('api')->user();
    }
}
```

---

## Phase 7 — Blade Views (Week 4–5)

Convert all 162 PHP template view files to Blade. The conversion is largely mechanical.

### Conversion Rules

| PHP Template Pattern | Blade Equivalent |
|---|---|
| `<?= sanitize($var) ?>` | `{{ $var }}` (Blade auto-escapes) |
| `<?= $html ?>` (raw) | `{!! $html !!}` |
| `<?php if ($x): ?>` | `@if ($x)` |
| `<?php foreach ($arr as $i): ?>` | `@foreach ($arr as $i)` |
| `<?php else: ?> / endif ?>` | `@else / @endif` |
| `<?= csrf_field() ?>` | `@csrf` |
| `old('field')` | `old('field')` (unchanged) |
| `session()->getFlash('errors', [])` | `session('errors', [])` |
| `date('d M Y', strtotime($d))` | `\Carbon\Carbon::parse($d)->format('d M Y')` |
| `$layout = 'app'; ob_start();` | `@extends('layouts.app')` |
| `$content = ob_get_clean();` | `@section('content') ... @endsection` |
| `<?= $content ?? '' ?>` | `@yield('content')` |
| `include __DIR__ . '/../components/pagination.php'` | `@include('components.pagination')` |

### Layout Conversion

`resources/views/layouts/app.php` → `resources/views/layouts/app.blade.php`
`resources/views/layouts/auth.php` → `resources/views/layouts/auth.blade.php`

The full-page split auth layout with CSS animations (orbs, fade-up, floating icon) converts cleanly — it's pure CSS/HTML, no PHP logic.

### View Directory Map (all convert in-place)

```
auth/           login, register, forgot-password, reset-password, verify-email, mfa
dashboard/      index
employees/      index, create, edit, show, timeline
departments/    index, create, edit, show
designations/   index, create, edit, show
attendance/     index, create, edit, my-records
payroll/        salary-structures/, allowances/, deductions/, process/, payslips/, reports/
products/       index, create, edit, show  +  brands/, units/
inventory/      warehouses/, stock-in/, stock-out/, transfers/, adjustments/, reports/
customers/      index, show, create, edit, orders, ledger
suppliers/      index, show, create, edit, orders, ledger
purchasing/     orders/, grn/, returns/, invoices/  ← create returns/ and invoices/ dirs
sales/          orders/, invoices/, quotations/, payments/, returns/  ← create returns/
accounting/     accounts/, journals/, ledger/, trial-balance/, statements/, reconciliation/
expenses/       index, show, create, edit, reports/
reports/        index, sales, inventory, hr, vat, financial, branch-comparison
settings/       general, company, email, payment-methods, currencies, backup, numbering, tax, modules, audit-log
users/          index, create, edit, show
roles/          index, create, edit, show
branches/       index, create, edit, show
notifications/  index, preferences
profile/        show, edit
components/     pagination, flash-messages, breadcrumbs
```

---

## Phase 8 — Implement Missing Features (Week 5)

These are features that didn't exist in the old codebase — now implement them cleanly in Laravel.

### Report Controllers (NEW — Phase 4 of old plan now built-in)

All follow the same pattern: inject service, call query method, return view with data.

```php
// app/Http/Controllers/HR/AttendanceReportController.php
public function monthly(Request $request): View {
    $data = $this->attendanceService->monthlyReport(
        month: $request->integer('month', now()->month),
        year:  $request->integer('year',  now()->year),
        branchId: Auth::user()->branch_id,
    );
    return view('attendance.reports.monthly', compact('data'));
}
```

| Controller | Routes | Service Method |
|---|---|---|
| `AttendanceReportController` | monthly, summary, lateComers, absentees | `AttendanceService` |
| `InventoryReportController` | stockLedger, stockSummary, valuation, movement, lowStock, expiry | `InventoryService` / `ReportingService` |
| `SalesReportController` | summary, byCustomer, byProduct, outstanding, payments, aging | `SalesService` / `ReportingService` |
| `PurchaseReportController` | summary, bySupplier, byProduct, payments | `PurchaseService` / `ReportingService` |
| `ExpenseReportController` | index | `ExpenseService` |

### Returns & Supplier Invoices (NEW)

Requires new DB tables (add in Phase 2 migration):
- `sales_returns` + `sales_return_items`
- `purchase_returns` + `purchase_return_items`
- `supplier_invoices` + `supplier_invoice_items`

Controllers: `SalesReturnController`, `PurchaseReturnController`, `SupplierInvoiceController`

### Payroll Processing (complete the stubs)

`PayrollProcessingController::run()`:
```php
DB::transaction(function () use ($month, $year, $branchId) {
    $employees = Employee::active()->whereBranchId($branchId)->with('salaryStructure')->get();
    foreach ($employees as $emp) {
        $this->payrollService->processEmployee($emp, $month, $year);
    }
});
```

`PayslipController::pdf()` — use `PdfService::generate('payroll.payslip-pdf', ['payroll' => $payroll])`.

### Dashboard Widgets

Four JSON endpoints (`/dashboard/widgets/sales`, `/widgets/inventory`, etc.) return aggregate data queried via `ReportingService`. Frontend calls these via `fetch()` on page load.

---

## Phase 9 — Testing & Deployment (Week 6)

### Test Strategy

```bash
php artisan make:test AuthTest --unit
php artisan make:test ProductCrudTest --feature
```

Port existing tests from `tests/Unit/` and `tests/Feature/`. Add new feature tests for:
- Login + JWT token lifecycle
- Permission checks for each role
- Invoice creation + payment allocation
- Payroll processing calculation
- Stock level update after goods receipt

### Performance

```bash
php artisan route:cache       # cache all 530 routes
php artisan config:cache      # cache config
php artisan view:cache        # pre-compile all Blade views
php artisan optimize          # run all caches
```

Add Redis for session + cache (already configured in `.env`).

### CI/CD

Update `.github/workflows/ci.yml`:
```yaml
- run: php artisan migrate --force
- run: php artisan test
- run: ./vendor/bin/phpstan analyse
- run: ./vendor/bin/pint --test
```

---

## Execution Order & Timeline

```
Week 1:   Phase 1 (Setup) + Phase 2 (DB Schema + Models)
Week 2:   Phase 3 (Auth) + Phase 4 (Services)
Week 3:   Phase 5 (Routes) + Phase 6 (Controllers — core modules)
Week 4:   Phase 6 (Controllers — remaining) + Phase 7 (Blade Views — bulk)
Week 5:   Phase 7 (Views — cleanup) + Phase 8 (Missing Features)
Week 6:   Phase 9 (Testing + Deployment)
```

---

## Key Decisions & Constraints

| Decision | Choice | Reason |
|---|---|---|
| API token auth | Keep `firebase/php-jwt` | Avoids schema change; existing token format |
| Web session auth | Laravel session guard | Native, no extra package |
| PDF | Keep `dompdf/dompdf` | Already installed, no config change |
| Excel | Keep `phpoffice/phpspreadsheet` | Already installed |
| Google OAuth | Keep `league/oauth2-google` | Already installed |
| RBAC | Custom `PermissionMiddleware` | Preserve 3-part match logic exactly |
| Views | Blade conversion, no Vue/React | Stays server-rendered, minimal JS risk |
| DB | Keep existing MySQL schema | No data migration needed |

---

## Verification Checklist

After each phase:

1. **Phase 1** — `php artisan about` shows no errors; `http://localhost:8080` returns 200
2. **Phase 2** — `php artisan migrate:status` shows all migrations run; `SHOW TABLES` confirms all 46+ tables exist
3. **Phase 3** — Login via web session works; `/api/v1/auth/login` returns JWT; permission check blocks unauthorized user
4. **Phase 4** — No `EntryNotFoundException` on any route; service layer unit tests pass
5. **Phase 5** — `php artisan route:list` shows all 530 routes; no `RouteNotFoundException`
6. **Phase 6** — Each module index page returns 200 with real data for super_admin user
7. **Phase 7** — All 162 Blade views render without PHP errors; `php artisan view:cache` completes without errors
8. **Phase 8** — Each report URL returns paginated data; payslip PDF downloads; payroll `run()` creates `payroll` rows
9. **Phase 9** — `php artisan test` passes; `php artisan optimize` completes; production Docker build succeeds
