<?php

declare(strict_types=1);

/**
 * BizCore ERP - Database Configuration
 *
 * Supports MySQL (primary), SQLite (testing), and read/write splitting.
 * Connection pooling settings are tuned for a mid-sized ERP deployment.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    */

    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        /*
        |----------------------------------------------------------------------
        | MySQL - Primary (Production / Development)
        |----------------------------------------------------------------------
        */

        'mysql' => [
            'driver'         => 'mysql',
            'host'           => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'           => (int) ($_ENV['DB_PORT'] ?? 3306),
            'database'       => $_ENV['DB_DATABASE'] ?? 'bizcore_erp',
            'username'       => $_ENV['DB_USERNAME'] ?? 'bizcore',
            'password'       => $_ENV['DB_PASSWORD'] ?? '',
            'charset'        => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation'      => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => 'InnoDB',
            'timezone'       => '+06:00',   // BDT (UTC+6)

            // SSL (enable in production)
            'ssl' => [
                'enabled'   => filter_var($_ENV['DB_SSL'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'ca'        => $_ENV['DB_SSL_CA'] ?? null,
                'cert'      => $_ENV['DB_SSL_CERT'] ?? null,
                'key'       => $_ENV['DB_SSL_KEY'] ?? null,
                'verify'    => filter_var($_ENV['DB_SSL_VERIFY'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ],

            // PDO options for robustness
            'options' => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci,
                                                 time_zone = '+06:00',
                                                 sql_mode  = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'",
            ],

            // Connection pool / persistence
            'pool' => [
                'min_connections'   => (int) ($_ENV['DB_POOL_MIN'] ?? 2),
                'max_connections'   => (int) ($_ENV['DB_POOL_MAX'] ?? 20),
                'connect_timeout'   => (int) ($_ENV['DB_CONNECT_TIMEOUT'] ?? 10),
                'wait_timeout'      => (int) ($_ENV['DB_WAIT_TIMEOUT'] ?? 28800),
                'heartbeat'         => (int) ($_ENV['DB_HEARTBEAT'] ?? 60),
                'max_idle_time'     => (int) ($_ENV['DB_MAX_IDLE_TIME'] ?? 3600),
                'persistent'        => filter_var($_ENV['DB_PERSISTENT'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],

            // Read / Write splitting (configure DB_READ_HOST for a replica)
            'read' => [
                'host' => array_filter(explode(',', $_ENV['DB_READ_HOST'] ?? '')),
            ],
            'write' => [
                'host' => [$_ENV['DB_HOST'] ?? '127.0.0.1'],
            ],
            'sticky' => true,   // Use write connection for remainder of request after a write
        ],

        /*
        |----------------------------------------------------------------------
        | MySQL - Test (isolated database for PHPUnit)
        |----------------------------------------------------------------------
        */

        'mysql_testing' => [
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_TEST_HOST'] ?? '127.0.0.1',
            'port'      => (int) ($_ENV['DB_TEST_PORT'] ?? 3306),
            'database'  => $_ENV['DB_TEST_DATABASE'] ?? 'bizcore_erp_test',
            'username'  => $_ENV['DB_TEST_USERNAME'] ?? 'bizcore_test',
            'password'  => $_ENV['DB_TEST_PASSWORD'] ?? '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => 'InnoDB',
            'timezone'  => '+06:00',
            'options'   => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ],
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 5,
                'connect_timeout' => 5,
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | SQLite - Lightweight testing / offline mode
        |----------------------------------------------------------------------
        */

        'sqlite' => [
            'driver'                  => 'sqlite',
            'database'                => $_ENV['DB_SQLITE_PATH'] ?? dirname(__DIR__) . '/database/testing.sqlite',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
            'options'                 => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    */

    'migrations' => [
        'table'   => 'migrations',
        'path'    => dirname(__DIR__) . '/database/migrations',
        'seed_path' => dirname(__DIR__) . '/database/seeders',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Logging
    |--------------------------------------------------------------------------
    |
    | Enable slow-query logging in non-production environments.
    | Threshold is in milliseconds.
    |
    */

    'query_log' => [
        'enabled'           => filter_var($_ENV['DB_QUERY_LOG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'slow_query_log'    => filter_var($_ENV['DB_SLOW_QUERY_LOG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'slow_threshold_ms' => (int) ($_ENV['DB_SLOW_THRESHOLD_MS'] ?? 1000),
        'log_channel'       => 'database',
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */

    'backup' => [
        'enabled'      => filter_var($_ENV['DB_BACKUP_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'path'         => $_ENV['DB_BACKUP_PATH'] ?? dirname(__DIR__) . '/storage/backups/database',
        'keep_last'    => (int) ($_ENV['DB_BACKUP_KEEP'] ?? 30),
        'schedule'     => $_ENV['DB_BACKUP_SCHEDULE'] ?? '0 2 * * *',  // 2 AM daily
        'compress'     => true,
        'encrypt'      => filter_var($_ENV['DB_BACKUP_ENCRYPT'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],

];
