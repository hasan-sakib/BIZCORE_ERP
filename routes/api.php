<?php

declare(strict_types=1);

/**
 * BizCore ERP - REST API Routes  (v1)
 *
 * All routes are mounted under /api/v1/ and use the 'api' guard (JWT).
 *
 * Conventions:
 *  - Every response is JSON: { "success": bool, "data": ..., "message": string }
 *  - Paginated list responses include a "meta" pagination envelope
 *  - Authenticated routes require "Authorization: Bearer <access_token>"
 *  - Rate limiting: 60 req/min per IP by default (overridden per route group)
 *  - Versioning: /api/v1/ — add /api/v2/ groups when breaking changes are needed
 *
 * Middleware:
 *  'api.auth'       => validates JWT, binds user to request context
 *  'api.guest'      => rejects authenticated users (login/refresh endpoints)
 *  'api.throttle'   => e.g. 'api.throttle:60,1'  (60 req per 1 min)
 *  'api.permission' => RBAC permission check     e.g. 'api.permission:products.create'
 *  'api.module'     => ERP module enabled check  e.g. 'api.module:inventory'
 *  'api.json'       => enforces Content-Type: application/json on writes
 *
 * @var \App\Core\Router $router
 *
 * @package BizCore\ERP
 * @version 1.0.0
 */

// ============================================================================
// API v1 — ALL ROUTES
// ============================================================================

