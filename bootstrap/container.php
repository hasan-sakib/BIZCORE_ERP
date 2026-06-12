<?php

declare(strict_types=1);

/**
 * BizCore ERP - Dependency Injection Container Setup
 *
 * This file:
 *  1. Defines the lightweight PSR-11–compatible Application container
 *  2. Registers all concrete bindings (interfaces → implementations)
 *  3. Registers shared singletons (DB, Cache, Session, Logger, Mailer, …)
 *  4. Registers service providers from config/app.php
 *  5. Returns the fully-configured Application instance
 *
 * The container implements Psr\Container\ContainerInterface so that any
 * PSR-11–aware library (e.g. Monolog, League packages) can resolve
 * dependencies through it.
 *
 * @package BizCore\ERP
 * @version 1.0.0
 */

namespace App\Foundation;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

// ---------------------------------------------------------------------------
// PSR-11 Exception Stubs
// (If the full PSR-11 package is pulled in via Composer this can be removed.)
// ---------------------------------------------------------------------------

if (! interface_exists(\Psr\Container\ContainerInterface::class)) {
    throw new \RuntimeException(
        'PSR-11 ContainerInterface not found. ' .
        'Run: composer require psr/container'
    );
}

// ---------------------------------------------------------------------------
// Application Container Class
// ---------------------------------------------------------------------------

