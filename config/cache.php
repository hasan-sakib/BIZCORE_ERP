<?php

declare(strict_types=1);

/**
 * BizCore ERP - Cache Configuration
 *
 * Redis is the primary cache driver for production.
 * Array driver is used in testing; file driver as a local fallback.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "redis", "file", "array", "null"
    |
    */

    'default' => $_ENV['CACHE_DRIVER'] ?? 'redis',

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    */

    'stores' => [

        'redis' => [
            'driver'     => 'redis',
            'connection' => 'cache',        // references redis.connections.cache
            'prefix'     => 'bizcore:cache:',
            'lock_connection' => 'default', // separate connection for distributed locks
        ],

        'file' => [
            'driver'          => 'file',
            'path'            => dirname(__DIR__) . '/storage/framework/cache/data',
            'lock_path'       => dirname(__DIR__) . '/storage/framework/cache/locks',
            'permission'      => 0755,
            'directory_permission' => 0755,
        ],

        'array' => [
            'driver'    => 'array',
            'serialize' => false,
        ],

        'null' => [
            'driver' => 'null',
        ],

        /*
        |----------------------------------------------------------------------
        | Dedicated caches for specific subsystems
        |----------------------------------------------------------------------
        | These use the same Redis server but a different key prefix and TTL.
        */

        'session_cache' => [
            'driver'     => 'redis',
            'connection' => 'session',
            'prefix'     => 'bizcore:session:',
        ],

        'report_cache' => [
            'driver'     => 'redis',
            'connection' => 'cache',
            'prefix'     => 'bizcore:report:',
        ],

        'rate_limiter' => [
            'driver'     => 'redis',
            'connection' => 'default',
            'prefix'     => 'bizcore:rl:',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Applied globally to all cache keys to avoid collisions when sharing
    | a Redis instance across multiple applications.
    |
    */

    'prefix' => 'bizcore',

    /*
    |--------------------------------------------------------------------------
    | Default TTL (Time-to-Live)
    |--------------------------------------------------------------------------
    |
    | All durations in seconds unless stated otherwise.
    |
    */

    'ttl' => [
        'default'          => (int) ($_ENV['CACHE_TTL'] ?? 3600),      // 1 hour
        'short'            => 300,     // 5 minutes  — live dashboard widgets
        'medium'           => 1800,    // 30 minutes — lookup tables
        'long'             => 86400,   // 24 hours   — static reference data
        'forever'          => 0,       // no expiry  — use carefully

        // ERP-specific TTLs
        'user_permissions' => 3600,    // RBAC permission set per user
        'branch_settings'  => 7200,    // branch configuration
        'chart_of_accounts'=> 86400,   // COA rarely changes
        'product_catalog'  => 1800,    // product / price list
        'exchange_rates'   => 3600,    // currency rates
        'vat_rates'        => 86400,   // VAT configuration
        'report_data'      => 900,     // pre-computed report results
        'dashboard_widgets'=> 300,     // KPI counters
        'inventory_levels' => 60,      // near-real-time stock
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tags Support
    |--------------------------------------------------------------------------
    |
    | Tags allow flushing a group of related cache entries together.
    | Requires a driver that supports tags (redis, memcached).
    |
    */

    'tags' => [
        'enabled' => true,

        // Predefined tag groups for ERP modules
        'groups' => [
            'accounting'  => ['coa', 'journal', 'ledger', 'trial_balance'],
            'inventory'   => ['products', 'stock', 'warehouses', 'movements'],
            'sales'       => ['orders', 'invoices', 'customers', 'pricing'],
            'purchasing'  => ['po', 'vendors', 'receipts'],
            'hr'          => ['employees', 'departments', 'attendance'],
            'payroll'     => ['salary', 'payslips', 'deductions'],
            'vat'         => ['vat_rates', 'vat_returns', 'mushak'],
            'reports'     => ['financial', 'sales_report', 'stock_report'],
            'settings'    => ['app_settings', 'branch_settings', 'user_prefs'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locking (Distributed Mutex)
    |--------------------------------------------------------------------------
    |
    | Used to prevent race conditions in concurrent ERP operations
    | (e.g., stock deduction, invoice numbering, journal posting).
    |
    */

    'locks' => [
        'driver'  => 'redis',
        'prefix'  => 'bizcore:lock:',
        'default_timeout' => 10,    // seconds to hold a lock
        'retry_wait'      => 250,   // milliseconds between retries
        'retry_count'     => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming
    |--------------------------------------------------------------------------
    |
    | Defines which caches are pre-warmed on application boot.
    |
    */

    'warming' => [
        'enabled' => filter_var($_ENV['CACHE_WARMING'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'targets' => [
            'chart_of_accounts',
            'vat_rates',
            'branch_settings',
            'product_catalog',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled'     => true,
        'track_hits'  => true,
        'track_misses'=> true,
        'log_slow_reads_ms' => 100,
    ],

];
