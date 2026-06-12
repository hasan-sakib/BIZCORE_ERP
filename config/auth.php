<?php

declare(strict_types=1);

/**
 * BizCore ERP - Authentication Configuration
 *
 * Defines guards, providers, password policies, account lockout,
 * and multi-factor authentication settings for the ERP platform.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Authentication Guard
    |--------------------------------------------------------------------------
    |
    | "web"  - session-based for browser UI
    | "api"  - stateless JWT for REST API / mobile clients
    |
    */

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [

        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
            'remember' => [
                'enabled'  => true,
                'duration' => 43200,    // 30 days in minutes
                'cookie'   => 'bizcore_remember',
            ],
        ],

        'api' => [
            'driver'   => 'jwt',
            'provider' => 'users',
            'input_key'  => 'api_token',    // for query-string fallback (non-production only)
            'storage_key' => 'api_token',
            'hash'       => false,
        ],

        'branch_admin' => [
            'driver'   => 'session',
            'provider' => 'branch_admins',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        'branch_admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\BranchAdmin::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policies
    |--------------------------------------------------------------------------
    */

    'passwords' => [

        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,           // minutes
            'throttle' => 60,           // seconds between reset attempts

            // Password complexity requirements
            'min_length'         => 8,
            'max_length'         => 128,
            'require_uppercase'  => true,
            'require_lowercase'  => true,
            'require_numeric'    => true,
            'require_special'    => true,
            'special_chars'      => '!@#$%^&*()-_=+[]{}|;:,.<>?',
            'disallow_common'    => true,       // block common passwords list
            'disallow_username'  => true,       // password cannot contain username
            'history_count'      => 5,          // cannot reuse last N passwords
            'max_age_days'       => 90,         // force change after N days (0 = disabled)
            'min_age_days'       => 1,          // must keep password for at least N days
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout
    |--------------------------------------------------------------------------
    |
    | After MAX_LOGIN_ATTEMPTS failed logins within the decay window,
    | the account is locked for LOCKOUT_DURATION seconds.
    |
    */

    'lockout' => [
        'enabled'          => true,
        'max_attempts'     => (int) ($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5),
        'decay_seconds'    => 300,      // sliding window for counting attempts
        'lockout_duration' => (int) ($_ENV['LOCKOUT_DURATION'] ?? 900),   // 15 minutes
        'notify_user'      => true,     // email user on lockout
        'notify_admin'     => true,     // email super-admin on repeated lockouts
        'auto_unlock'      => true,     // automatically unlock after duration
        'ip_based'         => true,     // also track by IP (in addition to user)
        'ip_max_attempts'  => 20,       // per-IP threshold (cross-user brute force)
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */

    'session' => [
        'regenerate_on_login'  => true,     // prevent session fixation
        'invalidate_on_logout' => true,
        'single_session'       => false,    // allow multiple concurrent sessions
        'idle_timeout'         => (int) ($_ENV['SESSION_LIFETIME'] ?? 120),   // minutes
        'absolute_timeout'     => 480,      // hard limit in minutes regardless of activity
        'bind_to_ip'           => false,    // bind session to originating IP (breaks behind LB)
        'bind_to_ua'           => true,     // bind session to user agent
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication (MFA)
    |--------------------------------------------------------------------------
    */

    'mfa' => [
        'enabled'        => filter_var($_ENV['MFA_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'required_roles' => ['super_admin', 'admin', 'finance_manager'],
        'methods'        => ['totp', 'email_otp'],

        'totp' => [
            'issuer'    => 'BizCore ERP',
            'digits'    => 6,
            'period'    => 30,
            'algorithm' => 'sha1',
            'window'    => 1,       // allow 1 step drift
        ],

        'email_otp' => [
            'length'  => 6,
            'expires' => 10,        // minutes
            'numeric_only' => true,
        ],

        'backup_codes' => [
            'count'  => 10,
            'length' => 8,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Based Access Control (RBAC)
    |--------------------------------------------------------------------------
    */

    'rbac' => [
        'enabled' => true,
        'cache'   => true,
        'cache_ttl' => 3600,    // seconds

        'super_admin_role' => 'super_admin',

        'roles' => [
            'super_admin'       => 'Super Administrator',
            'admin'             => 'Administrator',
            'branch_manager'    => 'Branch Manager',
            'finance_manager'   => 'Finance Manager',
            'accountant'        => 'Accountant',
            'hr_manager'        => 'HR Manager',
            'sales_manager'     => 'Sales Manager',
            'sales_rep'         => 'Sales Representative',
            'purchase_manager'  => 'Purchase Manager',
            'inventory_manager' => 'Inventory Manager',
            'warehouse_staff'   => 'Warehouse Staff',
            'cashier'           => 'Cashier / POS Operator',
            'auditor'           => 'Auditor (Read-Only)',
            'report_viewer'     => 'Report Viewer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth / SSO (future)
    |--------------------------------------------------------------------------
    */

    'sso' => [
        'enabled'   => false,
        'providers' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Blacklisting
    |--------------------------------------------------------------------------
    |
    | Invalidated JWT tokens are stored in Redis until their natural expiry.
    |
    */

    'token_blacklist' => [
        'enabled'    => true,
        'driver'     => 'redis',
        'prefix'     => 'bizcore:blacklist:',
        'grace_period' => 0,    // seconds of grace for clock skew
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Logging
    |--------------------------------------------------------------------------
    */

    'activity_log' => [
        'enabled'            => true,
        'log_logins'         => true,
        'log_logouts'        => true,
        'log_failed_logins'  => true,
        'log_password_changes' => true,
        'log_permission_denied' => true,
        'retain_days'        => 365,
    ],

];
