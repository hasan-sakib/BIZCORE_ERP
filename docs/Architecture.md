# Architecture Guide — BizCore ERP

## Overview

BizCore ERP follows Clean Architecture principles, separating concerns across four distinct layers. Each layer has a single responsibility and dependencies only flow inward (toward the domain).

```
┌─────────────────────────────────────┐
│  Presentation Layer                 │
│  Controllers (Web, API), Views      │
├─────────────────────────────────────┤
│  Application Layer                  │
│  Services, DTOs, Validators         │
├─────────────────────────────────────┤
│  Domain Layer                       │
│  Entities, Business Rules, Events   │
├─────────────────────────────────────┤
│  Infrastructure Layer               │
│  Repositories, Cache, Mail, PDF     │
└─────────────────────────────────────┘
```

## Layer Responsibilities

### Presentation Layer (`app/Controllers/`, `resources/views/`)

- Receives HTTP requests
- Validates input at surface level (required fields, types)
- Calls Application Services
- Returns responses (HTML views or JSON)
- No business logic — only orchestration

**Controllers:**
- `app/Controllers/` — Web controllers (session-based auth, HTML responses)
- `app/Controllers/Api/V1/` — API controllers (JWT auth, JSON responses)
- `app/Controllers/Auth/` — Authentication controllers

### Application Layer (`app/Services/`, `app/DTOs/`)

- Implements use cases / application workflows
- Orchestrates domain entities and infrastructure
- Handles cross-cutting concerns (caching, notifications)
- Contains transaction boundaries

**Key Services:**
- `AuthService` — Login, logout, password reset, JWT generation
- `EmployeeService` — Employee CRUD, transfers, employee number generation
- `InventoryService` — Stock in/out/adjust/transfer, weighted average cost
- `PayrollService` — Monthly payroll processing, Bangladesh tax calculation
- `SalesService` — Order/invoice creation, FIFO payment allocation
- `AccountingService` — Double-entry posting, financial statements
- `ReportService` — Dashboard metrics, cached aggregations

### Domain Layer (`app/Entities/`)

- Pure PHP objects with no framework dependencies
- Encapsulate business rules and invariants
- Constructed via `fromArray()` factory, never by `new Entity(col, col, ...)`
- Immutable by default (readonly constructor promotion)

**Key Entities:**
- `User` — Authentication identity
- `Employee` — HR entity with business methods (getFullName, getAge, etc.)
- `Product` — Inventory item with VAT and costing attributes
- `Invoice` — Sales document with isOverdue(), isPaid() logic
- `JournalEntry` — Accounting entry with isBalanced() validation

### Infrastructure Layer (`app/Repositories/`, `app/Core/`)

- Implements persistence abstractions
- No business logic — only data access patterns
- Database, cache, mail, file storage adapters

**Core Infrastructure:**
- `app/Core/Application.php` — PSR-11 IoC container with auto-wiring
- `app/Core/Database.php` — PDO wrapper with fluent query builder
- `app/Core/Cache.php` — Predis wrapper with remember/forget pattern
- `app/Core/Router.php` — HTTP router with named routes and middleware
- `app/Core/Auth.php` — JWT generation/validation/blacklisting
- `app/Repositories/BaseRepository.php` — Common CRUD operations

## Request Lifecycle

```
HTTP Request
    ↓
public/index.php          ← Bootstrap: load config, create Application, start session
    ↓
Router::dispatch()         ← Match route, resolve middleware pipeline
    ↓
Middleware (Auth, CSRF, Rate Limit, JWT)
    ↓
Controller::action()       ← Validate surface input
    ↓
Service::method()          ← Business logic, DB transactions
    ↓
Repository::query()        ← PDO prepared statement
    ↓
Database → MySQL           ← Execute query
    ↓
Entity::fromArray()        ← Hydrate domain object
    ↓
Controller returns         ← view() or json()
    ↓
HTTP Response
```

## Dependency Injection

The `Application` class implements PSR-11 with auto-wiring:

