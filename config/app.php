<?php

declare(strict_types=1);

/**
 * BizCore ERP - Application Configuration
 *
 * Central application configuration file. All values are sourced from
 * environment variables with safe defaults for local development.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Application Identity
    |--------------------------------------------------------------------------
    |
    | The name and version of the application. The name is used in
    | notifications, emails, and throughout the UI.
    |
    */

    'name'    => $_ENV['APP_NAME'] ?? 'BizCore ERP',
    'version' => '1.0.0',
    'tagline' => 'Multi-Branch Enterprise Resource Planning Platform',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | Supported: "local", "development", "staging", "production"
    | In production set APP_DEBUG=false and APP_ENV=production.
    |
    */

    'env'   => $_ENV['APP_ENV'] ?? 'local',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url'   => rtrim($_ENV['APP_URL'] ?? 'http://localhost:8080', '/'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | Used to encrypt cookies, sessions, and other sensitive data.
    | Generate with: php -r "echo 'base64:'.base64_encode(random_bytes(32));"
    |
    */

    'key'    => $_ENV['APP_KEY'] ?? '',
    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Timezone & Locale
    |--------------------------------------------------------------------------
    |
    | Default timezone is Asia/Dhaka (BDT, UTC+6) for Bangladesh operations.
    | Supported locales: en (English), bn (Bengali/Bangla).
    |
    */

    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Dhaka',
    'locale'   => $_ENV['APP_LOCALE'] ?? 'en',

    'supported_locales' => [
        'en' => [
            'name'      => 'English',
            'native'    => 'English',
            'direction' => 'ltr',
            'flag'      => 'gb',
        ],
        'bn' => [
            'name'      => 'Bengali',
            'native'    => 'বাংলা',
            'direction' => 'ltr',
            'flag'      => 'bd',
        ],
    ],

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for Bangladesh Taka (BDT).
    | Additional currencies can be enabled per branch.
    |
    */

    'currency' => [
        'default' => 'BDT',
        'symbol'  => '৳',
        'code'    => 'BDT',
        'locale'  => 'bn_BD',
        'format'  => '%s %v',      // symbol amount
        'decimal_separator'   => '.',
        'thousands_separator' => ',',
        'decimal_places'      => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Date & Number Formatting
    |--------------------------------------------------------------------------
    */

    'date_format'     => 'd/m/Y',
    'datetime_format' => 'd/m/Y H:i:s',
    'time_format'     => 'H:i',

    'fiscal_year' => [
        'start_month' => 7,   // July
        'start_day'   => 1,
        'end_month'   => 6,   // June
        'end_day'     => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Branch Configuration
    |--------------------------------------------------------------------------
    |
    | BizCore ERP supports multiple branches under a single installation.
    | Each branch gets its own data scope, user permissions, and reporting.
    |
    */

    'multi_branch' => [
        'enabled'               => true,
        'max_branches'          => 50,
        'branch_code_prefix'    => 'BR',
        'default_branch_code'   => 'BR001',
        'cross_branch_transfer' => true,
        'consolidated_reports'  => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | ERP Modules
    |--------------------------------------------------------------------------
    |
    | List of all available ERP modules. Each module can be enabled or
    | disabled independently per branch or globally.
    |
    */

    'modules' => [
        'accounting'       => ['enabled' => true,  'version' => '1.0.0'],
        'inventory'        => ['enabled' => true,  'version' => '1.0.0'],
        'sales'            => ['enabled' => true,  'version' => '1.0.0'],
        'purchasing'       => ['enabled' => true,  'version' => '1.0.0'],
        'hr'               => ['enabled' => true,  'version' => '1.0.0'],
        'payroll'          => ['enabled' => true,  'version' => '1.0.0'],
        'crm'              => ['enabled' => true,  'version' => '1.0.0'],
        'manufacturing'    => ['enabled' => false, 'version' => '1.0.0'],
        'projects'         => ['enabled' => false, 'version' => '1.0.0'],
        'pos'              => ['enabled' => true,  'version' => '1.0.0'],
        'asset_management' => ['enabled' => true,  'version' => '1.0.0'],
        'vat_compliance'   => ['enabled' => true,  'version' => '1.0.0'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Providers
    |--------------------------------------------------------------------------
    |
    | Providers are booted in the order listed. Core framework providers
    | must come before module-specific providers.
    |
    */

    'providers' => [
        // Framework Core
        App\Providers\AppServiceProvider::class,
        App\Providers\DatabaseServiceProvider::class,
        App\Providers\CacheServiceProvider::class,
        App\Providers\SessionServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\ValidationServiceProvider::class,
        App\Providers\LoggingServiceProvider::class,
        App\Providers\MailServiceProvider::class,
        App\Providers\StorageServiceProvider::class,
        App\Providers\QueueServiceProvider::class,
        App\Providers\EventServiceProvider::class,

        // ERP Modules
        App\Modules\Accounting\AccountingServiceProvider::class,
        App\Modules\Inventory\InventoryServiceProvider::class,
        App\Modules\Sales\SalesServiceProvider::class,
        App\Modules\Purchasing\PurchasingServiceProvider::class,
        App\Modules\HR\HRServiceProvider::class,
        App\Modules\Payroll\PayrollServiceProvider::class,
        App\Modules\CRM\CRMServiceProvider::class,
        App\Modules\POS\POSServiceProvider::class,
        App\Modules\AssetManagement\AssetServiceProvider::class,
        App\Modules\VATCompliance\VATServiceProvider::class,
        App\Modules\Reports\ReportServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | Facade-style aliases for commonly used classes throughout the app.
    |
    */

    'aliases' => [
        'App'        => App\Facades\AppFacade::class,
        'Auth'       => App\Facades\AuthFacade::class,
        'Cache'      => App\Facades\CacheFacade::class,
        'Config'     => App\Facades\ConfigFacade::class,
        'DB'         => App\Facades\DatabaseFacade::class,
        'Event'      => App\Facades\EventFacade::class,
        'Hash'       => App\Facades\HashFacade::class,
        'Log'        => App\Facades\LogFacade::class,
        'Mail'       => App\Facades\MailFacade::class,
        'Queue'      => App\Facades\QueueFacade::class,
        'Redis'      => App\Facades\RedisFacade::class,
        'Route'      => App\Facades\RouteFacade::class,
        'Session'    => App\Facades\SessionFacade::class,
        'Storage'    => App\Facades\StorageFacade::class,
        'Validator'  => App\Facades\ValidatorFacade::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'enabled'  => false,
        'message'  => 'BizCore ERP is currently undergoing scheduled maintenance. We will be back shortly.',
        'retry'    => 60,
        'allowed_ips' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'api' => [
        'version'        => 'v1',
        'prefix'         => '/api',
        'throttle'       => (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 60),
        'throttle_window'=> (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60),
        'strict_https'   => filter_var($_ENV['APP_ENV'] ?? 'local', FILTER_VALIDATE_BOOLEAN) === false
                            && ($_ENV['APP_ENV'] ?? 'local') === 'production',
    ],

];
