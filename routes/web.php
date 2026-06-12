<?php

declare(strict_types=1);

/**
 * BizCore ERP - Web Routes
 *
 * All browser-facing (session-authenticated) routes are defined here.
 * Routes are loaded by the Router singleton during container bootstrap.
 *
 * Conventions:
 *  - $router->get|post|put|patch|delete|any($uri, $handler, $middlewares)
 *  - $handler  = 'ControllerClass@method'  OR  callable
 *  - Route groups share a common prefix, middleware, and namespace prefix
 *  - All controllers live under App\Http\Controllers\ (namespace auto-prefixed
 *    by the router when the handler contains no backslash)
 *
 * Middleware handles:
 *  'auth'         => verifies active session / user is logged in
 *  'guest'        => redirects authenticated users away (login page, etc.)
 *  'permission'   => RBAC permission check  e.g. 'permission:products.create'
 *  'module'       => checks the ERP module is enabled  e.g. 'module:payroll'
 *  'csrf'         => verifies CSRF token on POST/PUT/PATCH/DELETE
 *  'throttle'     => per-route rate limiting
 *  'verified'     => email-verified users only
 *  'mfa'          => MFA-verified users only
 *
 * @var \App\Core\Router $router
 *
 * @package BizCore\ERP
 * @version 1.0.0
 */

// ============================================================================
// AUTHENTICATION ROUTES  (guests only)
// ============================================================================

$router->group(['prefix' => '', 'middleware' => ['guest', 'throttle:10,1']], function () use ($router): void {

    // Login
    $router->get('/login',  'Auth\AuthController@showLogin',   'auth.login');
    $router->post('/login', 'Auth\AuthController@login',       'auth.login.submit');

    // Registration
    $router->get('/register',  'Auth\RegisterController@showForm', 'auth.register');
    $router->post('/register', 'Auth\RegisterController@register', 'auth.register.submit');

    // Forgot Password
    $router->get('/forgot-password',
        'Auth\PasswordResetController@showForgotForm',         'auth.password.request');
    $router->post('/forgot-password',
        'Auth\PasswordResetController@sendResetLink',          'auth.password.email');

    // Reset Password (token from email)
    $router->get('/reset-password/{token}',
        'Auth\PasswordResetController@showResetForm',          'auth.password.reset');
    $router->post('/reset-password',
        'Auth\PasswordResetController@reset',                  'auth.password.update');
});

// Google OAuth (no guest middleware — callback state validation handles security)
$router->get('/auth/google',            'Auth\OAuthController@redirect',     'auth.google');
$router->get('/auth/google/callback',   'Auth\OAuthController@callback',     'auth.google.callback');
$router->get('/auth/complete-profile',  'Auth\OAuthController@showComplete', 'auth.complete-profile');
$router->post('/auth/complete-profile', 'Auth\OAuthController@complete',     'auth.complete-profile.submit');

// Logout (authenticated)
$router->post('/logout', 'Auth\AuthController@logout', 'auth.logout', ['auth', 'csrf']);

// MFA verification (shown after login if MFA is required)
$router->get('/auth/mfa',          'Auth\MfaController@showVerifyForm',  'auth.mfa.verify');
$router->post('/auth/mfa/verify',  'Auth\MfaController@verify',          'auth.mfa.verify.submit',  ['csrf']);
$router->get('/auth/mfa/setup',    'Auth\MfaController@showSetup',       'auth.mfa.setup',         ['auth']);
$router->post('/auth/mfa/confirm', 'Auth\MfaController@confirm',         'auth.mfa.setup.confirm',  ['auth', 'csrf']);
$router->post('/auth/mfa/disable', 'Auth\MfaController@disable',         'auth.mfa.disable',        ['auth', 'csrf']);

// Email verification
$router->get('/email/verify',            'Auth\EmailVerificationController@notice',  'verification.notice', ['auth']);
$router->get('/email/verify/{id}/{hash}','Auth\EmailVerificationController@verify',  'verification.verify', ['auth', 'signed']);
$router->post('/email/resend',           'Auth\EmailVerificationController@resend',  'verification.resend', ['auth', 'csrf', 'throttle:3,1']);

// ============================================================================
// AUTHENTICATED WEB ROUTES
// ============================================================================