if (! class_exists(Application::class)) {

    /**
     * BizCore Application Container
     *
     * Lightweight IoC / service container with:
     *  - Singleton (shared) binding
     *  - Factory (transient) binding
     *  - Contextual alias resolution
     *  - Service provider boot lifecycle
     *  - Auto-wiring for simple constructor injection (reflection-based)
     */
    final class Application implements ContainerInterface
    {
        // ── State ────────────────────────────────────────────────────────────

        /** @var Application|null Singleton application instance */
        private static ?Application $instance = null;

        /** @var array<string, callable> Factory closures */
        private array $bindings = [];

        /** @var array<string, mixed> Resolved singleton instances */
        private array $instances = [];

        /** @var array<string, string> Abstract → concrete alias map */
        private array $aliases = [];

        /** @var list<\App\Providers\ServiceProvider> Registered providers */
        private array $providers = [];

        /** @var bool Whether providers have been booted */
        private bool $booted = false;

        /** @var array<string, mixed> Loaded config files cache */
        private array $configCache = [];

        // ── Constructor ──────────────────────────────────────────────────────

        private function __construct(private readonly string $basePath) {}

        // ── Singleton accessor ───────────────────────────────────────────────

        public static function getInstance(string $basePath = ''): self
        {
            if (self::$instance === null) {
                if ($basePath === '') {
                    throw new \LogicException(
                        'Application::getInstance() called before Application was created. ' .
                        'Provide basePath on first call.'
                    );
                }
                self::$instance = new self($basePath);
            }

            return self::$instance;
        }

        /** Destroy the singleton (testing only). */
        public static function flush(): void
        {
            self::$instance = null;
        }

        // ── PSR-11 ContainerInterface ────────────────────────────────────────

        /**
         * @template T of object
         * @param  class-string<T>|string $id
         * @return T|mixed
         */
        public function get(string $id): mixed
        {
            // Resolve alias first.
            $id = $this->aliases[$id] ?? $id;

            // Return already-resolved singleton.
            if (isset($this->instances[$id])) {
                return $this->instances[$id];
            }

            // Invoke factory binding.
            if (isset($this->bindings[$id])) {
                return ($this->bindings[$id])($this);
            }

            // Attempt auto-wiring via reflection.
            if (class_exists($id)) {
                return $this->resolve($id);
            }

            throw new class("No binding registered for [{$id}].")
                extends \RuntimeException
                implements NotFoundExceptionInterface {};
        }

        public function has(string $id): bool
        {
            $id = $this->aliases[$id] ?? $id;

            return isset($this->instances[$id])
                || isset($this->bindings[$id])
                || class_exists($id);
        }

        // ── Binding API ──────────────────────────────────────────────────────

        /**
         * Register a factory binding (creates a new instance on every get()).
         */
        public function bind(string $abstract, callable $factory): void
        {
            $abstract = $this->aliases[$abstract] ?? $abstract;
            $this->bindings[$abstract] = $factory;
        }

        /**
         * Register a singleton binding (created once, cached thereafter).
         */
        public function singleton(string $abstract, callable $factory): void
        {
            $this->bind($abstract, function (Application $app) use ($abstract, $factory): mixed {
                if (! isset($this->instances[$abstract])) {
                    $this->instances[$abstract] = $factory($app);
                }
                return $this->instances[$abstract];
            });
        }

        /**
         * Register a pre-resolved instance as a singleton.
         */
        public function instance(string $abstract, mixed $concrete): void
        {
            $abstract = $this->aliases[$abstract] ?? $abstract;
            $this->instances[$abstract] = $concrete;
        }

        /**
         * Register an alias (abstract → concrete class string).
         */
        public function alias(string $abstract, string $concrete): void
        {
            $this->aliases[$abstract] = $concrete;
        }

        // ── Resolution Helpers ───────────────────────────────────────────────

        /**
         * Attempt to auto-wire a class by inspecting its constructor parameters.
         *
         * @template T of object
         * @param  class-string<T> $concrete
         * @return T
         */
        private function resolve(string $concrete): object
        {
            try {
                $reflector = new \ReflectionClass($concrete);
            } catch (\ReflectionException $e) {
                throw new class("Target class [{$concrete}] does not exist.", 0, $e)
                    extends \RuntimeException
                    implements ContainerExceptionInterface {};
            }

            if (! $reflector->isInstantiable()) {
                throw new class("Target [{$concrete}] is not instantiable.")
                    extends \RuntimeException
                    implements ContainerExceptionInterface {};
            }

            $constructor = $reflector->getConstructor();

            if ($constructor === null) {
                return new $concrete();
            }

            $dependencies = array_map(
                function (\ReflectionParameter $param) use ($concrete): mixed {
                    $type = $param->getType();

                    if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                        return $this->get($type->getName());
                    }

                    if ($param->isDefaultValueAvailable()) {
                        return $param->getDefaultValue();
                    }

                    throw new class(
                        "Cannot resolve parameter \${$param->getName()} for [{$concrete}]."
                    )
                        extends \RuntimeException
                        implements ContainerExceptionInterface {};
                },
                $constructor->getParameters()
            );

            return $reflector->newInstanceArgs($dependencies);
        }

        // ── Service Provider Lifecycle ───────────────────────────────────────

        /**
         * Register a service provider instance.
         */
        public function register(\App\Providers\ServiceProvider $provider): void
        {
            $provider->register($this);
            $this->providers[] = $provider;
        }

        /**
         * Boot all registered service providers.
         * Called once by bootstrap/app.php after all providers are registered.
         */
        public function boot(): void
        {
            if ($this->booted) {
                return;
            }

            foreach ($this->providers as $provider) {
                if (method_exists($provider, 'boot')) {
                    $provider->boot($this);
                }
            }

            $this->booted = true;
        }

        // ── Configuration helpers ────────────────────────────────────────────

        /**
         * Load (and cache) a config file, returning the value at dot-notation $key.
         */
        public function config(string $key, mixed $default = null): mixed
        {
            [$file, $rest] = array_pad(explode('.', $key, 2), 2, null);

            if (! isset($this->configCache[$file])) {
                $path = $this->basePath . '/config/' . $file . '.php';
                if (! file_exists($path)) {
                    return $default;
                }
                $this->configCache[$file] = require $path;
            }

            if ($rest === null) {
                return $this->configCache[$file] ?? $default;
            }

            return $this->dotGet($this->configCache[$file], $rest, $default);
        }

        private function dotGet(array $array, string $key, mixed $default): mixed
        {
            foreach (explode('.', $key) as $segment) {
                if (! is_array($array) || ! array_key_exists($segment, $array)) {
                    return $default;
                }
                $array = $array[$segment];
            }

            return $array;
        }

        // ── Path Helpers ─────────────────────────────────────────────────────

        public function basePath(string $path = ''): string
        {
            return $this->basePath . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
        }

        public function appPath(string $path = ''): string
        {
            return $this->basePath('app' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
        }

        public function storagePath(string $path = ''): string
        {
            return $this->basePath('storage' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
        }

        public function configPath(string $path = ''): string
        {
            return $this->basePath('config' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
        }

        public function routesPath(string $path = ''): string
        {
            return $this->basePath('routes' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
        }

        public function resourcePath(string $path = ''): string
        {
            return $this->basePath('resources' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
        }

        // ── Environment Helpers ──────────────────────────────────────────────

        public function environment(): string
        {
            return $_ENV['APP_ENV'] ?? 'local';
        }

        public function isProduction(): bool
        {
            return $this->environment() === 'production';
        }

        public function isDebug(): bool
        {
            return filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        public function isBooted(): bool
        {
            return $this->booted;
        }
    }
}

// ============================================================================
// INSTANTIATE THE APPLICATION
// ============================================================================

$app = Application::getInstance(BASE_PATH);

// Register the application with itself so it can be resolved from the container.
$app->instance(\App\Foundation\Application::class, $app);
$app->instance(\Psr\Container\ContainerInterface::class, $app);

// ============================================================================
// CORE FRAMEWORK BINDINGS
// ============================================================================

// ── Configuration ────────────────────────────────────────────────────────────
$app->singleton('config', static fn ($app) => new class($app) {
    public function __construct(private readonly Application $app) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->app->config($key, $default);
    }

    public function __invoke(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }
});

// ── Logger (Monolog) ─────────────────────────────────────────────────────────
$app->singleton('logger', static function (Application $app): \Monolog\Logger {
    $channel = $_ENV['LOG_CHANNEL'] ?? 'daily';
    $level   = \Monolog\Level::fromName(strtolower($_ENV['LOG_LEVEL'] ?? 'debug'));
    $maxFiles= (int) ($_ENV['LOG_MAX_FILES'] ?? 14);

    $logger = new \Monolog\Logger('bizcore');

    $formatter = new \Monolog\Formatter\LineFormatter(
        format:         "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
        dateFormat:     'Y-m-d H:i:s',
        allowInlineLineBreaks: true,
        ignoreEmptyContextAndExtra: true
    );

    // File handler (daily rotating).
    $fileHandler = new \Monolog\Handler\RotatingFileHandler(
        filename:   $app->storagePath('logs/bizcore.log'),
        maxFiles:   $maxFiles,
        level:      $level,
        bubble:     true,
        filePermission: 0644
    );
    $fileHandler->setFormatter($formatter);
    $logger->pushHandler($fileHandler);

    // In production also send ERROR+ to a separate error log.
    if ($app->isProduction()) {
        $errorHandler = new \Monolog\Handler\RotatingFileHandler(
            filename: $app->storagePath('logs/error.log'),
            maxFiles: $maxFiles,
            level:    \Monolog\Level::Error,
            bubble:   false
        );
        $errorHandler->setFormatter($formatter);
        $logger->pushHandler($errorHandler);
    }

    // In debug mode, send everything to STDERR as well.
    if ($app->isDebug() && ! $app->isProduction()) {
        $stderrHandler = new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Level::Debug);
        $stderrHandler->setFormatter($formatter);
        $logger->pushHandler($stderrHandler);
    }

    return $logger;
});

$app->alias(\Psr\Log\LoggerInterface::class, 'logger');
$app->alias(\Monolog\Logger::class, 'logger');

// ── Database ─────────────────────────────────────────────────────────────────
$app->singleton('db', static function (Application $app): \App\Core\Database {
    $cfg  = $app->config('database');
    $conn = $cfg['connections'][$cfg['default'] ?? 'mysql'] ?? $cfg;
    return new \App\Core\Database($conn);
});

$app->alias(\App\Core\Database::class, 'db');

// PDO binding — lets repositories that type-hint PDO be auto-wired.
$app->singleton(\PDO::class, static function (Application $app): \PDO {
    return $app->get('db')->getConnection();
});

// ── Cache ─────────────────────────────────────────────────────────────────────
$app->singleton('cache', static function (): \App\Core\Cache {
    return new \App\Core\Cache([
        'host'     => $_ENV['REDIS_HOST']     ?? '127.0.0.1',
        'port'     => (int) ($_ENV['REDIS_PORT'] ?? 6379),
        'password' => (($_ENV['REDIS_PASSWORD'] ?? 'null') !== 'null') ? $_ENV['REDIS_PASSWORD'] : null,
        'database' => (int) ($_ENV['REDIS_DB'] ?? 0),
        'prefix'   => 'bizcore:cache:',
    ]);
});

$app->alias(\App\Core\Cache::class, 'cache');

// ── Session ───────────────────────────────────────────────────────────────────
$app->singleton('session', static function (): \App\Core\Session {
    $session = new \App\Core\Session();
    $session->start();
    return $session;
});

$app->alias(\App\Core\Session::class, 'session');

// ── Request ───────────────────────────────────────────────────────────────────
$app->singleton('request', static fn () => \App\Http\Request::fromGlobals());

$app->alias(\App\Http\Request::class, 'request');

// ── Response Factory ──────────────────────────────────────────────────────────
$app->bind('response', static fn () => new \App\Http\Response());

// ── Router ────────────────────────────────────────────────────────────────────
$app->singleton('router', static function (Application $app): \App\Core\Router {
    $router = new \App\Core\Router();

    // Load web routes.
    require $app->routesPath('web.php');

    // Load API routes.
    require $app->routesPath('api.php');

    return $router;
});

$app->alias(\App\Core\Router::class, 'router');

// ── Authentication ────────────────────────────────────────────────────────────
$app->singleton('auth', static function (Application $app): \App\Core\Auth {
    return new \App\Core\Auth(
        session:        $app->get('session'),
        userRepository: $app->get(\App\Repositories\UserRepository::class),
        cache:          $app->get('cache'),
    );
});

$app->alias(\App\Core\Auth::class, 'auth');

// ── Hash Manager ──────────────────────────────────────────────────────────────
$app->singleton('hash', static fn () => new \App\Security\HashManager(
    algo:   PASSWORD_BCRYPT,
    rounds: (int) ($_ENV['BCRYPT_ROUNDS'] ?? 12)
));

// ── Encryptor ─────────────────────────────────────────────────────────────────
$app->singleton('encryptor', static function (Application $app): \App\Security\Encryptor {
    $key = $_ENV['APP_KEY'] ?? '';
    if (str_starts_with($key, 'base64:')) {
        $key = base64_decode(substr($key, 7));
    }
    return new \App\Security\Encryptor($key);
});

// ── Mailer ────────────────────────────────────────────────────────────────────
$app->singleton('mailer', static function (Application $app): \App\Mail\MailManager {
    return new \App\Mail\MailManager(
        config: $app->config('mail'),
        logger: $app->get('logger')
    );
});

// ── Storage / Filesystem ──────────────────────────────────────────────────────
$app->singleton('storage', static function (Application $app): \App\Storage\StorageManager {
    return new \App\Storage\StorageManager(
        basePath: $app->storagePath('app'),
        driver:   $_ENV['STORAGE_DRIVER'] ?? 'local',
        logger:   $app->get('logger')
    );
});

// ── Validator ─────────────────────────────────────────────────────────────────
$app->bind('validator', static fn (Application $app) => new \App\Validation\Validator(
    db: $app->get('db')
));

$app->alias(\App\Validation\Validator::class, 'validator');

// ── Queue (Redis-backed) ──────────────────────────────────────────────────────
$app->singleton('queue', static function (Application $app): \App\Queue\QueueManager {
    return new \App\Queue\QueueManager(
        redis:  $app->get('redis'),
        logger: $app->get('logger'),
        config: [
            'driver'      => $_ENV['QUEUE_DRIVER']      ?? 'redis',
            'retry_after' => (int) ($_ENV['QUEUE_RETRY_AFTER'] ?? 90),
        ]
    );
});

// ── Event Dispatcher ──────────────────────────────────────────────────────────
$app->singleton('events', static fn (Application $app) => new \App\Events\EventDispatcher($app));

// ── Rate Limiter ──────────────────────────────────────────────────────────────
$app->singleton('rate_limiter', static function (Application $app): \App\Security\RateLimiter {
    return new \App\Security\RateLimiter(
        redis:          $app->get('redis'),
        maxRequests:    (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 60),
        decaySeconds:   (int) ($_ENV['RATE_LIMIT_WINDOW']   ?? 60),
        prefix:         'bizcore:rl:'
    );
});

// ── CSRF Guard ────────────────────────────────────────────────────────────────
$app->singleton('csrf', static function (Application $app): \App\Security\CsrfGuard {
    return new \App\Security\CsrfGuard(
        session:  $app->get('session'),
        lifetime: (int) ($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600)
    );
});

// ============================================================================
// REPOSITORY BINDINGS
// (interface → concrete class; repositories are transient — new per request)
// ============================================================================

$repos = [
    // Auth & Users
    \App\Contracts\Repositories\UserRepositoryInterface::class
        => \App\Repositories\UserRepository::class,
    \App\Contracts\Repositories\RoleRepositoryInterface::class
        => \App\Repositories\RoleRepository::class,
    \App\Contracts\Repositories\PermissionRepositoryInterface::class
        => \App\Repositories\PermissionRepository::class,
    \App\Contracts\Repositories\BranchRepositoryInterface::class
        => \App\Repositories\BranchRepository::class,

    // HR
    \App\Contracts\Repositories\EmployeeRepositoryInterface::class
        => \App\Repositories\EmployeeRepository::class,
    \App\Contracts\Repositories\DepartmentRepositoryInterface::class
        => \App\Repositories\DepartmentRepository::class,
    \App\Contracts\Repositories\DesignationRepositoryInterface::class
        => \App\Repositories\DesignationRepository::class,
    \App\Contracts\Repositories\AttendanceRepositoryInterface::class
        => \App\Repositories\AttendanceRepository::class,

    // Payroll
    \App\Contracts\Repositories\SalaryStructureRepositoryInterface::class
        => \App\Repositories\SalaryStructureRepository::class,
    \App\Contracts\Repositories\PayrollRepositoryInterface::class
        => \App\Repositories\PayrollRepository::class,

    // CRM
    \App\Contracts\Repositories\CustomerRepositoryInterface::class
        => \App\Repositories\CustomerRepository::class,

    // Inventory & Products
    \App\Contracts\Repositories\ProductRepositoryInterface::class
        => \App\Repositories\ProductRepository::class,
    \App\Contracts\Repositories\CategoryRepositoryInterface::class
        => \App\Repositories\CategoryRepository::class,
    \App\Contracts\Repositories\WarehouseRepositoryInterface::class
        => \App\Repositories\WarehouseRepository::class,
    \App\Contracts\Repositories\StockRepositoryInterface::class
        => \App\Repositories\StockRepository::class,

    // Purchasing
    \App\Contracts\Repositories\SupplierRepositoryInterface::class
        => \App\Repositories\SupplierRepository::class,
    \App\Contracts\Repositories\PurchaseOrderRepositoryInterface::class
        => \App\Repositories\PurchaseOrderRepository::class,

    // Sales
    \App\Contracts\Repositories\SalesOrderRepositoryInterface::class
        => \App\Repositories\SalesOrderRepository::class,
    \App\Contracts\Repositories\InvoiceRepositoryInterface::class
        => \App\Repositories\InvoiceRepository::class,
    \App\Contracts\Repositories\QuotationRepositoryInterface::class
        => \App\Repositories\QuotationRepository::class,
    \App\Contracts\Repositories\PaymentRepositoryInterface::class
        => \App\Repositories\PaymentRepository::class,

    // Expenses
    \App\Contracts\Repositories\ExpenseRepositoryInterface::class
        => \App\Repositories\ExpenseRepository::class,

    // Accounting
    \App\Contracts\Repositories\AccountRepositoryInterface::class
        => \App\Repositories\AccountRepository::class,
    \App\Contracts\Repositories\JournalRepositoryInterface::class
        => \App\Repositories\JournalRepository::class,

    // Reports
    \App\Contracts\Repositories\ReportRepositoryInterface::class
        => \App\Repositories\ReportRepository::class,

    // Notifications
    \App\Contracts\Repositories\NotificationRepositoryInterface::class
        => \App\Repositories\NotificationRepository::class,
];

foreach ($repos as $interface => $concrete) {
    $app->bind($interface, static fn (Application $app) => $app->get($concrete));
}

// ============================================================================
// SERVICE BINDINGS
// (Domain services — transient; each resolution creates a new instance)
// ============================================================================

$services = [
    // Auth
    \App\Contracts\Services\AuthServiceInterface::class
        => \App\Services\AuthService::class,

    // HR
    \App\Contracts\Services\EmployeeServiceInterface::class
        => \App\Services\EmployeeService::class,
    \App\Contracts\Services\AttendanceServiceInterface::class
        => \App\Services\AttendanceService::class,

    // Payroll
    \App\Contracts\Services\PayrollServiceInterface::class
        => \App\Services\PayrollService::class,

    // CRM
    \App\Contracts\Services\CustomerServiceInterface::class
        => \App\Services\CustomerService::class,

    // Inventory
    \App\Contracts\Services\ProductServiceInterface::class
        => \App\Services\ProductService::class,
    \App\Contracts\Services\InventoryServiceInterface::class
        => \App\Services\InventoryService::class,
    \App\Contracts\Services\StockTransferServiceInterface::class
        => \App\Services\StockTransferService::class,

    // Purchasing
    \App\Contracts\Services\PurchaseServiceInterface::class
        => \App\Services\PurchaseService::class,

    // Sales
    \App\Contracts\Services\SalesServiceInterface::class
        => \App\Services\SalesService::class,
    \App\Contracts\Services\InvoiceServiceInterface::class
        => \App\Services\InvoiceService::class,

    // Expenses
    \App\Contracts\Services\ExpenseServiceInterface::class
        => \App\Services\ExpenseService::class,

    // Accounting
    \App\Contracts\Services\AccountingServiceInterface::class
        => \App\Services\AccountingService::class,
    \App\Contracts\Services\ReportingServiceInterface::class
        => \App\Services\ReportingService::class,

    // Notifications
    \App\Contracts\Services\NotificationServiceInterface::class
        => \App\Services\NotificationService::class,

    // PDF Generation
    \App\Contracts\Services\PdfServiceInterface::class
        => \App\Services\PdfService::class,

    // Excel Export
    \App\Contracts\Services\ExcelServiceInterface::class
        => \App\Services\ExcelService::class,
];

foreach ($services as $interface => $concrete) {
    $app->bind($interface, static fn (Application $app) => $app->get($concrete));
}

// ============================================================================
// REGISTER SERVICE PROVIDERS
// ============================================================================

$providerClasses = $app->config('app.providers') ?? [];

foreach ($providerClasses as $providerClass) {
    if (! class_exists($providerClass)) {
        // Non-fatal: log and skip missing module providers (module may be disabled).
        if ($app->has('logger')) {
            $app->get('logger')->warning(
                "Service provider [{$providerClass}] not found; skipping registration."
            );
        }
        continue;
    }

    $app->register(new $providerClass());
}

// ============================================================================
// CLASS ALIASES (Facade-style short names)
// ============================================================================

$aliases = $app->config('app.aliases') ?? [];

foreach ($aliases as $alias => $concrete) {
    $app->alias($alias, $concrete);
}

// ============================================================================
// EXPLICIT BINDINGS FOR CLASSES WITH ARRAY CONFIG DEPENDENCIES
// (auto-wiring cannot resolve primitive/array constructor params)
// ============================================================================

// Predis client — used by AuthService for token blacklisting.
$app->singleton(\Predis\Client::class, static function (): \Predis\Client {
    $password = ($_ENV['REDIS_PASSWORD'] ?? 'null') !== 'null' ? ($_ENV['REDIS_PASSWORD'] ?? null) : null;
    return new \Predis\Client([
        'scheme'   => 'tcp',
        'host'     => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port'     => (int) ($_ENV['REDIS_PORT'] ?? 6379),
        'password' => $password,
        'database' => (int) ($_ENV['REDIS_DB'] ?? 0),
    ]);
});

$app->alias('redis', \Predis\Client::class);

// MailService — requires mail config array.
$app->singleton(\App\Services\MailService::class, static function (Application $app): \App\Services\MailService {
    return new \App\Services\MailService(
        mailConfig: $app->config('mail'),
        logger:     $app->get('logger'),
    );
});

// AuthService — requires config arrays and Redis client.
$app->singleton(\App\Services\AuthService::class, static function (Application $app): \App\Services\AuthService {
    return new \App\Services\AuthService(
        userRepository: $app->get(\App\Repositories\UserRepository::class),
        mailService:    $app->get(\App\Services\MailService::class),
        redis:          $app->get(\Predis\Client::class),
        jwtConfig:      $app->config('jwt'),
        appConfig:      $app->config('app'),
    );
});

$app->bind(\App\Controllers\Auth\ProfileController::class, static fn ($app) =>
    new \App\Controllers\Auth\ProfileController(
        $app->get(\App\Services\UserService::class),
    )
);

// LoginController / AuthController — requires AuthService and app config array.
$app->bind(\App\Controllers\Auth\LoginController::class, static function (Application $app): \App\Controllers\Auth\LoginController {
    return new \App\Controllers\Auth\LoginController(
        authService: $app->get(\App\Services\AuthService::class),
        appConfig:   $app->config('app'),
    );
});

$app->bind(\App\Controllers\Auth\AuthController::class, static function (Application $app): \App\Controllers\Auth\AuthController {
    return new \App\Controllers\Auth\AuthController(
        authService: $app->get(\App\Services\AuthService::class),
        appConfig:   $app->config('app'),
    );
});

// RegisterController — requires UserRepository.
$app->bind(\App\Controllers\Auth\RegisterController::class, static fn (Application $app) =>
    new \App\Controllers\Auth\RegisterController(
        userRepository: $app->get(\App\Repositories\UserRepository::class),
    )
);

// OAuthController — requires services config array and UserRepository.
$app->bind(\App\Controllers\Auth\OAuthController::class, static fn (Application $app) =>
    new \App\Controllers\Auth\OAuthController(
        servicesConfig: $app->config('services') ?? [],
        userRepository: $app->get(\App\Repositories\UserRepository::class),
        cache:          $app->get(\App\Core\Cache::class),
    )
);

// ============================================================================
// Repositories — all PDO-injected
// ============================================================================

$app->bind(\App\Repositories\CatalogRepository::class,    static fn ($app) => new \App\Repositories\CatalogRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\ProductRepository::class,    static fn ($app) => new \App\Repositories\ProductRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\CustomerRepository::class,   static fn ($app) => new \App\Repositories\CustomerRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\SupplierRepository::class,   static fn ($app) => new \App\Repositories\SupplierRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\HRRepository::class,         static fn ($app) => new \App\Repositories\HRRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\EmployeeRepository::class,   static fn ($app) => new \App\Repositories\EmployeeRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\AttendanceRepository::class, static fn ($app) => new \App\Repositories\AttendanceRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\InventoryRepository::class,  static fn ($app) => new \App\Repositories\InventoryRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\ExpenseRepository::class,    static fn ($app) => new \App\Repositories\ExpenseRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\SalesRepository::class,      static fn ($app) => new \App\Repositories\SalesRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\PurchasingRepository::class, static fn ($app) => new \App\Repositories\PurchasingRepository($app->get(\PDO::class)));

// UserService — requires storagePath scalar resolved from STORAGE_PATH constant.
$app->singleton(\App\Services\UserService::class, static fn ($app) =>
    new \App\Services\UserService(
        userRepository: $app->get(\App\Repositories\UserRepository::class),
        authService:    $app->get(\App\Services\AuthService::class),
        mailService:    $app->get(\App\Services\MailService::class),
        logger:         $app->get(\Psr\Log\LoggerInterface::class),
        storagePath:    STORAGE_PATH . '/app/public',
    )
);

// ============================================================================
// Controllers — Users & Roles & Branches
// ============================================================================

$app->bind(\App\Controllers\Users\UserController::class, static fn ($app) =>
    new \App\Controllers\Users\UserController(
        $app->get(\App\Services\UserService::class),
        $app->get(\App\Services\RoleService::class),
        $app->get(\App\Repositories\UserRepository::class),
    )
);

$app->bind(\App\Controllers\Users\RoleController::class, static fn ($app) =>
    new \App\Controllers\Users\RoleController(
        $app->get(\App\Services\RoleService::class),
        $app->get(\App\Repositories\RoleRepository::class),
    )
);

$app->bind(\App\Controllers\Branches\BranchController::class, static fn ($app) =>
    new \App\Controllers\Branches\BranchController(
        $app->get(\App\Services\BranchService::class),
        $app->get(\App\Repositories\BranchRepository::class),
    )
);

// ============================================================================
// Controllers — Inventory / Products
// ============================================================================

$app->bind(\App\Controllers\Inventory\CategoryController::class, static fn ($app) =>
    new \App\Controllers\Inventory\CategoryController($app->get(\App\Repositories\CatalogRepository::class))
);
$app->bind(\App\Controllers\Inventory\BrandController::class, static fn ($app) =>
    new \App\Controllers\Inventory\BrandController($app->get(\App\Repositories\CatalogRepository::class))
);
$app->bind(\App\Controllers\Inventory\UnitController::class, static fn ($app) =>
    new \App\Controllers\Inventory\UnitController($app->get(\App\Repositories\CatalogRepository::class))
);
$app->bind(\App\Controllers\Inventory\ProductController::class, static fn ($app) =>
    new \App\Controllers\Inventory\ProductController(
        $app->get(\App\Repositories\ProductRepository::class),
        $app->get(\App\Repositories\CatalogRepository::class),
    )
);
$app->bind(\App\Controllers\Inventory\WarehouseController::class, static fn ($app) =>
    new \App\Controllers\Inventory\WarehouseController($app->get(\App\Repositories\InventoryRepository::class))
);
$app->bind(\App\Controllers\Inventory\StockInController::class, static fn ($app) =>
    new \App\Controllers\Inventory\StockInController($app->get(\App\Repositories\InventoryRepository::class))
);
$app->bind(\App\Controllers\Inventory\StockOutController::class, static fn ($app) =>
    new \App\Controllers\Inventory\StockOutController($app->get(\App\Repositories\InventoryRepository::class))
);
$app->bind(\App\Controllers\Inventory\StockTransferController::class, static fn ($app) =>
    new \App\Controllers\Inventory\StockTransferController($app->get(\App\Repositories\InventoryRepository::class))
);
$app->bind(\App\Controllers\Inventory\StockAdjustmentController::class, static fn ($app) =>
    new \App\Controllers\Inventory\StockAdjustmentController($app->get(\App\Repositories\InventoryRepository::class))
);

// ============================================================================
// Controllers — CRM & Purchasing
// ============================================================================

$app->bind(\App\Controllers\CRM\CustomerController::class, static fn ($app) =>
    new \App\Controllers\CRM\CustomerController($app->get(\App\Repositories\CustomerRepository::class))
);
$app->bind(\App\Controllers\Purchasing\SupplierController::class, static fn ($app) =>
    new \App\Controllers\Purchasing\SupplierController($app->get(\App\Repositories\SupplierRepository::class))
);
$app->bind(\App\Controllers\Purchasing\PurchaseOrderController::class, static fn ($app) =>
    new \App\Controllers\Purchasing\PurchaseOrderController($app->get(\App\Repositories\PurchasingRepository::class))
);
$app->bind(\App\Controllers\Purchasing\GoodsReceiptController::class, static fn ($app) =>
    new \App\Controllers\Purchasing\GoodsReceiptController($app->get(\App\Repositories\PurchasingRepository::class))
);

// ============================================================================
// Controllers — HR
// ============================================================================

$app->bind(\App\Controllers\HR\DepartmentController::class, static fn ($app) =>
    new \App\Controllers\HR\DepartmentController($app->get(\App\Repositories\HRRepository::class))
);
$app->bind(\App\Controllers\HR\DesignationController::class, static fn ($app) =>
    new \App\Controllers\HR\DesignationController($app->get(\App\Repositories\HRRepository::class))
);
$app->bind(\App\Controllers\HR\EmployeeController::class, static fn ($app) =>
    new \App\Controllers\HR\EmployeeController(
        $app->get(\App\Repositories\EmployeeRepository::class),
        $app->get(\App\Repositories\HRRepository::class),
    )
);
$app->bind(\App\Controllers\HR\AttendanceController::class, static fn ($app) =>
    new \App\Controllers\HR\AttendanceController(
        $app->get(\App\Repositories\AttendanceRepository::class),
        $app->get(\App\Repositories\EmployeeRepository::class),
    )
);

// ============================================================================
// Controllers — Expenses
// ============================================================================

$app->bind(\App\Controllers\Expenses\ExpenseCategoryController::class, static fn ($app) =>
    new \App\Controllers\Expenses\ExpenseCategoryController($app->get(\App\Repositories\ExpenseRepository::class))
);
$app->bind(\App\Controllers\Expenses\ExpenseController::class, static fn ($app) =>
    new \App\Controllers\Expenses\ExpenseController($app->get(\App\Repositories\ExpenseRepository::class))
);

// ============================================================================
// Controllers — Settings
// ============================================================================

$app->bind(\App\Controllers\Settings\SettingsController::class, static fn ($app) =>
    new \App\Controllers\Settings\SettingsController()
);

// ============================================================================
// Controllers — Sales
// ============================================================================

$app->bind(\App\Controllers\Sales\InvoiceController::class, static fn ($app) =>
    new \App\Controllers\Sales\InvoiceController(
        $app->get(\App\Repositories\SalesRepository::class),
        $app->get(\App\Repositories\CustomerRepository::class),
        $app->get(\App\Repositories\ProductRepository::class),
    )
);
$app->bind(\App\Controllers\Sales\QuotationController::class, static fn ($app) =>
    new \App\Controllers\Sales\QuotationController(
        $app->get(\App\Repositories\SalesRepository::class),
        $app->get(\App\Repositories\CustomerRepository::class),
        $app->get(\App\Repositories\ProductRepository::class),
    )
);
$app->bind(\App\Controllers\Sales\SalesOrderController::class, static fn ($app) =>
    new \App\Controllers\Sales\SalesOrderController(
        $app->get(\App\Repositories\SalesRepository::class),
        $app->get(\App\Repositories\CustomerRepository::class),
        $app->get(\App\Repositories\ProductRepository::class),
    )
);
$app->bind(\App\Controllers\Sales\PaymentController::class, static fn ($app) =>
    new \App\Controllers\Sales\PaymentController($app->get(\App\Repositories\SalesRepository::class))
);

// ============================================================================
// Controllers — Accounting
// ============================================================================

$app->bind(\App\Repositories\AccountRepository::class,  static fn ($app) => new \App\Repositories\AccountRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\JournalRepository::class,  static fn ($app) => new \App\Repositories\JournalRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\PayrollRepository::class,  static fn ($app) => new \App\Repositories\PayrollRepository($app->get(\PDO::class)));
$app->bind(\App\Repositories\NotificationRepository::class, static fn ($app) => new \App\Repositories\NotificationRepository($app->get(\PDO::class)));

$app->bind(\App\Controllers\Accounting\ChartOfAccountsController::class, static fn ($app) =>
    new \App\Controllers\Accounting\ChartOfAccountsController($app->get(\App\Repositories\AccountRepository::class))
);
$app->bind(\App\Controllers\Accounting\JournalController::class, static fn ($app) =>
    new \App\Controllers\Accounting\JournalController(
        $app->get(\App\Repositories\JournalRepository::class),
        $app->get(\App\Repositories\AccountRepository::class),
    )
);
$app->bind(\App\Controllers\Accounting\LedgerController::class, static fn ($app) =>
    new \App\Controllers\Accounting\LedgerController(
        $app->get(\App\Repositories\AccountRepository::class),
        $app->get(\App\Repositories\JournalRepository::class),
    )
);
$app->bind(\App\Controllers\Accounting\TrialBalanceController::class, static fn ($app) =>
    new \App\Controllers\Accounting\TrialBalanceController($app->get(\App\Repositories\AccountRepository::class))
);
$app->bind(\App\Controllers\Accounting\FinancialStatementController::class, static fn ($app) =>
    new \App\Controllers\Accounting\FinancialStatementController($app->get(\App\Repositories\AccountRepository::class))
);
$app->bind(\App\Controllers\Accounting\ReconciliationController::class, static fn ($app) =>
    new \App\Controllers\Accounting\ReconciliationController()
);
$app->bind(\App\Controllers\Accounting\CostCenterController::class, static fn ($app) =>
    new \App\Controllers\Accounting\CostCenterController()
);

// ============================================================================
// Controllers — Reports
// ============================================================================

$app->bind(\App\Controllers\Reports\ReportController::class, static fn ($app) =>
    new \App\Controllers\Reports\ReportController()
);
$app->bind(\App\Controllers\Reports\VatReportController::class, static fn ($app) =>
    new \App\Controllers\Reports\VatReportController()
);

// ============================================================================
// Controllers — Notifications
// ============================================================================

$app->bind(\App\Controllers\NotificationController::class, static fn ($app) =>
    new \App\Controllers\NotificationController($app->get(\App\Repositories\NotificationRepository::class))
);

// ============================================================================
// Controllers — Payroll
// ============================================================================

$app->bind(\App\Controllers\Payroll\SalaryStructureController::class, static fn ($app) =>
    new \App\Controllers\Payroll\SalaryStructureController(
        $app->get(\App\Repositories\PayrollRepository::class),
        $app->get(\App\Repositories\EmployeeRepository::class),
    )
);
$app->bind(\App\Controllers\Payroll\AllowanceController::class, static fn ($app) =>
    new \App\Controllers\Payroll\AllowanceController($app->get(\App\Repositories\PayrollRepository::class))
);
$app->bind(\App\Controllers\Payroll\DeductionController::class, static fn ($app) =>
    new \App\Controllers\Payroll\DeductionController($app->get(\App\Repositories\PayrollRepository::class))
);
$app->bind(\App\Controllers\Payroll\PayrollProcessingController::class, static fn ($app) =>
    new \App\Controllers\Payroll\PayrollProcessingController($app->get(\App\Repositories\PayrollRepository::class))
);
$app->bind(\App\Controllers\Payroll\PayslipController::class, static fn ($app) =>
    new \App\Controllers\Payroll\PayslipController($app->get(\App\Repositories\PayrollRepository::class))
);
$app->bind(\App\Controllers\Payroll\PayrollReportController::class, static fn ($app) =>
    new \App\Controllers\Payroll\PayrollReportController($app->get(\App\Repositories\PayrollRepository::class))
);

// ============================================================================
// Controllers — Settings (additional)
// ============================================================================

$app->bind(\App\Controllers\Settings\BackupController::class, static fn ($app) =>
    new \App\Controllers\Settings\BackupController()
);
$app->bind(\App\Controllers\Settings\NumberingController::class, static fn ($app) =>
    new \App\Controllers\Settings\NumberingController()
);
$app->bind(\App\Controllers\Settings\CurrencyController::class, static fn ($app) =>
    new \App\Controllers\Settings\CurrencyController()
);
$app->bind(\App\Controllers\Settings\ModuleController::class, static fn ($app) =>
    new \App\Controllers\Settings\ModuleController()
);
$app->bind(\App\Controllers\Settings\PaymentMethodController::class, static fn ($app) =>
    new \App\Controllers\Settings\PaymentMethodController()
);

// ============================================================================
// Return the configured Application instance to bootstrap/app.php
// ============================================================================

return $app;