$router->group([
    'prefix'     => '/api/v1',
    'middleware' => ['api.throttle:60,1', 'api.json'],
    'namespace'  => 'App\\Http\\Controllers\\Api\\V1',
], function () use ($router): void {

    // ── Health check (no auth required) ───────────────────────────────────

    $router->get('/health', 'HealthController@check', 'api.v1.health');
    $router->get('/version','HealthController@version','api.v1.version');

    // =========================================================================
    // AUTHENTICATION ENDPOINTS
    // =========================================================================

    $router->group([
        'prefix'     => '/auth',
        'middleware' => ['api.throttle:10,1'],
    ], function () use ($router): void {

        /**
         * POST /api/v1/auth/login
         * Authenticate with email + password; receive access + refresh tokens.
         *
         * Body: { "email": string, "password": string, "device_id"?: string }
         *
         * Response 200:
         * {
         *   "success": true,
         *   "data": {
         *     "token_type":    "Bearer",
         *     "access_token":  "eyJ...",
         *     "refresh_token": "eyJ...",
         *     "expires_in":    3600,
         *     "user": { "id", "name", "email", "role", "branch_id", "permissions": [] }
         *   }
         * }
         */
        $router->post('/login',    'AuthController@login',    'api.v1.auth.login',   ['api.guest']);

        /**
         * POST /api/v1/auth/logout
         * Revoke the current access token (added to Redis blacklist).
         *
         * Header: Authorization: Bearer <access_token>
         * Response 200: { "success": true, "message": "Logged out successfully." }
         */
        $router->post('/logout',   'AuthController@logout',   'api.v1.auth.logout',  ['api.auth']);

        /**
         * POST /api/v1/auth/refresh
         * Exchange a valid refresh token for a new access + refresh token pair.
         *
         * Body: { "refresh_token": "eyJ..." }
         * Response 200: same shape as /auth/login response.
         */
        $router->post('/refresh',  'AuthController@refresh',  'api.v1.auth.refresh', ['api.throttle:5,1']);

        /**
         * POST /api/v1/auth/forgot-password
         * Trigger a password reset email.
         *
         * Body: { "email": string }
         * Response 200: { "success": true, "message": "Reset link sent." }
         */
        $router->post('/forgot-password', 'AuthController@forgotPassword', 'api.v1.auth.forgot-password');

        /**
         * POST /api/v1/auth/reset-password
         * Set a new password using a token received by email.
         *
         * Body: { "token": string, "email": string, "password": string, "password_confirmation": string }
         * Response 200: { "success": true, "message": "Password reset successfully." }
         */
        $router->post('/reset-password',  'AuthController@resetPassword',  'api.v1.auth.reset-password');

        /**
         * GET /api/v1/auth/me
         * Return the authenticated user's profile and permissions.
         *
         * Response 200: { "success": true, "data": { user object } }
         */
        $router->get('/me',        'AuthController@me',        'api.v1.auth.me',     ['api.auth']);

        /**
         * PUT /api/v1/auth/me
         * Update the authenticated user's own profile (name, locale, avatar).
         */
        $router->put('/me',        'AuthController@updateMe',  'api.v1.auth.me.update', ['api.auth']);

        /**
         * PUT /api/v1/auth/me/password
         * Change the current user's password.
         *
         * Body: { "current_password": string, "password": string, "password_confirmation": string }
         */
        $router->put('/me/password','AuthController@changePassword','api.v1.auth.me.password',['api.auth']);
    });

    // =========================================================================
    // AUTHENTICATED API ROUTES
    // All routes below require a valid JWT access token.
    // =========================================================================

    $router->group(['middleware' => ['api.auth']], function () use ($router): void {

        // ── Users ─────────────────────────────────────────────────────────

        $router->group(['prefix' => '/users', 'middleware' => ['api.permission:users.access']], function () use ($router): void {
            /**
             * GET  /api/v1/users
             * List users with optional filters: ?branch_id=&role=&search=&per_page=&page=
             */
            $router->get('/',       'UserController@index',   'api.v1.users.index');
            /** GET  /api/v1/users/{id}  — Fetch a single user with role + branch info */
            $router->get('/{id}',   'UserController@show',    'api.v1.users.show');
            /** POST /api/v1/users  — Create a new user (admin only) */
            $router->post('/',      'UserController@store',   'api.v1.users.store',   ['api.permission:users.create']);
            /** PUT  /api/v1/users/{id} — Update user details */
            $router->put('/{id}',   'UserController@update',  'api.v1.users.update',  ['api.permission:users.edit']);
            /** DELETE /api/v1/users/{id} — Soft-delete user */
            $router->delete('/{id}','UserController@destroy', 'api.v1.users.destroy', ['api.permission:users.delete']);
        });

        // ── Products ──────────────────────────────────────────────────────

        $router->group(['prefix' => '/products', 'middleware' => ['api.module:inventory']], function () use ($router): void {

            /**
             * GET /api/v1/products
             * Paginated product catalog.
             * Query: ?category_id=&brand_id=&search=&has_stock=1&warehouse_id=&per_page=&page=
             *
             * Response 200:
             * {
             *   "success": true,
             *   "data": [ { "id", "sku", "name", "category", "brand", "unit", "cost_price",
             *                "selling_price", "tax_rate", "stock_quantity", "variants": [] } ],
             *   "meta": { "total", "per_page", "current_page", "last_page" }
             * }
             */
            $router->get('/',            'ProductController@index',   'api.v1.products.index',  ['api.permission:products.access']);

            /**
             * POST /api/v1/products
             * Create a new product.
             *
             * Body: { "sku", "name", "category_id", "brand_id", "unit_id", "description",
             *         "cost_price", "selling_price", "tax_rate", "track_stock": bool,
             *         "reorder_level", "variants": [ { "name", "sku", "price" } ] }
             *
             * Response 201: { "success": true, "data": { product object }, "message": "Product created." }
             */
            $router->post('/',           'ProductController@store',   'api.v1.products.store',  ['api.permission:products.create']);

            /**
             * GET /api/v1/products/{id}
             * Full product detail including variants, images, and current stock levels by warehouse.
             *
             * Response 200: { "success": true, "data": { full product object } }
             */
            $router->get('/{id}',        'ProductController@show',    'api.v1.products.show',   ['api.permission:products.access']);

            /**
             * PUT /api/v1/products/{id}
             * Full or partial update of a product.
             *
             * Body: same as POST (all fields optional for partial updates)
             * Response 200: { "success": true, "data": { updated product }, "message": "Product updated." }
             */
            $router->put('/{id}',        'ProductController@update',  'api.v1.products.update', ['api.permission:products.edit']);

            /**
             * DELETE /api/v1/products/{id}
             * Soft-delete a product (products with stock or linked orders cannot be deleted).
             *
             * Response 200: { "success": true, "message": "Product deleted." }
             * Response 409: { "success": false, "message": "Cannot delete product with existing stock." }
             */
            $router->delete('/{id}',     'ProductController@destroy', 'api.v1.products.destroy',['api.permission:products.delete']);

            // Product Variants
            $router->get('/{id}/variants',          'ProductVariantController@index',  'api.v1.products.variants.index');
            $router->post('/{id}/variants',         'ProductVariantController@store',  'api.v1.products.variants.store',  ['api.permission:products.edit']);
            $router->put('/{id}/variants/{varId}',  'ProductVariantController@update', 'api.v1.products.variants.update', ['api.permission:products.edit']);
            $router->delete('/{id}/variants/{varId}','ProductVariantController@destroy','api.v1.products.variants.destroy',['api.permission:products.edit']);

            // Product stock across all warehouses
            $router->get('/{id}/stock',  'ProductController@stock',   'api.v1.products.stock',  ['api.permission:inventory.access']);
        });

        // ── Inventory / Stock ─────────────────────────────────────────────

        $router->group(['prefix' => '/inventory', 'middleware' => ['api.module:inventory', 'api.permission:inventory.access']], function () use ($router): void {

            /**
             * GET /api/v1/inventory/stock
             * Current stock levels.
             * Query: ?warehouse_id=&product_id=&category_id=&low_stock=1&per_page=&page=
             *
             * Response 200:
             * {
             *   "success": true,
             *   "data": [
             *     { "product_id", "product_name", "sku", "warehouse_id", "warehouse_name",
             *       "quantity_on_hand", "reserved_quantity", "available_quantity",
             *       "reorder_level", "unit_cost", "total_value" }
             *   ],
             *   "meta": { pagination }
             * }
             */
            $router->get('/stock',           'InventoryController@stock',           'api.v1.inventory.stock');

            /**
             * GET /api/v1/inventory/stock/{productId}
             * Stock for a specific product across all warehouses.
             */
            $router->get('/stock/{productId}','InventoryController@productStock',   'api.v1.inventory.stock.product');

            /**
             * POST /api/v1/inventory/adjust
             * Create a stock adjustment entry.
             *
             * Body:
             * {
             *   "warehouse_id": int,
             *   "reference":    string,
             *   "reason":       "damage" | "theft" | "expiry" | "count_correction" | "other",
             *   "notes":        string?,
             *   "lines": [
             *     { "product_id": int, "variant_id"?: int, "quantity_delta": float,
             *       "unit_cost"?: float, "batch_no"?: string, "expiry_date"?: string }
             *   ]
             * }
             *
             * Response 201:
             * { "success": true, "data": { "adjustment_id", "reference", "status": "pending" }, "message": "Adjustment created." }
             */
            $router->post('/adjust',         'InventoryController@adjust',          'api.v1.inventory.adjust',   ['api.permission:inventory.adjustments']);

            /**
             * GET /api/v1/inventory/adjustments
             * List stock adjustments with filters.
             * Query: ?warehouse_id=&from=&to=&status=&per_page=&page=
             */
            $router->get('/adjustments',     'InventoryController@adjustments',     'api.v1.inventory.adjustments');

            /**
             * GET /api/v1/inventory/adjustments/{id}
             * Adjustment detail.
             */
            $router->get('/adjustments/{id}','InventoryController@adjustmentShow',  'api.v1.inventory.adjustment.show');

            /**
             * POST /api/v1/inventory/transfer
             * Initiate a stock transfer between warehouses or branches.
             *
             * Body:
             * {
             *   "from_warehouse_id": int,
             *   "to_warehouse_id":   int,
             *   "expected_date":     string (Y-m-d),
             *   "notes":             string?,
             *   "lines": [
             *     { "product_id": int, "variant_id"?: int, "quantity": float,
             *       "batch_no"?: string, "expiry_date"?: string }
             *   ]
             * }
             *
             * Response 201:
             * { "success": true, "data": { "transfer_id", "transfer_no", "status": "pending" }, "message": "Transfer initiated." }
             */
            $router->post('/transfer',       'InventoryController@transfer',        'api.v1.inventory.transfer',  ['api.permission:inventory.transfers']);

            /**
             * GET /api/v1/inventory/transfers
             * List transfers. Query: ?from_warehouse_id=&to_warehouse_id=&status=&per_page=&page=
             */
            $router->get('/transfers',       'InventoryController@transfers',       'api.v1.inventory.transfers');

            /**
             * GET /api/v1/inventory/transfers/{id}
             */
            $router->get('/transfers/{id}',  'InventoryController@transferShow',    'api.v1.inventory.transfer.show');

            /**
             * POST /api/v1/inventory/transfers/{id}/receive
             * Mark a transfer as received at the destination warehouse.
             */
            $router->post('/transfers/{id}/receive', 'InventoryController@receiveTransfer', 'api.v1.inventory.transfer.receive', ['api.permission:inventory.transfers']);

            /**
             * GET /api/v1/inventory/movements
             * Full stock movement log (all ins/outs/transfers/adjustments).
             * Query: ?product_id=&warehouse_id=&type=&from=&to=&per_page=&page=
             */
            $router->get('/movements',       'InventoryController@movements',       'api.v1.inventory.movements');

            /**
             * GET /api/v1/inventory/valuation
             * Stock valuation summary by warehouse / category.
             * Query: ?warehouse_id=&method=average|fifo|lifo&as_of=
             */
            $router->get('/valuation',       'InventoryController@valuation',       'api.v1.inventory.valuation');
        });

        // ── Customers ─────────────────────────────────────────────────────

        $router->group(['prefix' => '/customers', 'middleware' => ['api.module:crm']], function () use ($router): void {

            /**
             * GET /api/v1/customers
             * Paginated customer list.
             * Query: ?search=&type=individual|business&is_active=1&per_page=&page=
             *
             * Response 200:
             * {
             *   "success": true,
             *   "data": [
             *     { "id", "code", "name", "type", "email", "phone", "address",
             *       "credit_limit", "current_balance", "is_active" }
             *   ],
             *   "meta": { pagination }
             * }
             */
            $router->get('/',       'CustomerController@index',  'api.v1.customers.index',  ['api.permission:customers.access']);

            /**
             * POST /api/v1/customers
             * Create a customer.
             *
             * Body: { "name", "type"?, "email"?, "phone"?, "address"?,
             *         "tax_number"?, "credit_limit"?, "payment_terms"?, "notes"? }
             * Response 201: { "success": true, "data": { customer }, "message": "Customer created." }
             */
            $router->post('/',      'CustomerController@store',  'api.v1.customers.store',  ['api.permission:customers.create']);

            /**
             * GET /api/v1/customers/{id}
             * Full customer record including outstanding balance, recent orders, ledger summary.
             */
            $router->get('/{id}',   'CustomerController@show',   'api.v1.customers.show',   ['api.permission:customers.access']);

            /**
             * PUT /api/v1/customers/{id}
             * Update a customer.
             */
            $router->put('/{id}',   'CustomerController@update', 'api.v1.customers.update', ['api.permission:customers.edit']);

            /**
             * DELETE /api/v1/customers/{id}
             * Soft-delete (customers with open balances cannot be deleted).
             */
            $router->delete('/{id}','CustomerController@destroy','api.v1.customers.destroy',['api.permission:customers.delete']);

            // Customer Ledger
            $router->get('/{id}/ledger','CustomerController@ledger','api.v1.customers.ledger',['api.permission:customers.access']);

            // Outstanding invoices for this customer
            $router->get('/{id}/outstanding','CustomerController@outstanding','api.v1.customers.outstanding',['api.permission:customers.access']);
        });

        // ── Suppliers ─────────────────────────────────────────────────────

        $router->group(['prefix' => '/suppliers'], function () use ($router): void {

            /**
             * GET /api/v1/suppliers
             * Paginated supplier list.
             * Query: ?search=&category=&is_active=1&per_page=&page=
             */
            $router->get('/',       'SupplierController@index',  'api.v1.suppliers.index',  ['api.permission:suppliers.access']);

            /**
             * POST /api/v1/suppliers
             * Create a supplier.
             *
             * Body: { "name", "code"?, "email"?, "phone"?, "address"?,
             *         "tax_number"?, "bank_account"?, "payment_terms"?, "notes"? }
             * Response 201: { "success": true, "data": { supplier }, "message": "Supplier created." }
             */
            $router->post('/',      'SupplierController@store',  'api.v1.suppliers.store',  ['api.permission:suppliers.create']);

            /**
             * GET /api/v1/suppliers/{id}
             * Full supplier record with outstanding payable balance, purchase history.
             */
            $router->get('/{id}',   'SupplierController@show',   'api.v1.suppliers.show',   ['api.permission:suppliers.access']);

            /**
             * PUT /api/v1/suppliers/{id}
             */
            $router->put('/{id}',   'SupplierController@update', 'api.v1.suppliers.update', ['api.permission:suppliers.edit']);

            /**
             * DELETE /api/v1/suppliers/{id}
             */
            $router->delete('/{id}','SupplierController@destroy','api.v1.suppliers.destroy',['api.permission:suppliers.delete']);

            // Supplier Ledger
            $router->get('/{id}/ledger','SupplierController@ledger','api.v1.suppliers.ledger',['api.permission:suppliers.access']);
        });

        // ── Sales Orders ──────────────────────────────────────────────────

        $router->group(['prefix' => '/sales/orders', 'middleware' => ['api.module:sales']], function () use ($router): void {

            /**
             * GET /api/v1/sales/orders
             * Paginated sales order list.
             * Query: ?customer_id=&status=draft|confirmed|invoiced|cancelled&from=&to=&per_page=&page=
             *
             * Response 200:
             * {
             *   "success": true,
             *   "data": [
             *     { "id", "order_no", "customer", "date", "delivery_date",
             *       "subtotal", "tax_total", "discount_total", "grand_total",
             *       "paid_amount", "outstanding_amount", "status" }
             *   ],
             *   "meta": { pagination }
             * }
             */
            $router->get('/',       'SalesOrderController@index',  'api.v1.sales.orders.index',  ['api.permission:sales.access']);

            /**
             * POST /api/v1/sales/orders
             * Create a sales order.
             *
             * Body:
             * {
             *   "customer_id":   int,
             *   "order_date":    "Y-m-d",
             *   "delivery_date": "Y-m-d"?,
             *   "branch_id":     int?,
             *   "warehouse_id":  int?,
             *   "currency":      "BDT",
             *   "notes":         string?,
             *   "discount_type": "flat"|"percent"?,
             *   "discount_value": float?,
             *   "lines": [
             *     { "product_id": int, "variant_id"?: int, "quantity": float,
             *       "unit_price": float, "discount"?: float, "tax_rate"?: float,
             *       "notes"?: string }
             *   ]
             * }
             *
             * Response 201:
             * { "success": true, "data": { order + lines }, "message": "Sales order created." }
             */
            $router->post('/',      'SalesOrderController@store',  'api.v1.sales.orders.store',  ['api.permission:sales.create']);

            /**
             * GET /api/v1/sales/orders/{id}
             * Full sales order detail with lines, payments, linked invoices.
             */
            $router->get('/{id}',   'SalesOrderController@show',   'api.v1.sales.orders.show',   ['api.permission:sales.access']);

            /**
             * PUT /api/v1/sales/orders/{id}
             * Update a draft sales order. Confirmed/invoiced orders cannot be edited.
             *
             * Body: same as POST (all fields optional for partial update)
             * Response 200: { "success": true, "data": { updated order }, "message": "Order updated." }
             */
            $router->put('/{id}',   'SalesOrderController@update', 'api.v1.sales.orders.update', ['api.permission:sales.edit']);

            /**
             * DELETE /api/v1/sales/orders/{id}
             * Cancel / soft-delete a draft order. Confirmed orders must use the cancel endpoint.
             */
            $router->delete('/{id}','SalesOrderController@destroy','api.v1.sales.orders.destroy',['api.permission:sales.delete']);

            /**
             * POST /api/v1/sales/orders/{id}/confirm
             * Confirm a draft order (triggers stock reservation).
             */
            $router->post('/{id}/confirm', 'SalesOrderController@confirm', 'api.v1.sales.orders.confirm', ['api.permission:sales.approve']);

            /**
             * POST /api/v1/sales/orders/{id}/cancel
             * Cancel a confirmed order (releases stock reservation).
             */
            $router->post('/{id}/cancel',  'SalesOrderController@cancel',  'api.v1.sales.orders.cancel',  ['api.permission:sales.edit']);

            /**
             * POST /api/v1/sales/orders/{id}/invoice
             * Generate a sales invoice from this order.
             *
             * Body: { "invoice_date"?: "Y-m-d", "due_date"?: "Y-m-d", "notes"?: string }
             *
             * Response 201:
             * {
             *   "success": true,
             *   "data": {
             *     "invoice_id":  int,
             *     "invoice_no":  string,
             *     "invoice_url": "/api/v1/sales/invoices/{invoice_id}"
             *   },
             *   "message": "Invoice created from order."
             * }
             */
            $router->post('/{id}/invoice', 'SalesOrderController@createInvoice', 'api.v1.sales.orders.invoice', ['api.permission:sales.invoices']);
        });

        // ── Sales Invoices ────────────────────────────────────────────────

        $router->group(['prefix' => '/sales/invoices', 'middleware' => ['api.module:sales', 'api.permission:sales.access']], function () use ($router): void {
            $router->get('/',       'SalesInvoiceController@index',  'api.v1.sales.invoices.index');
            $router->post('/',      'SalesInvoiceController@store',  'api.v1.sales.invoices.store',  ['api.permission:sales.invoices']);
            $router->get('/{id}',   'SalesInvoiceController@show',   'api.v1.sales.invoices.show');
            $router->put('/{id}',   'SalesInvoiceController@update', 'api.v1.sales.invoices.update', ['api.permission:sales.invoices']);
            $router->post('/{id}/payment', 'SalesInvoiceController@recordPayment', 'api.v1.sales.invoices.payment', ['api.permission:sales.payments']);
            $router->post('/{id}/void',    'SalesInvoiceController@void',           'api.v1.sales.invoices.void',    ['api.permission:sales.invoices.void']);
        });

        // ── Sales Returns ──────────────────────────────────────────────────

        $router->group(['prefix' => '/sales/returns', 'middleware' => ['api.module:sales', 'api.permission:sales.returns']], function () use ($router): void {
            $router->get('/',       'SalesReturnController@index',  'api.v1.sales.returns.index');
            $router->post('/',      'SalesReturnController@store',  'api.v1.sales.returns.store');
            $router->get('/{id}',   'SalesReturnController@show',   'api.v1.sales.returns.show');
            $router->post('/{id}/approve','SalesReturnController@approve','api.v1.sales.returns.approve',['api.permission:sales.approve']);
        });

        // ── Purchase Orders ───────────────────────────────────────────────

        $router->group(['prefix' => '/purchasing/orders', 'middleware' => ['api.module:purchasing', 'api.permission:purchasing.access']], function () use ($router): void {
            $router->get('/',       'PurchaseOrderController@index',  'api.v1.purchasing.orders.index');
            $router->post('/',      'PurchaseOrderController@store',  'api.v1.purchasing.orders.store',  ['api.permission:purchasing.create']);
            $router->get('/{id}',   'PurchaseOrderController@show',   'api.v1.purchasing.orders.show');
            $router->put('/{id}',   'PurchaseOrderController@update', 'api.v1.purchasing.orders.update', ['api.permission:purchasing.edit']);
            $router->delete('/{id}','PurchaseOrderController@destroy','api.v1.purchasing.orders.destroy',['api.permission:purchasing.delete']);
            $router->post('/{id}/approve', 'PurchaseOrderController@approve', 'api.v1.purchasing.orders.approve', ['api.permission:purchasing.approve']);
            $router->post('/{id}/receive', 'PurchaseOrderController@receive',  'api.v1.purchasing.orders.receive', ['api.permission:purchasing.grn']);
        });

        // ── HR: Employees ─────────────────────────────────────────────────

        $router->group(['prefix' => '/hr/employees', 'middleware' => ['api.module:hr', 'api.permission:hr.employees.access']], function () use ($router): void {
            $router->get('/',       'HR\EmployeeController@index',  'api.v1.hr.employees.index');
            $router->post('/',      'HR\EmployeeController@store',  'api.v1.hr.employees.store',  ['api.permission:hr.employees.create']);
            $router->get('/{id}',   'HR\EmployeeController@show',   'api.v1.hr.employees.show');
            $router->put('/{id}',   'HR\EmployeeController@update', 'api.v1.hr.employees.update', ['api.permission:hr.employees.edit']);
            $router->delete('/{id}','HR\EmployeeController@destroy','api.v1.hr.employees.destroy',['api.permission:hr.employees.delete']);
        });

        // ── Attendance ────────────────────────────────────────────────────

        $router->group(['prefix' => '/attendance', 'middleware' => ['api.module:hr', 'api.permission:attendance.access']], function () use ($router): void {
            $router->get('/',              'AttendanceController@index',   'api.v1.attendance.index');
            $router->post('/check-in',     'AttendanceController@checkIn', 'api.v1.attendance.check-in');
            $router->post('/check-out',    'AttendanceController@checkOut','api.v1.attendance.check-out');
            $router->get('/my',            'AttendanceController@my',      'api.v1.attendance.my');
            $router->get('/summary/{employeeId}', 'AttendanceController@employeeSummary', 'api.v1.attendance.summary');
        });

        // ── Payroll ───────────────────────────────────────────────────────

        $router->group(['prefix' => '/payroll', 'middleware' => ['api.module:payroll', 'api.permission:payroll.access']], function () use ($router): void {
            $router->get('/payslips',          'PayrollController@payslips',    'api.v1.payroll.payslips');
            $router->get('/payslips/{id}',     'PayrollController@payslipShow', 'api.v1.payroll.payslip.show');
            $router->post('/process',          'PayrollController@process',     'api.v1.payroll.process',  ['api.permission:payroll.process']);
            $router->get('/salary-structures', 'PayrollController@structures',  'api.v1.payroll.structures');
        });

        // ── Accounting ────────────────────────────────────────────────────

        $router->group(['prefix' => '/accounting', 'middleware' => ['api.module:accounting', 'api.permission:accounting.access']], function () use ($router): void {
            $router->get('/accounts',          'AccountingController@accounts',     'api.v1.accounting.accounts');
            $router->get('/journals',          'AccountingController@journals',     'api.v1.accounting.journals');
            $router->post('/journals',         'AccountingController@storeJournal', 'api.v1.accounting.journals.store', ['api.permission:accounting.journals']);
            $router->get('/journals/{id}',     'AccountingController@journalShow',  'api.v1.accounting.journal.show');
            $router->get('/ledger/{accountId}','AccountingController@ledger',       'api.v1.accounting.ledger');
        });

        // ── Expenses ──────────────────────────────────────────────────────

        $router->group(['prefix' => '/expenses', 'middleware' => ['api.permission:expenses.access']], function () use ($router): void {
            $router->get('/',       'ExpenseController@index',  'api.v1.expenses.index');
            $router->post('/',      'ExpenseController@store',  'api.v1.expenses.store',  ['api.permission:expenses.create']);
            $router->get('/{id}',   'ExpenseController@show',   'api.v1.expenses.show');
            $router->put('/{id}',   'ExpenseController@update', 'api.v1.expenses.update', ['api.permission:expenses.edit']);
            $router->delete('/{id}','ExpenseController@destroy','api.v1.expenses.destroy',['api.permission:expenses.delete']);
            $router->post('/{id}/approve','ExpenseController@approve','api.v1.expenses.approve',['api.permission:expenses.approve']);
        });

        // ── Notifications ─────────────────────────────────────────────────

        $router->group(['prefix' => '/notifications'], function () use ($router): void {
            $router->get('/',                     'NotificationController@index',       'api.v1.notifications.index');
            $router->get('/unread-count',         'NotificationController@unreadCount', 'api.v1.notifications.unread-count');
            $router->post('/{id}/mark-read',      'NotificationController@markRead',    'api.v1.notifications.mark-read');
            $router->post('/mark-all-read',       'NotificationController@markAllRead', 'api.v1.notifications.mark-all-read');
            $router->delete('/{id}',              'NotificationController@destroy',     'api.v1.notifications.destroy');
        });

        // =========================================================================
        // REPORTS
        // =========================================================================

        $router->group(['prefix' => '/reports', 'middleware' => ['api.permission:reports.access']], function () use ($router): void {

            /**
             * GET /api/v1/reports/sales
             * Aggregated sales report.
             *
             * Query parameters:
             *  - from          (Y-m-d)   default: first day of current month
             *  - to            (Y-m-d)   default: today
             *  - group_by      daily|weekly|monthly  default: daily
             *  - branch_id     int (omit for all branches)
             *  - customer_id   int (omit for all customers)
             *  - product_id    int (omit for all products)
             *  - category_id   int
             *  - salesman_id   int
             *  - include_vat   bool   default: true
             *
             * Response 200:
             * {
             *   "success": true,
             *   "data": {
             *     "period":          { "from": "Y-m-d", "to": "Y-m-d" },
             *     "summary": {
             *       "total_orders":   int,
             *       "total_invoiced": float,
             *       "total_payments": float,
             *       "total_outstanding": float,
             *       "total_returns":  float,
             *       "gross_revenue":  float,
             *       "net_revenue":    float,
             *       "vat_collected":  float
             *     },
             *     "series": [
             *       { "date": "Y-m-d", "orders": int, "revenue": float, "returns": float }
             *     ],
             *     "top_products": [
             *       { "product_id", "name", "qty_sold", "revenue" }
             *     ],
             *     "top_customers": [
             *       { "customer_id", "name", "orders", "revenue" }
             *     ]
             *   }
             * }
             */
            $router->get('/sales', 'ReportController@sales', 'api.v1.reports.sales');

            /**
             * GET /api/v1/reports/inventory
             * Aggregated inventory / stock report.
             *
             * Query parameters:
             *  - warehouse_id    int (omit for all warehouses)
             *  - category_id     int
             *  - as_of           Y-m-d  default: today
             *  - low_stock_only  bool
             *  - expiring_days   int    include items expiring within N days
             *  - valuation_method average|fifo|lifo  default: average
             *
             * Response 200:
             * {
             *   "success": true,
             *   "data": {
             *     "as_of":           "Y-m-d",
             *     "total_skus":      int,
             *     "total_value":     float,
             *     "currency":        "BDT",
             *     "by_warehouse": [
             *       { "warehouse_id", "name", "total_skus", "total_quantity", "total_value" }
             *     ],
             *     "by_category": [
             *       { "category_id", "name", "skus", "quantity", "value" }
             *     ],
             *     "low_stock": [
             *       { "product_id", "sku", "name", "warehouse", "quantity", "reorder_level" }
             *     ],
             *     "movements_summary": {
             *       "total_in":  float,
             *       "total_out": float,
             *       "adjustments": float
             *     }
             *   }
             * }
             */
            $router->get('/inventory', 'ReportController@inventory', 'api.v1.reports.inventory');

            /**
             * GET /api/v1/reports/revenue
             * Revenue & profitability report.
             *
             * Query parameters:
             *  - from           Y-m-d
             *  - to             Y-m-d
             *  - group_by       monthly|quarterly|yearly  default: monthly
             *  - branch_id      int
             *  - include_cogs   bool  default: true  (include cost of goods sold)
             *  - include_expenses bool default: true
             *
             * Response 200:
             * {
             *   "success": true,
             *   "data": {
             *     "period":       { "from": "Y-m-d", "to": "Y-m-d" },
             *     "currency":     "BDT",
             *     "summary": {
             *       "gross_revenue":   float,
             *       "cogs":            float,
             *       "gross_profit":    float,
             *       "gross_margin_pct":float,
             *       "total_expenses":  float,
             *       "net_profit":      float,
             *       "net_margin_pct":  float,
             *       "vat_collected":   float,
             *       "vat_paid":        float
             *     },
             *     "series": [
             *       {
             *         "period":        "2025-01",
             *         "revenue":       float,
             *         "cogs":          float,
             *         "gross_profit":  float,
             *         "expenses":      float,
             *         "net_profit":    float
             *       }
             *     ],
             *     "expense_breakdown": [
             *       { "category": string, "amount": float, "pct_of_revenue": float }
             *     ],
             *     "revenue_by_branch": [
             *       { "branch_id": int, "branch_name": string, "revenue": float, "net_profit": float }
             *     ]
             *   }
             * }
             */
            $router->get('/revenue', 'ReportController@revenue', 'api.v1.reports.revenue');

            /**
             * GET /api/v1/reports/purchases
             * Purchase report. Query: ?from=&to=&supplier_id=&group_by=
             */
            $router->get('/purchases',       'ReportController@purchases',      'api.v1.reports.purchases');

            /**
             * GET /api/v1/reports/payroll
             * Payroll cost summary. Query: ?from=&to=&branch_id=&department_id=
             */
            $router->get('/payroll',         'ReportController@payroll',        'api.v1.reports.payroll',         ['api.permission:payroll.access']);

            /**
             * GET /api/v1/reports/customer-aging
             * Accounts receivable aging. Query: ?as_of=&branch_id=
             */
            $router->get('/customer-aging',  'ReportController@customerAging',  'api.v1.reports.customer-aging');

            /**
             * GET /api/v1/reports/supplier-aging
             * Accounts payable aging. Query: ?as_of=&branch_id=
             */
            $router->get('/supplier-aging',  'ReportController@supplierAging',  'api.v1.reports.supplier-aging');

            /**
             * GET /api/v1/reports/trial-balance
             * Trial balance. Query: ?as_of=&branch_id=
             */
            $router->get('/trial-balance',   'ReportController@trialBalance',   'api.v1.reports.trial-balance',   ['api.permission:accounting.access']);

            /**
             * GET /api/v1/reports/balance-sheet
             * Balance sheet. Query: ?as_of=&branch_id=
             */
            $router->get('/balance-sheet',   'ReportController@balanceSheet',   'api.v1.reports.balance-sheet',   ['api.permission:accounting.access']);

            /**
             * GET /api/v1/reports/vat
             * VAT/Mushak report for Bangladesh NBR compliance.
             * Query: ?from=&to=&branch_id=&type=mushak-6.3|mushak-6.4|mushak-6.7|return
             */
            $router->get('/vat',             'ReportController@vat',            'api.v1.reports.vat');

            /**
             * POST /api/v1/reports/export
             * Export any report to Excel or PDF.
             *
             * Body: { "type": "sales|inventory|revenue|...", "format": "excel|pdf", ...filters }
             * Response 200: binary file download (application/pdf or application/vnd.openxmlformats...)
             */
            $router->post('/export',         'ReportController@export',          'api.v1.reports.export');
        });

        // ── Settings (read-only via API) ──────────────────────────────────

        $router->group(['prefix' => '/settings', 'middleware' => ['api.permission:settings.access']], function () use ($router): void {
            $router->get('/general',         'SettingsController@general',    'api.v1.settings.general');
            $router->get('/branches',        'SettingsController@branches',   'api.v1.settings.branches');
            $router->get('/currencies',      'SettingsController@currencies', 'api.v1.settings.currencies');
            $router->get('/payment-methods', 'SettingsController@paymentMethods','api.v1.settings.payment-methods');
        });

    }); // end authenticated group

    // ============================================================================
    // WEBHOOK / CALLBACK ENDPOINTS (unsigned — validated internally by each handler)
    // ============================================================================

    $router->group(['prefix' => '/webhooks', 'middleware' => ['api.throttle:300,1']], function () use ($router): void {
        /**
         * POST /api/v1/webhooks/bkash
         * bKash payment gateway IPN (Instant Payment Notification).
         */
        $router->post('/bkash',  'Webhooks\BkashWebhookController@handle',  'api.v1.webhooks.bkash');

        /**
         * POST /api/v1/webhooks/nagad
         * Nagad payment gateway IPN.
         */
        $router->post('/nagad',  'Webhooks\NagadWebhookController@handle',   'api.v1.webhooks.nagad');

        /**
         * POST /api/v1/webhooks/sms-status
         * SMS delivery status callback.
         */
        $router->post('/sms-status', 'Webhooks\SmsStatusController@handle', 'api.v1.webhooks.sms-status');
    });

}); // end /api/v1 group

// ============================================================================
// API FALLBACK — 404 for unknown API routes
// ============================================================================

$router->get('/api/{any}',    'Api\FallbackController@notFound', 'api.fallback', [], true);
$router->post('/api/{any}',   'Api\FallbackController@notFound', 'api.fallback.post', [], true);
$router->put('/api/{any}',    'Api\FallbackController@notFound', 'api.fallback.put', [], true);
$router->patch('/api/{any}',  'Api\FallbackController@notFound', 'api.fallback.patch', [], true);
$router->delete('/api/{any}', 'Api\FallbackController@notFound', 'api.fallback.delete', [], true);