$router->group(['middleware' => ['auth', 'verified', 'mfa']], function () use ($router): void {

    // ── Dashboard ─────────────────────────────────────────────────────────

    $router->get('/',          'DashboardController@index',  'dashboard');
    $router->get('/dashboard', 'DashboardController@index',  'dashboard.index');

    // Dashboard widget data (AJAX endpoints for charts/KPI cards)
    $router->get('/dashboard/widgets/sales',     'DashboardController@widgetSales',     'dashboard.widget.sales');
    $router->get('/dashboard/widgets/inventory', 'DashboardController@widgetInventory', 'dashboard.widget.inventory');
    $router->get('/dashboard/widgets/finance',   'DashboardController@widgetFinance',   'dashboard.widget.finance');
    $router->get('/dashboard/widgets/hr',        'DashboardController@widgetHr',        'dashboard.widget.hr');

    // ── User Management ───────────────────────────────────────────────────

    $router->group(['prefix' => '/users', 'middleware' => ['permission:users.access']], function () use ($router): void {
        $router->get('/',             'Users\UserController@index',   'users.index');
        $router->get('/create',       'Users\UserController@create',  'users.create',  ['permission:users.create']);
        $router->post('/',            'Users\UserController@store',   'users.store',   ['permission:users.create', 'csrf']);
        $router->get('/{id}',         'Users\UserController@show',    'users.show');
        $router->get('/{id}/edit',    'Users\UserController@edit',    'users.edit',    ['permission:users.edit']);
        $router->put('/{id}',         'Users\UserController@update',  'users.update',  ['permission:users.edit', 'csrf']);
        $router->delete('/{id}',      'Users\UserController@destroy', 'users.destroy', ['permission:users.delete', 'csrf']);
        $router->post('/{id}/restore','Users\UserController@restore', 'users.restore', ['permission:users.edit', 'csrf']);
        $router->post('/{id}/toggle-status', 'Users\UserController@toggleStatus', 'users.toggle-status', ['permission:users.edit', 'csrf']);
        $router->post('/{id}/reset-password','Users\UserController@resetPassword', 'users.reset-password', ['permission:users.edit', 'csrf']);
    });

    // ── My Profile (current authenticated user) ───────────────────────────

    $router->get('/profile',         'Auth\ProfileController@show',           'profile.show');
    $router->get('/profile/edit',    'Auth\ProfileController@edit',           'profile.edit');
    $router->put('/profile',         'Auth\ProfileController@update',         'profile.update',          ['csrf']);
    $router->put('/profile/password','Auth\ProfileController@updatePassword', 'profile.update.password', ['csrf']);
    $router->post('/profile/avatar', 'Auth\ProfileController@updateAvatar',   'profile.update.avatar',   ['csrf']);

    // ── Roles & Permissions ───────────────────────────────────────────────

    $router->group(['prefix' => '/roles', 'middleware' => ['permission:roles.access']], function () use ($router): void {
        $router->get('/',           'Users\RoleController@index',   'roles.index');
        $router->get('/create',     'Users\RoleController@create',  'roles.create',  ['permission:roles.create']);
        $router->post('/',          'Users\RoleController@store',   'roles.store',   ['permission:roles.create', 'csrf']);
        $router->get('/{id}',       'Users\RoleController@show',    'roles.show');
        $router->get('/{id}/edit',  'Users\RoleController@edit',    'roles.edit',    ['permission:roles.edit']);
        $router->put('/{id}',       'Users\RoleController@update',  'roles.update',  ['permission:roles.edit', 'csrf']);
        $router->delete('/{id}',    'Users\RoleController@destroy', 'roles.destroy', ['permission:roles.delete', 'csrf']);
        $router->post('/{id}/assign', 'Users\RoleController@assignPermissions', 'roles.assign', ['permission:roles.edit', 'csrf']);
    });

    $router->group(['prefix' => '/permissions', 'middleware' => ['permission:permissions.access']], function () use ($router): void {
        $router->get('/',       'Users\PermissionController@index',  'permissions.index');
        $router->get('/{id}',   'Users\PermissionController@show',   'permissions.show');
    });

    // ── Branch Management ─────────────────────────────────────────────────

    $router->group(['prefix' => '/branches', 'middleware' => ['permission:branches.access']], function () use ($router): void {
        $router->get('/',            'Branches\BranchController@index',   'branches.index');
        $router->get('/create',      'Branches\BranchController@create',  'branches.create',  ['permission:branches.create']);
        $router->post('/',           'Branches\BranchController@store',   'branches.store',   ['permission:branches.create', 'csrf']);
        $router->get('/{id}',        'Branches\BranchController@show',    'branches.show');
        $router->get('/{id}/edit',   'Branches\BranchController@edit',    'branches.edit',    ['permission:branches.edit']);
        $router->put('/{id}',        'Branches\BranchController@update',  'branches.update',  ['permission:branches.edit', 'csrf']);
        $router->delete('/{id}',     'Branches\BranchController@destroy', 'branches.destroy', ['permission:branches.delete', 'csrf']);
        $router->post('/switch/{id}','Branches\BranchController@switchBranch', 'branches.switch', ['csrf']);
    });

    // ── HR: Departments ───────────────────────────────────────────────────

    $router->group(['prefix' => '/hr/departments', 'middleware' => ['module:hr', 'permission:hr.departments.access']], function () use ($router): void {
        $router->get('/',            'HR\DepartmentController@index',   'hr.departments.index');
        $router->get('/create',      'HR\DepartmentController@create',  'hr.departments.create',  ['permission:hr.departments.create']);
        $router->post('/',           'HR\DepartmentController@store',   'hr.departments.store',   ['permission:hr.departments.create', 'csrf']);
        $router->get('/{id}',        'HR\DepartmentController@show',    'hr.departments.show');
        $router->get('/{id}/edit',   'HR\DepartmentController@edit',    'hr.departments.edit',    ['permission:hr.departments.edit']);
        $router->put('/{id}',        'HR\DepartmentController@update',  'hr.departments.update',  ['permission:hr.departments.edit', 'csrf']);
        $router->delete('/{id}',     'HR\DepartmentController@destroy', 'hr.departments.destroy', ['permission:hr.departments.delete', 'csrf']);
    });

    // ── HR: Designations ──────────────────────────────────────────────────

    $router->group(['prefix' => '/hr/designations', 'middleware' => ['module:hr', 'permission:hr.designations.access']], function () use ($router): void {
        $router->get('/',            'HR\DesignationController@index',   'hr.designations.index');
        $router->get('/create',      'HR\DesignationController@create',  'hr.designations.create',  ['permission:hr.designations.create']);
        $router->post('/',           'HR\DesignationController@store',   'hr.designations.store',   ['permission:hr.designations.create', 'csrf']);
        $router->get('/{id}',        'HR\DesignationController@show',    'hr.designations.show');
        $router->get('/{id}/edit',   'HR\DesignationController@edit',    'hr.designations.edit',    ['permission:hr.designations.edit']);
        $router->put('/{id}',        'HR\DesignationController@update',  'hr.designations.update',  ['permission:hr.designations.edit', 'csrf']);
        $router->delete('/{id}',     'HR\DesignationController@destroy', 'hr.designations.destroy', ['permission:hr.designations.delete', 'csrf']);
    });

    // ── HR: Employees ─────────────────────────────────────────────────────

    $router->group(['prefix' => '/hr/employees', 'middleware' => ['module:hr', 'permission:hr.employees.access']], function () use ($router): void {
        $router->get('/',                        'HR\EmployeeController@index',           'hr.employees.index');
        $router->get('/create',                  'HR\EmployeeController@create',          'hr.employees.create',          ['permission:hr.employees.create']);
        $router->post('/',                       'HR\EmployeeController@store',           'hr.employees.store',           ['permission:hr.employees.create', 'csrf']);
        $router->get('/{id}',                    'HR\EmployeeController@show',            'hr.employees.show');
        $router->get('/{id}/edit',               'HR\EmployeeController@edit',            'hr.employees.edit',            ['permission:hr.employees.edit']);
        $router->put('/{id}',                    'HR\EmployeeController@update',          'hr.employees.update',          ['permission:hr.employees.edit', 'csrf']);
        $router->delete('/{id}',                 'HR\EmployeeController@destroy',         'hr.employees.destroy',         ['permission:hr.employees.delete', 'csrf']);

        // Employee Documents
        $router->get('/{id}/documents',          'HR\EmployeeDocumentController@index',   'hr.employees.documents.index');
        $router->post('/{id}/documents',         'HR\EmployeeDocumentController@store',   'hr.employees.documents.store',  ['csrf']);
        $router->delete('/{id}/documents/{docId}','HR\EmployeeDocumentController@destroy','hr.employees.documents.destroy',['permission:hr.employees.edit', 'csrf']);

        // Employee Transfers
        $router->get('/{id}/transfers',          'HR\EmployeeTransferController@index',   'hr.employees.transfers.index');
        $router->post('/{id}/transfer',          'HR\EmployeeTransferController@store',   'hr.employees.transfers.store',  ['permission:hr.employees.edit', 'csrf']);

        // Employee Timeline / Activity
        $router->get('/{id}/timeline',           'HR\EmployeeController@timeline',        'hr.employees.timeline');
    });

    // ── Attendance ────────────────────────────────────────────────────────

    $router->group(['prefix' => '/attendance', 'middleware' => ['module:hr', 'permission:attendance.access']], function () use ($router): void {
        $router->get('/',                 'HR\AttendanceController@index',       'attendance.index');
        $router->get('/my',               'HR\AttendanceController@myAttendance','attendance.my');

        // Manual check-in / check-out
        $router->post('/check-in',        'HR\AttendanceController@checkIn',     'attendance.check-in',   ['csrf']);
        $router->post('/check-out',       'HR\AttendanceController@checkOut',    'attendance.check-out',  ['csrf']);

        // Admin: create / edit attendance records
        $router->get('/create',           'HR\AttendanceController@create',      'attendance.create',   ['permission:attendance.create']);
        $router->post('/',                'HR\AttendanceController@store',       'attendance.store',    ['permission:attendance.create', 'csrf']);
        $router->get('/{id}/edit',        'HR\AttendanceController@edit',        'attendance.edit',     ['permission:attendance.edit']);
        $router->put('/{id}',             'HR\AttendanceController@update',      'attendance.update',   ['permission:attendance.edit', 'csrf']);
        $router->delete('/{id}',          'HR\AttendanceController@destroy',     'attendance.destroy',  ['permission:attendance.delete', 'csrf']);

        // Reports
        $router->get('/reports/monthly',  'HR\AttendanceReportController@monthly',   'attendance.reports.monthly');
        $router->get('/reports/summary',  'HR\AttendanceReportController@summary',   'attendance.reports.summary');
        $router->get('/reports/late',     'HR\AttendanceReportController@lateComers','attendance.reports.late');
        $router->get('/reports/absent',   'HR\AttendanceReportController@absentees', 'attendance.reports.absent');
        $router->post('/reports/export',  'HR\AttendanceReportController@export',    'attendance.reports.export', ['csrf']);
    });

    // ── Payroll ───────────────────────────────────────────────────────────

    $router->group(['prefix' => '/payroll', 'middleware' => ['module:payroll', 'permission:payroll.access']], function () use ($router): void {

        // Salary Structures
        $router->get('/salary-structures',          'Payroll\SalaryStructureController@index',  'payroll.salary-structures.index');
        $router->get('/salary-structures/create',   'Payroll\SalaryStructureController@create', 'payroll.salary-structures.create', ['permission:payroll.manage']);
        $router->post('/salary-structures',         'Payroll\SalaryStructureController@store',  'payroll.salary-structures.store',  ['permission:payroll.manage', 'csrf']);
        $router->get('/salary-structures/{id}',     'Payroll\SalaryStructureController@show',   'payroll.salary-structures.show');
        $router->get('/salary-structures/{id}/edit','Payroll\SalaryStructureController@edit',   'payroll.salary-structures.edit',   ['permission:payroll.manage']);
        $router->put('/salary-structures/{id}',     'Payroll\SalaryStructureController@update', 'payroll.salary-structures.update', ['permission:payroll.manage', 'csrf']);
        $router->delete('/salary-structures/{id}',  'Payroll\SalaryStructureController@destroy','payroll.salary-structures.destroy',['permission:payroll.manage', 'csrf']);

        // Allowance Components
        $router->get('/allowances',                 'Payroll\AllowanceController@index',  'payroll.allowances.index');
        $router->post('/allowances',                'Payroll\AllowanceController@store',  'payroll.allowances.store',  ['permission:payroll.manage', 'csrf']);
        $router->put('/allowances/{id}',            'Payroll\AllowanceController@update', 'payroll.allowances.update', ['permission:payroll.manage', 'csrf']);
        $router->delete('/allowances/{id}',         'Payroll\AllowanceController@destroy','payroll.allowances.destroy',['permission:payroll.manage', 'csrf']);

        // Deduction Components
        $router->get('/deductions',                 'Payroll\DeductionController@index',  'payroll.deductions.index');
        $router->post('/deductions',                'Payroll\DeductionController@store',  'payroll.deductions.store',  ['permission:payroll.manage', 'csrf']);
        $router->put('/deductions/{id}',            'Payroll\DeductionController@update', 'payroll.deductions.update', ['permission:payroll.manage', 'csrf']);
        $router->delete('/deductions/{id}',         'Payroll\DeductionController@destroy','payroll.deductions.destroy',['permission:payroll.manage', 'csrf']);

        // Payroll Processing (monthly payroll runs)
        $router->get('/process',                    'Payroll\PayrollProcessingController@index',    'payroll.process.index',   ['permission:payroll.process']);
        $router->post('/process/run',               'Payroll\PayrollProcessingController@run',      'payroll.process.run',     ['permission:payroll.process', 'csrf']);
        $router->post('/process/{id}/approve',      'Payroll\PayrollProcessingController@approve',  'payroll.process.approve', ['permission:payroll.approve', 'csrf']);
        $router->post('/process/{id}/reject',       'Payroll\PayrollProcessingController@reject',   'payroll.process.reject',  ['permission:payroll.approve', 'csrf']);
        $router->post('/process/{id}/disburse',     'Payroll\PayrollProcessingController@disburse', 'payroll.process.disburse',['permission:payroll.disburse', 'csrf']);

        // Payslips
        $router->get('/payslips',                   'Payroll\PayslipController@index',   'payroll.payslips.index');
        $router->get('/payslips/{id}',              'Payroll\PayslipController@show',    'payroll.payslips.show');
        $router->get('/payslips/{id}/pdf',          'Payroll\PayslipController@pdf',     'payroll.payslips.pdf');
        $router->post('/payslips/{id}/email',       'Payroll\PayslipController@email',   'payroll.payslips.email',  ['permission:payroll.manage', 'csrf']);

        // Payroll Reports
        $router->get('/reports/summary',            'Payroll\PayrollReportController@summary',     'payroll.reports.summary');
        $router->get('/reports/register',           'Payroll\PayrollReportController@register',    'payroll.reports.register');
        $router->get('/reports/tax',                'Payroll\PayrollReportController@taxReport',   'payroll.reports.tax');
        $router->post('/reports/export',            'Payroll\PayrollReportController@export',      'payroll.reports.export', ['csrf']);
    });

    // ── CRM: Customers ────────────────────────────────────────────────────

    $router->group(['prefix' => '/customers', 'middleware' => ['module:crm', 'permission:customers.access']], function () use ($router): void {
        $router->get('/',            'CRM\CustomerController@index',          'customers.index');
        $router->get('/create',      'CRM\CustomerController@create',         'customers.create',  ['permission:customers.create']);
        $router->post('/',           'CRM\CustomerController@store',          'customers.store',   ['permission:customers.create', 'csrf']);
        $router->get('/{id}',        'CRM\CustomerController@show',           'customers.show');
        $router->get('/{id}/edit',   'CRM\CustomerController@edit',           'customers.edit',    ['permission:customers.edit']);
        $router->put('/{id}',        'CRM\CustomerController@update',         'customers.update',  ['permission:customers.edit', 'csrf']);
        $router->delete('/{id}',     'CRM\CustomerController@destroy',        'customers.destroy', ['permission:customers.delete', 'csrf']);
        $router->get('/{id}/ledger', 'CRM\CustomerController@ledger',         'customers.ledger');
        $router->get('/{id}/orders', 'CRM\CustomerController@orders',         'customers.orders');
        $router->get('/{id}/credit', 'CRM\CustomerController@creditHistory',  'customers.credit');
    });

    // ── Suppliers ─────────────────────────────────────────────────────────

    $router->group(['prefix' => '/suppliers', 'middleware' => ['permission:suppliers.access']], function () use ($router): void {
        $router->get('/',            'Purchasing\SupplierController@index',   'suppliers.index');
        $router->get('/create',      'Purchasing\SupplierController@create',  'suppliers.create',  ['permission:suppliers.create']);
        $router->post('/',           'Purchasing\SupplierController@store',   'suppliers.store',   ['permission:suppliers.create', 'csrf']);
        $router->get('/{id}',        'Purchasing\SupplierController@show',    'suppliers.show');
        $router->get('/{id}/edit',   'Purchasing\SupplierController@edit',    'suppliers.edit',    ['permission:suppliers.edit']);
        $router->put('/{id}',        'Purchasing\SupplierController@update',  'suppliers.update',  ['permission:suppliers.edit', 'csrf']);
        $router->delete('/{id}',     'Purchasing\SupplierController@destroy', 'suppliers.destroy', ['permission:suppliers.delete', 'csrf']);
        $router->get('/{id}/ledger', 'Purchasing\SupplierController@ledger',  'suppliers.ledger');
        $router->get('/{id}/orders', 'Purchasing\SupplierController@orders',  'suppliers.orders');
    });

    // ── Products: Categories ──────────────────────────────────────────────

    $router->group(['prefix' => '/products/categories', 'middleware' => ['module:inventory', 'permission:products.access']], function () use ($router): void {
        $router->get('/',            'Inventory\CategoryController@index',   'products.categories.index');
        $router->get('/create',      'Inventory\CategoryController@create',  'products.categories.create',  ['permission:products.create']);
        $router->post('/',           'Inventory\CategoryController@store',   'products.categories.store',   ['permission:products.create', 'csrf']);
        $router->get('/{id}/edit',   'Inventory\CategoryController@edit',    'products.categories.edit',    ['permission:products.edit']);
        $router->put('/{id}',        'Inventory\CategoryController@update',  'products.categories.update',  ['permission:products.edit', 'csrf']);
        $router->delete('/{id}',     'Inventory\CategoryController@destroy', 'products.categories.destroy', ['permission:products.delete', 'csrf']);
    });

    // ── Products: Brands ──────────────────────────────────────────────────

    $router->group(['prefix' => '/products/brands', 'middleware' => ['module:inventory', 'permission:products.access']], function () use ($router): void {
        $router->get('/',            'Inventory\BrandController@index',   'products.brands.index');
        $router->get('/create',      'Inventory\BrandController@create',  'products.brands.create',  ['permission:products.create']);
        $router->post('/',           'Inventory\BrandController@store',   'products.brands.store',   ['permission:products.create', 'csrf']);
        $router->get('/{id}/edit',   'Inventory\BrandController@edit',    'products.brands.edit',    ['permission:products.edit']);
        $router->put('/{id}',        'Inventory\BrandController@update',  'products.brands.update',  ['permission:products.edit', 'csrf']);
        $router->delete('/{id}',     'Inventory\BrandController@destroy', 'products.brands.destroy', ['permission:products.delete', 'csrf']);
    });

    // ── Products: Units of Measure ────────────────────────────────────────

    $router->group(['prefix' => '/products/units', 'middleware' => ['module:inventory', 'permission:products.access']], function () use ($router): void {
        $router->get('/',            'Inventory\UnitController@index',   'products.units.index');
        $router->get('/create',      'Inventory\UnitController@create',  'products.units.create',  ['permission:products.create']);
        $router->post('/',           'Inventory\UnitController@store',   'products.units.store',   ['permission:products.create', 'csrf']);
        $router->get('/{id}/edit',   'Inventory\UnitController@edit',    'products.units.edit',    ['permission:products.edit']);
        $router->put('/{id}',        'Inventory\UnitController@update',  'products.units.update',  ['permission:products.edit', 'csrf']);
        $router->delete('/{id}',     'Inventory\UnitController@destroy', 'products.units.destroy', ['permission:products.delete', 'csrf']);
    });

    // ── Products ──────────────────────────────────────────────────────────

    $router->group(['prefix' => '/products', 'middleware' => ['module:inventory', 'permission:products.access']], function () use ($router): void {
        $router->get('/',               'Inventory\ProductController@index',        'products.index');
        $router->get('/create',         'Inventory\ProductController@create',       'products.create',  ['permission:products.create']);
        $router->post('/',              'Inventory\ProductController@store',        'products.store',   ['permission:products.create', 'csrf']);
        $router->get('/{id}',           'Inventory\ProductController@show',         'products.show');
        $router->get('/{id}/edit',      'Inventory\ProductController@edit',         'products.edit',    ['permission:products.edit']);
        $router->put('/{id}',           'Inventory\ProductController@update',       'products.update',  ['permission:products.edit', 'csrf']);
        $router->delete('/{id}',        'Inventory\ProductController@destroy',      'products.destroy', ['permission:products.delete', 'csrf']);

        // Product Variants
        $router->get('/{id}/variants',          'Inventory\ProductVariantController@index',   'products.variants.index');
        $router->post('/{id}/variants',         'Inventory\ProductVariantController@store',   'products.variants.store',  ['permission:products.edit', 'csrf']);
        $router->put('/{id}/variants/{varId}',  'Inventory\ProductVariantController@update',  'products.variants.update', ['permission:products.edit', 'csrf']);
        $router->delete('/{id}/variants/{varId}','Inventory\ProductVariantController@destroy','products.variants.destroy',['permission:products.edit', 'csrf']);

        // Product Images
        $router->post('/{id}/images',           'Inventory\ProductImageController@store',   'products.images.store',  ['permission:products.edit', 'csrf']);
        $router->delete('/{id}/images/{imgId}', 'Inventory\ProductImageController@destroy', 'products.images.destroy',['permission:products.edit', 'csrf']);

        // Stock levels (read-only view per product)
        $router->get('/{id}/stock',  'Inventory\ProductController@stockLevels', 'products.stock');
    });

    // ── Inventory: Warehouses ─────────────────────────────────────────────

    $router->group(['prefix' => '/inventory/warehouses', 'middleware' => ['module:inventory', 'permission:inventory.access']], function () use ($router): void {
        $router->get('/',            'Inventory\WarehouseController@index',   'inventory.warehouses.index');
        $router->get('/create',      'Inventory\WarehouseController@create',  'inventory.warehouses.create',  ['permission:inventory.manage']);
        $router->post('/',           'Inventory\WarehouseController@store',   'inventory.warehouses.store',   ['permission:inventory.manage', 'csrf']);
        $router->get('/{id}',        'Inventory\WarehouseController@show',    'inventory.warehouses.show');
        $router->get('/{id}/edit',   'Inventory\WarehouseController@edit',    'inventory.warehouses.edit',    ['permission:inventory.manage']);
        $router->put('/{id}',        'Inventory\WarehouseController@update',  'inventory.warehouses.update',  ['permission:inventory.manage', 'csrf']);
        $router->delete('/{id}',     'Inventory\WarehouseController@destroy', 'inventory.warehouses.destroy', ['permission:inventory.manage', 'csrf']);
        $router->get('/{id}/stock',  'Inventory\WarehouseController@stock',   'inventory.warehouses.stock');
    });

    // ── Inventory: Stock In ───────────────────────────────────────────────

    $router->group(['prefix' => '/inventory/stock-in', 'middleware' => ['module:inventory', 'permission:inventory.access']], function () use ($router): void {
        $router->get('/',            'Inventory\StockInController@index',  'inventory.stock-in.index');
        $router->get('/create',      'Inventory\StockInController@create', 'inventory.stock-in.create', ['permission:inventory.stock-in']);
        $router->post('/',           'Inventory\StockInController@store',  'inventory.stock-in.store',  ['permission:inventory.stock-in', 'csrf']);
        $router->get('/{id}',        'Inventory\StockInController@show',   'inventory.stock-in.show');
        $router->get('/{id}/pdf',    'Inventory\StockInController@pdf',    'inventory.stock-in.pdf');
    });

    // ── Inventory: Stock Out ──────────────────────────────────────────────

    $router->group(['prefix' => '/inventory/stock-out', 'middleware' => ['module:inventory', 'permission:inventory.access']], function () use ($router): void {
        $router->get('/',            'Inventory\StockOutController@index',  'inventory.stock-out.index');
        $router->get('/create',      'Inventory\StockOutController@create', 'inventory.stock-out.create', ['permission:inventory.stock-out']);
        $router->post('/',           'Inventory\StockOutController@store',  'inventory.stock-out.store',  ['permission:inventory.stock-out', 'csrf']);
        $router->get('/{id}',        'Inventory\StockOutController@show',   'inventory.stock-out.show');
    });

    // ── Inventory: Stock Transfers ────────────────────────────────────────

    $router->group(['prefix' => '/inventory/transfers', 'middleware' => ['module:inventory', 'permission:inventory.access']], function () use ($router): void {
        $router->get('/',                  'Inventory\StockTransferController@index',    'inventory.transfers.index');
        $router->get('/create',            'Inventory\StockTransferController@create',   'inventory.transfers.create',  ['permission:inventory.transfers']);
        $router->post('/',                 'Inventory\StockTransferController@store',    'inventory.transfers.store',   ['permission:inventory.transfers', 'csrf']);
        $router->get('/{id}',              'Inventory\StockTransferController@show',     'inventory.transfers.show');
        $router->post('/{id}/confirm',     'Inventory\StockTransferController@confirm',  'inventory.transfers.confirm', ['permission:inventory.transfers', 'csrf']);
        $router->post('/{id}/receive',     'Inventory\StockTransferController@receive',  'inventory.transfers.receive', ['permission:inventory.transfers', 'csrf']);
        $router->post('/{id}/cancel',      'Inventory\StockTransferController@cancel',   'inventory.transfers.cancel',  ['permission:inventory.transfers', 'csrf']);
    });

    // ── Inventory: Adjustments ────────────────────────────────────────────

    $router->group(['prefix' => '/inventory/adjustments', 'middleware' => ['module:inventory', 'permission:inventory.adjustments']], function () use ($router): void {
        $router->get('/',            'Inventory\StockAdjustmentController@index',   'inventory.adjustments.index');
        $router->get('/create',      'Inventory\StockAdjustmentController@create',  'inventory.adjustments.create');
        $router->post('/',           'Inventory\StockAdjustmentController@store',   'inventory.adjustments.store',  ['csrf']);
        $router->get('/{id}',        'Inventory\StockAdjustmentController@show',    'inventory.adjustments.show');
        $router->post('/{id}/approve','Inventory\StockAdjustmentController@approve','inventory.adjustments.approve',['permission:inventory.adjustments.approve', 'csrf']);
    });

    // ── Inventory: Reports ────────────────────────────────────────────────

    $router->group(['prefix' => '/inventory/reports', 'middleware' => ['module:inventory', 'permission:inventory.access']], function () use ($router): void {
        $router->get('/stock-ledger',    'Inventory\InventoryReportController@stockLedger',  'inventory.reports.stock-ledger');
        $router->get('/stock-summary',   'Inventory\InventoryReportController@stockSummary', 'inventory.reports.stock-summary');
        $router->get('/valuation',       'Inventory\InventoryReportController@valuation',    'inventory.reports.valuation');
        $router->get('/movement',        'Inventory\InventoryReportController@movement',     'inventory.reports.movement');
        $router->get('/low-stock',       'Inventory\InventoryReportController@lowStock',     'inventory.reports.low-stock');
        $router->get('/expiry',          'Inventory\InventoryReportController@expiry',       'inventory.reports.expiry');
        $router->post('/export',         'Inventory\InventoryReportController@export',       'inventory.reports.export', ['csrf']);
    });

    // ── Purchasing: Purchase Orders ───────────────────────────────────────

    $router->group(['prefix' => '/purchasing/orders', 'middleware' => ['module:purchasing', 'permission:purchasing.access']], function () use ($router): void {
        $router->get('/',                'Purchasing\PurchaseOrderController@index',   'purchasing.orders.index');
        $router->get('/create',          'Purchasing\PurchaseOrderController@create',  'purchasing.orders.create',  ['permission:purchasing.create']);
        $router->post('/',               'Purchasing\PurchaseOrderController@store',   'purchasing.orders.store',   ['permission:purchasing.create', 'csrf']);
        $router->get('/{id}',            'Purchasing\PurchaseOrderController@show',    'purchasing.orders.show');
        $router->get('/{id}/edit',       'Purchasing\PurchaseOrderController@edit',    'purchasing.orders.edit',    ['permission:purchasing.edit']);
        $router->put('/{id}',            'Purchasing\PurchaseOrderController@update',  'purchasing.orders.update',  ['permission:purchasing.edit', 'csrf']);
        $router->delete('/{id}',         'Purchasing\PurchaseOrderController@destroy', 'purchasing.orders.destroy', ['permission:purchasing.delete', 'csrf']);
        $router->post('/{id}/approve',   'Purchasing\PurchaseOrderController@approve', 'purchasing.orders.approve', ['permission:purchasing.approve', 'csrf']);
        $router->post('/{id}/cancel',    'Purchasing\PurchaseOrderController@cancel',  'purchasing.orders.cancel',  ['permission:purchasing.edit', 'csrf']);
        $router->get('/{id}/pdf',        'Purchasing\PurchaseOrderController@pdf',     'purchasing.orders.pdf');
        $router->post('/{id}/email',     'Purchasing\PurchaseOrderController@email',   'purchasing.orders.email',   ['csrf']);
    });

    // ── Purchasing: GRN (Goods Receipt Notes) ────────────────────────────

    $router->group(['prefix' => '/purchasing/grn', 'middleware' => ['module:purchasing', 'permission:purchasing.access']], function () use ($router): void {
        $router->get('/',            'Purchasing\GoodsReceiptController@index',   'purchasing.grn.index');
        $router->get('/create',      'Purchasing\GoodsReceiptController@create',  'purchasing.grn.create',  ['permission:purchasing.grn']);
        $router->post('/',           'Purchasing\GoodsReceiptController@store',   'purchasing.grn.store',   ['permission:purchasing.grn', 'csrf']);
        $router->get('/{id}',        'Purchasing\GoodsReceiptController@show',    'purchasing.grn.show');
        $router->get('/{id}/pdf',    'Purchasing\GoodsReceiptController@pdf',     'purchasing.grn.pdf');
    });

    // ── Purchasing: Bills / Supplier Invoices ─────────────────────────────

    $router->group(['prefix' => '/purchasing/invoices', 'middleware' => ['module:purchasing', 'permission:purchasing.access']], function () use ($router): void {
        $router->get('/',            'Purchasing\SupplierInvoiceController@index',   'purchasing.invoices.index');
        $router->get('/create',      'Purchasing\SupplierInvoiceController@create',  'purchasing.invoices.create',  ['permission:purchasing.invoices']);
        $router->post('/',           'Purchasing\SupplierInvoiceController@store',   'purchasing.invoices.store',   ['permission:purchasing.invoices', 'csrf']);
        $router->get('/{id}',        'Purchasing\SupplierInvoiceController@show',    'purchasing.invoices.show');
        $router->get('/{id}/edit',   'Purchasing\SupplierInvoiceController@edit',    'purchasing.invoices.edit',    ['permission:purchasing.invoices']);
        $router->put('/{id}',        'Purchasing\SupplierInvoiceController@update',  'purchasing.invoices.update',  ['permission:purchasing.invoices', 'csrf']);
        $router->post('/{id}/pay',   'Purchasing\SupplierInvoiceController@pay',     'purchasing.invoices.pay',     ['permission:purchasing.payments', 'csrf']);
    });

    // ── Purchasing: Returns ───────────────────────────────────────────────

    $router->group(['prefix' => '/purchasing/returns', 'middleware' => ['module:purchasing', 'permission:purchasing.returns']], function () use ($router): void {
        $router->get('/',            'Purchasing\PurchaseReturnController@index',  'purchasing.returns.index');
        $router->get('/create',      'Purchasing\PurchaseReturnController@create', 'purchasing.returns.create');
        $router->post('/',           'Purchasing\PurchaseReturnController@store',  'purchasing.returns.store',  ['csrf']);
        $router->get('/{id}',        'Purchasing\PurchaseReturnController@show',   'purchasing.returns.show');
        $router->post('/{id}/approve','Purchasing\PurchaseReturnController@approve','purchasing.returns.approve',['permission:purchasing.approve', 'csrf']);
    });

    // ── Purchasing: Reports ───────────────────────────────────────────────

    $router->group(['prefix' => '/purchasing/reports', 'middleware' => ['module:purchasing', 'permission:purchasing.access']], function () use ($router): void {
        $router->get('/summary',     'Purchasing\PurchaseReportController@summary',   'purchasing.reports.summary');
        $router->get('/by-supplier', 'Purchasing\PurchaseReportController@bySupplier','purchasing.reports.by-supplier');
        $router->get('/by-product',  'Purchasing\PurchaseReportController@byProduct', 'purchasing.reports.by-product');
        $router->get('/payments',    'Purchasing\PurchaseReportController@payments',  'purchasing.reports.payments');
        $router->post('/export',     'Purchasing\PurchaseReportController@export',    'purchasing.reports.export', ['csrf']);
    });

    // ── Sales: Quotations ─────────────────────────────────────────────────

    $router->group(['prefix' => '/sales/quotations', 'middleware' => ['module:sales', 'permission:sales.access']], function () use ($router): void {
        $router->get('/',                'Sales\QuotationController@index',   'sales.quotations.index');
        $router->get('/create',          'Sales\QuotationController@create',  'sales.quotations.create',  ['permission:sales.create']);
        $router->post('/',               'Sales\QuotationController@store',   'sales.quotations.store',   ['permission:sales.create', 'csrf']);
        $router->get('/{id}',            'Sales\QuotationController@show',    'sales.quotations.show');
        $router->get('/{id}/edit',       'Sales\QuotationController@edit',    'sales.quotations.edit',    ['permission:sales.edit']);
        $router->put('/{id}',            'Sales\QuotationController@update',  'sales.quotations.update',  ['permission:sales.edit', 'csrf']);
        $router->delete('/{id}',         'Sales\QuotationController@destroy', 'sales.quotations.destroy', ['permission:sales.delete', 'csrf']);
        $router->get('/{id}/pdf',        'Sales\QuotationController@pdf',     'sales.quotations.pdf');
        $router->post('/{id}/email',     'Sales\QuotationController@email',   'sales.quotations.email',   ['csrf']);
        $router->post('/{id}/convert',   'Sales\QuotationController@convert', 'sales.quotations.convert', ['permission:sales.create', 'csrf']);
    });

    // ── Sales: Orders ─────────────────────────────────────────────────────

    $router->group(['prefix' => '/sales/orders', 'middleware' => ['module:sales', 'permission:sales.access']], function () use ($router): void {
        $router->get('/',                'Sales\SalesOrderController@index',   'sales.orders.index');
        $router->get('/create',          'Sales\SalesOrderController@create',  'sales.orders.create',  ['permission:sales.create']);
        $router->post('/',               'Sales\SalesOrderController@store',   'sales.orders.store',   ['permission:sales.create', 'csrf']);
        $router->get('/{id}',            'Sales\SalesOrderController@show',    'sales.orders.show');
        $router->get('/{id}/edit',       'Sales\SalesOrderController@edit',    'sales.orders.edit',    ['permission:sales.edit']);
        $router->put('/{id}',            'Sales\SalesOrderController@update',  'sales.orders.update',  ['permission:sales.edit', 'csrf']);
        $router->delete('/{id}',         'Sales\SalesOrderController@destroy', 'sales.orders.destroy', ['permission:sales.delete', 'csrf']);
        $router->post('/{id}/approve',   'Sales\SalesOrderController@approve', 'sales.orders.approve', ['permission:sales.approve', 'csrf']);
        $router->post('/{id}/cancel',    'Sales\SalesOrderController@cancel',  'sales.orders.cancel',  ['permission:sales.edit', 'csrf']);
        $router->post('/{id}/invoice',   'Sales\SalesOrderController@createInvoice', 'sales.orders.invoice', ['permission:sales.invoices', 'csrf']);
    });

    // ── Sales: Invoices ───────────────────────────────────────────────────

    $router->group(['prefix' => '/sales/invoices', 'middleware' => ['module:sales', 'permission:sales.access']], function () use ($router): void {
        $router->get('/',                'Sales\InvoiceController@index',   'sales.invoices.index');
        $router->get('/create',          'Sales\InvoiceController@create',  'sales.invoices.create',  ['permission:sales.invoices']);
        $router->post('/',               'Sales\InvoiceController@store',   'sales.invoices.store',   ['permission:sales.invoices', 'csrf']);
        $router->get('/{id}',            'Sales\InvoiceController@show',    'sales.invoices.show');
        $router->get('/{id}/edit',       'Sales\InvoiceController@edit',    'sales.invoices.edit',    ['permission:sales.invoices']);
        $router->put('/{id}',            'Sales\InvoiceController@update',  'sales.invoices.update',  ['permission:sales.invoices', 'csrf']);
        $router->get('/{id}/pdf',        'Sales\InvoiceController@pdf',     'sales.invoices.pdf');
        $router->post('/{id}/email',     'Sales\InvoiceController@email',   'sales.invoices.email',   ['csrf']);
        $router->post('/{id}/void',      'Sales\InvoiceController@void',    'sales.invoices.void',    ['permission:sales.invoices.void', 'csrf']);
        $router->post('/{id}/payment',   'Sales\InvoiceController@recordPayment','sales.invoices.payment',['permission:sales.payments', 'csrf']);
    });

    // ── Sales: Returns ────────────────────────────────────────────────────

    $router->group(['prefix' => '/sales/returns', 'middleware' => ['module:sales', 'permission:sales.returns']], function () use ($router): void {
        $router->get('/',            'Sales\SalesReturnController@index',   'sales.returns.index');
        $router->get('/create',      'Sales\SalesReturnController@create',  'sales.returns.create');
        $router->post('/',           'Sales\SalesReturnController@store',   'sales.returns.store',  ['csrf']);
        $router->get('/{id}',        'Sales\SalesReturnController@show',    'sales.returns.show');
        $router->post('/{id}/approve','Sales\SalesReturnController@approve','sales.returns.approve',['permission:sales.approve', 'csrf']);
        $router->get('/{id}/pdf',    'Sales\SalesReturnController@pdf',     'sales.returns.pdf');
    });

    // ── Sales: Payments ───────────────────────────────────────────────────

    $router->group(['prefix' => '/sales/payments', 'middleware' => ['module:sales', 'permission:sales.payments']], function () use ($router): void {
        $router->get('/',            'Sales\PaymentController@index',   'sales.payments.index');
        $router->get('/create',      'Sales\PaymentController@create',  'sales.payments.create');
        $router->post('/',           'Sales\PaymentController@store',   'sales.payments.store',  ['csrf']);
        $router->get('/{id}',        'Sales\PaymentController@show',    'sales.payments.show');
        $router->get('/{id}/pdf',    'Sales\PaymentController@pdf',     'sales.payments.pdf');
        $router->post('/{id}/refund','Sales\PaymentController@refund',  'sales.payments.refund', ['permission:sales.payments.refund', 'csrf']);
    });

    // ── Sales: Reports ────────────────────────────────────────────────────

    $router->group(['prefix' => '/sales/reports', 'middleware' => ['module:sales', 'permission:sales.access']], function () use ($router): void {
        $router->get('/summary',       'Sales\SalesReportController@summary',       'sales.reports.summary');
        $router->get('/by-customer',   'Sales\SalesReportController@byCustomer',    'sales.reports.by-customer');
        $router->get('/by-product',    'Sales\SalesReportController@byProduct',     'sales.reports.by-product');
        $router->get('/by-salesman',   'Sales\SalesReportController@bySalesman',    'sales.reports.by-salesman');
        $router->get('/payments',      'Sales\SalesReportController@payments',      'sales.reports.payments');
        $router->get('/outstanding',   'Sales\SalesReportController@outstanding',   'sales.reports.outstanding');
        $router->get('/aging',         'Sales\SalesReportController@aging',         'sales.reports.aging');
        $router->post('/export',       'Sales\SalesReportController@export',        'sales.reports.export', ['csrf']);
    });

    // ── Expenses: Categories ──────────────────────────────────────────────

    $router->group(['prefix' => '/expenses/categories', 'middleware' => ['permission:expenses.access']], function () use ($router): void {
        $router->get('/',            'Expenses\ExpenseCategoryController@index',   'expenses.categories.index');
        $router->get('/create',      'Expenses\ExpenseCategoryController@create',  'expenses.categories.create',  ['permission:expenses.manage']);
        $router->post('/',           'Expenses\ExpenseCategoryController@store',   'expenses.categories.store',   ['permission:expenses.manage', 'csrf']);
        $router->get('/{id}/edit',   'Expenses\ExpenseCategoryController@edit',    'expenses.categories.edit',    ['permission:expenses.manage']);
        $router->put('/{id}',        'Expenses\ExpenseCategoryController@update',  'expenses.categories.update',  ['permission:expenses.manage', 'csrf']);
        $router->delete('/{id}',     'Expenses\ExpenseCategoryController@destroy', 'expenses.categories.destroy', ['permission:expenses.manage', 'csrf']);
    });

    // ── Expenses ──────────────────────────────────────────────────────────

    $router->group(['prefix' => '/expenses', 'middleware' => ['permission:expenses.access']], function () use ($router): void {
        $router->get('/',            'Expenses\ExpenseController@index',   'expenses.index');
        $router->get('/create',      'Expenses\ExpenseController@create',  'expenses.create',  ['permission:expenses.create']);
        $router->post('/',           'Expenses\ExpenseController@store',   'expenses.store',   ['permission:expenses.create', 'csrf']);
        $router->get('/{id}',        'Expenses\ExpenseController@show',    'expenses.show');
        $router->get('/{id}/edit',   'Expenses\ExpenseController@edit',    'expenses.edit',    ['permission:expenses.edit']);
        $router->put('/{id}',        'Expenses\ExpenseController@update',  'expenses.update',  ['permission:expenses.edit', 'csrf']);
        $router->delete('/{id}',     'Expenses\ExpenseController@destroy', 'expenses.destroy', ['permission:expenses.delete', 'csrf']);
        $router->post('/{id}/approve','Expenses\ExpenseController@approve','expenses.approve', ['permission:expenses.approve', 'csrf']);
        $router->get('/{id}/receipt','Expenses\ExpenseController@receipt', 'expenses.receipt');

        // Expense Reports
        $router->get('/reports/summary',    'Expenses\ExpenseReportController@summary',   'expenses.reports.summary');
        $router->get('/reports/by-category','Expenses\ExpenseReportController@byCategory','expenses.reports.by-category');
        $router->get('/reports/monthly',    'Expenses\ExpenseReportController@monthly',   'expenses.reports.monthly');
        $router->post('/reports/export',    'Expenses\ExpenseReportController@export',    'expenses.reports.export', ['csrf']);
    });

    // ── Accounting: Chart of Accounts ─────────────────────────────────────

    $router->group(['prefix' => '/accounting', 'middleware' => ['module:accounting', 'permission:accounting.access']], function () use ($router): void {

        // Chart of Accounts
        $router->get('/accounts',            'Accounting\ChartOfAccountsController@index',   'accounting.coa.index');
        $router->get('/accounts/create',     'Accounting\ChartOfAccountsController@create',  'accounting.coa.create',  ['permission:accounting.manage']);
        $router->post('/accounts',           'Accounting\ChartOfAccountsController@store',   'accounting.coa.store',   ['permission:accounting.manage', 'csrf']);
        $router->get('/accounts/{id}',       'Accounting\ChartOfAccountsController@show',    'accounting.coa.show');
        $router->get('/accounts/{id}/edit',  'Accounting\ChartOfAccountsController@edit',    'accounting.coa.edit',    ['permission:accounting.manage']);
        $router->put('/accounts/{id}',       'Accounting\ChartOfAccountsController@update',  'accounting.coa.update',  ['permission:accounting.manage', 'csrf']);
        $router->delete('/accounts/{id}',    'Accounting\ChartOfAccountsController@destroy', 'accounting.coa.destroy', ['permission:accounting.manage', 'csrf']);

        // Journal Entries
        $router->get('/journals',            'Accounting\JournalController@index',   'accounting.journals.index');
        $router->get('/journals/create',     'Accounting\JournalController@create',  'accounting.journals.create', ['permission:accounting.journals']);
        $router->post('/journals',           'Accounting\JournalController@store',   'accounting.journals.store',  ['permission:accounting.journals', 'csrf']);
        $router->get('/journals/{id}',       'Accounting\JournalController@show',    'accounting.journals.show');
        $router->get('/journals/{id}/edit',  'Accounting\JournalController@edit',    'accounting.journals.edit',   ['permission:accounting.journals']);
        $router->put('/journals/{id}',       'Accounting\JournalController@update',  'accounting.journals.update', ['permission:accounting.journals', 'csrf']);
        $router->post('/journals/{id}/post', 'Accounting\JournalController@post',    'accounting.journals.post',   ['permission:accounting.journals.post', 'csrf']);
        $router->post('/journals/{id}/void', 'Accounting\JournalController@void',    'accounting.journals.void',   ['permission:accounting.journals.void', 'csrf']);
        $router->get('/journals/{id}/pdf',   'Accounting\JournalController@pdf',     'accounting.journals.pdf');

        // General Ledger
        $router->get('/ledger',              'Accounting\LedgerController@index',    'accounting.ledger.index');
        $router->get('/ledger/{accountId}',  'Accounting\LedgerController@show',     'accounting.ledger.show');
        $router->post('/ledger/export',      'Accounting\LedgerController@export',   'accounting.ledger.export', ['csrf']);

        // Trial Balance
        $router->get('/trial-balance',       'Accounting\TrialBalanceController@index',  'accounting.trial-balance');
        $router->post('/trial-balance/export','Accounting\TrialBalanceController@export','accounting.trial-balance.export', ['csrf']);

        // Financial Statements
        $router->get('/income-statement',    'Accounting\FinancialStatementController@incomeStatement',  'accounting.income-statement');
        $router->get('/balance-sheet',       'Accounting\FinancialStatementController@balanceSheet',     'accounting.balance-sheet');
        $router->get('/cash-flow',           'Accounting\FinancialStatementController@cashFlow',         'accounting.cash-flow');
        $router->get('/retained-earnings',   'Accounting\FinancialStatementController@retainedEarnings', 'accounting.retained-earnings');
        $router->post('/statements/export',  'Accounting\FinancialStatementController@export',           'accounting.statements.export', ['csrf']);

        // Bank Reconciliation
        $router->get('/reconciliation',      'Accounting\ReconciliationController@index',  'accounting.reconciliation.index');
        $router->post('/reconciliation',     'Accounting\ReconciliationController@store',  'accounting.reconciliation.store', ['csrf']);
        $router->get('/reconciliation/{id}', 'Accounting\ReconciliationController@show',   'accounting.reconciliation.show');

        // Cost Centers
        $router->get('/cost-centers',        'Accounting\CostCenterController@index',  'accounting.cost-centers.index');
        $router->post('/cost-centers',       'Accounting\CostCenterController@store',  'accounting.cost-centers.store',  ['permission:accounting.manage', 'csrf']);
        $router->put('/cost-centers/{id}',   'Accounting\CostCenterController@update', 'accounting.cost-centers.update', ['permission:accounting.manage', 'csrf']);
        $router->delete('/cost-centers/{id}','Accounting\CostCenterController@destroy','accounting.cost-centers.destroy',['permission:accounting.manage', 'csrf']);
    });

    // ── Reports (Consolidated) ────────────────────────────────────────────

    $router->group(['prefix' => '/reports', 'middleware' => ['permission:reports.access']], function () use ($router): void {
        $router->get('/',                      'Reports\ReportController@index',           'reports.index');
        $router->get('/sales',                 'Reports\ReportController@sales',           'reports.sales');
        $router->get('/purchases',             'Reports\ReportController@purchases',       'reports.purchases');
        $router->get('/inventory',             'Reports\ReportController@inventory',       'reports.inventory');
        $router->get('/financial',             'Reports\ReportController@financial',       'reports.financial');
        $router->get('/hr',                    'Reports\ReportController@hr',              'reports.hr');
        $router->get('/vat-mushak',            'Reports\VatReportController@mushak',       'reports.vat.mushak');
        $router->get('/vat-return',            'Reports\VatReportController@vatReturn',    'reports.vat.return');
        $router->get('/customer-aging',        'Reports\ReportController@customerAging',  'reports.customer-aging');
        $router->get('/supplier-aging',        'Reports\ReportController@supplierAging',  'reports.supplier-aging');
        $router->get('/profit-loss',           'Reports\ReportController@profitLoss',     'reports.profit-loss');
        $router->get('/branch-comparison',     'Reports\ReportController@branchComparison','reports.branch-comparison');
        $router->post('/export',               'Reports\ReportController@export',          'reports.export', ['csrf']);
    });

    // ── Settings ──────────────────────────────────────────────────────────

    $router->group(['prefix' => '/settings', 'middleware' => ['permission:settings.access']], function () use ($router): void {
        $router->get('/',                       'Settings\SettingsController@index',         'settings.index');

        // General Settings
        $router->get('/general',                'Settings\SettingsController@general',       'settings.general');
        $router->put('/general',                'Settings\SettingsController@updateGeneral', 'settings.general.update', ['permission:settings.manage', 'csrf']);

        // Company Settings
        $router->get('/company',                'Settings\CompanyController@edit',           'settings.company');
        $router->put('/company',                'Settings\CompanyController@update',         'settings.company.update', ['permission:settings.manage', 'csrf']);
        $router->post('/company/logo',          'Settings\CompanyController@uploadLogo',     'settings.company.logo',   ['permission:settings.manage', 'csrf']);

        // Tax & VAT Settings
        $router->get('/tax',                    'Settings\TaxController@index',              'settings.tax.index');
        $router->post('/tax',                   'Settings\TaxController@store',              'settings.tax.store',   ['permission:settings.manage', 'csrf']);
        $router->put('/tax/{id}',               'Settings\TaxController@update',             'settings.tax.update',  ['permission:settings.manage', 'csrf']);
        $router->delete('/tax/{id}',            'Settings\TaxController@destroy',            'settings.tax.destroy', ['permission:settings.manage', 'csrf']);

        // Currency Settings
        $router->get('/currencies',             'Settings\CurrencyController@index',         'settings.currencies.index');
        $router->post('/currencies',            'Settings\CurrencyController@store',         'settings.currencies.store',  ['permission:settings.manage', 'csrf']);
        $router->put('/currencies/{id}',        'Settings\CurrencyController@update',        'settings.currencies.update', ['permission:settings.manage', 'csrf']);

        // Email / SMTP Settings
        $router->get('/email',                  'Settings\EmailController@edit',             'settings.email');
        $router->put('/email',                  'Settings\EmailController@update',           'settings.email.update', ['permission:settings.manage', 'csrf']);
        $router->post('/email/test',            'Settings\EmailController@sendTest',         'settings.email.test',   ['permission:settings.manage', 'csrf']);

        // Number Series (Document Numbers)
        $router->get('/numbering',              'Settings\NumberingController@index',        'settings.numbering.index');
        $router->put('/numbering',              'Settings\NumberingController@update',       'settings.numbering.update', ['permission:settings.manage', 'csrf']);

        // Payment Methods
        $router->get('/payment-methods',        'Settings\PaymentMethodController@index',    'settings.payment-methods.index');
        $router->post('/payment-methods',       'Settings\PaymentMethodController@store',    'settings.payment-methods.store',  ['permission:settings.manage', 'csrf']);
        $router->put('/payment-methods/{id}',   'Settings\PaymentMethodController@update',   'settings.payment-methods.update', ['permission:settings.manage', 'csrf']);
        $router->delete('/payment-methods/{id}','Settings\PaymentMethodController@destroy',  'settings.payment-methods.destroy',['permission:settings.manage', 'csrf']);

        // Backup & Restore
        $router->get('/backup',                 'Settings\BackupController@index',           'settings.backup.index');
        $router->post('/backup/create',         'Settings\BackupController@create',          'settings.backup.create',  ['permission:settings.backup', 'csrf']);
        $router->post('/backup/{id}/restore',   'Settings\BackupController@restore',         'settings.backup.restore', ['permission:settings.backup', 'csrf']);
        $router->delete('/backup/{id}',         'Settings\BackupController@destroy',         'settings.backup.destroy', ['permission:settings.backup', 'csrf']);

        // Audit Logs
        $router->get('/audit-logs',             'Settings\AuditLogController@index',         'settings.audit-logs.index');
        $router->get('/audit-logs/{id}',        'Settings\AuditLogController@show',          'settings.audit-logs.show');
        $router->post('/audit-logs/export',     'Settings\AuditLogController@export',        'settings.audit-logs.export', ['csrf']);

        // Module Management
        $router->get('/modules',                'Settings\ModuleController@index',           'settings.modules.index');
        $router->post('/modules/{module}/toggle','Settings\ModuleController@toggle',         'settings.modules.toggle',   ['permission:settings.manage', 'csrf']);
    });

    // ── Notifications ─────────────────────────────────────────────────────

    $router->group(['prefix' => '/notifications'], function () use ($router): void {
        $router->get('/',                          'NotificationController@index',        'notifications.index');
        $router->get('/unread',                    'NotificationController@unread',       'notifications.unread');
        $router->post('/{id}/mark-read',           'NotificationController@markRead',     'notifications.mark-read',    ['csrf']);
        $router->post('/mark-all-read',            'NotificationController@markAllRead',  'notifications.mark-all-read',['csrf']);
        $router->delete('/{id}',                   'NotificationController@destroy',      'notifications.destroy',      ['csrf']);
        $router->delete('/clear-all',              'NotificationController@clearAll',     'notifications.clear-all',    ['csrf']);
        $router->get('/preferences',               'NotificationController@preferences',  'notifications.preferences');
        $router->put('/preferences',               'NotificationController@updatePreferences','notifications.preferences.update',['csrf']);
    });

}); // end authenticated group