```php
// Bind a concrete implementation
$app->bind(UserRepositoryInterface::class, UserRepository::class);

// Register as singleton
$app->singleton(Database::class, fn($app) => new Database($app->make('config.database')));

// Auto-wiring: resolves constructor parameters via ReflectionClass
$controller = $app->make(UserController::class);
// → sees UserController requires UserService
// → sees UserService requires UserRepository, Database
// → resolves the full dependency graph automatically
```

## Database Layer

All database access goes through `app/Core/Database.php`:

```php
// Fluent query builder
$users = $db->table('users')
    ->where('branch_id', $branchId)
    ->where('is_active', 1)
    ->whereNull('deleted_at')
    ->orderBy('name')
    ->limit(20)
    ->offset(0)
    ->get();

// Raw queries for complex JOINs/aggregates
$metrics = $db->fetchAll(
    "SELECT p.name, SUM(ii.quantity) AS qty_sold
     FROM invoice_items ii JOIN products p ON p.id = ii.product_id
     WHERE ii.created_at > ? GROUP BY p.id ORDER BY qty_sold DESC",
    [$fromDate]
);

// Transactions
$db->transaction(function () use ($db, $data) {
    $id = $db->table('invoices')->insert($data['header']);
    foreach ($data['items'] as $item) {
        $db->table('invoice_items')->insert(array_merge($item, ['invoice_id' => $id]));
    }
    // Rollback automatically on exception
});
```

## Caching Strategy

```
Redis key patterns:
  dashboard_metrics_{branchId}    → TTL 300s  (5 min)
  permissions_{userId}            → TTL 300s
  product_barcode_{md5(code)}     → TTL 3600s (1 hr)
  jwt_blacklist:{tokenHash}       → TTL = remaining JWT lifetime
  rate_limit:{ip}:{path}          → TTL = window size
```

The `Cache::remember()` pattern avoids cache stampedes:

```php
$value = $cache->remember('key', 300, function () {
    return $this->db->fetchAll($expensiveQuery);
});
```

## Middleware Pipeline

Routes are matched first, then middleware is applied left-to-right:

```php
$router->group(['prefix' => '/api/v1', 'middleware' => ['jwt', 'rate_limit:api']], function ($r) {
    $r->get('/products', [ProductApiController::class, 'index'], [], 'api.products.index');
});
```

Middleware classes:
- `AuthMiddleware` — Session auth for web routes
- `JwtMiddleware` — Bearer token validation for API routes
- `CsrfMiddleware` — CSRF token check (skips /api/*, /webhook/*)
- `RateLimitMiddleware` — Redis sliding window rate limiting

## File Structure

```
bizcore-erp/
├── app/
│   ├── Controllers/          # Presentation layer
│   │   ├── Auth/
│   │   ├── Api/
│   │   │   ├── BaseApiController.php
│   │   │   └── V1/
│   │   └── [Module]Controller.php
│   ├── Core/                 # Framework core
│   │   ├── Application.php
│   │   ├── Auth.php
│   │   ├── BaseController.php
│   │   ├── Cache.php
│   │   ├── Database.php
│   │   ├── Middleware/
│   │   ├── Permissions.php
│   │   ├── Router.php
│   │   ├── Session.php
│   │   └── Validator.php
│   ├── DTOs/                 # Data Transfer Objects
│   ├── Entities/             # Domain entities
│   ├── Exceptions/           # Domain exceptions
│   ├── Helpers/              # Global helper functions
│   ├── Http/                 # Request/Response wrappers
│   ├── Middleware/           # App-level middleware
│   ├── Repositories/         # Infrastructure (data access)
│   └── Services/             # Application layer
├── bootstrap/                # App bootstrap
├── config/                   # Configuration files
├── database/
│   ├── migrations/           # Ordered schema files
│   ├── seeders/              # Seed data
│   ├── migrate.php
│   └── seed.php
├── docker/                   # Docker configs
├── docs/                     # Documentation
├── public/                   # Web root
│   ├── assets/
│   └── index.php
├── resources/
│   ├── lang/                 # Translations (en, bn)
│   └── views/                # PHP view templates
├── routes/
│   ├── api.php
│   └── web.php
├── tests/                    # PHPUnit tests
└── docker-compose.yml
```
