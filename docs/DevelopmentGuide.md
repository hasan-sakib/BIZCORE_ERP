# Development Guide — BizCore ERP

## Prerequisites

- PHP 8.3 (with `pdo_mysql`, `redis`, `gd`, `mbstring`, `xml`, `zip` extensions)
- Docker Desktop 4.x+
- Composer 2.x
- Make
- Git

## Setup

```bash
git clone https://github.com/your-org/bizcore-erp.git
cd bizcore-erp
cp .env.example .env
make up
make migrate
make seed
```

## Available Make Commands

```bash
make up           # docker compose up -d --build
make down         # docker compose down
make restart      # docker compose restart
make logs         # docker compose logs -f
make shell        # bash shell inside PHP container
make migrate      # Run pending migrations
make rollback     # Rollback last migration batch
make fresh        # Drop all tables and re-migrate
make seed         # Run DatabaseSeeder
make seed-demo    # Run DemoDataSeeder
make test         # Run PHPUnit test suite
make stan         # Run PHPStan level 8
make cs           # Run PHP_CodeSniffer
make fix          # Auto-fix CS issues with phpcbf
make tinker       # Interactive PHP REPL in container
```

## Debugging

Xdebug is pre-installed in the `development` Docker stage. To connect:

**VS Code** `.vscode/launch.json`:
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
      }
    }
  ]
}
```

Set `XDEBUG_MODE=debug` in `.env` to enable.

## Adding a New Module

### 1. Database Migration

Create `database/migrations/0XX_create_my_table.php`:

```php
<?php
class CreateMyTable {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE my_entities (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                branch_id   INT UNSIGNED NOT NULL,
                name        VARCHAR(255) NOT NULL,
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                created_by  INT UNSIGNED NULL,
                updated_by  INT UNSIGNED NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at  TIMESTAMP NULL,
                INDEX idx_branch (branch_id),
                INDEX idx_active (is_active, deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS my_entities");
    }
}
```

### 2. Entity

Create `app/Entities/MyEntity.php`:

```php
<?php
declare(strict_types=1);

namespace App\Entities;

class MyEntity {
    public function __construct(
        public readonly int     $id,
        public readonly int     $branchId,
        public readonly string  $name,
        public readonly bool    $isActive,
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            id:       (int)$data['id'],
            branchId: (int)$data['branch_id'],
            name:     $data['name'],
            isActive: (bool)$data['is_active'],
        );
    }

    public function toArray(): array {
        return [
            'id'        => $this->id,
            'branch_id' => $this->branchId,
            'name'      => $this->name,
            'is_active' => $this->isActive,
        ];
    }
}
```

### 3. Repository

```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Entities\MyEntity;

class MyEntityRepository extends BaseRepository {
    protected string $table = 'my_entities';

    public function __construct(Database $db) {
        parent::__construct($db);
    }

    public function findById(int $id): ?MyEntity {
        $row = $this->db->table($this->table)->where('id', $id)->whereNull('deleted_at')->first();
        return $row ? MyEntity::fromArray($row) : null;
    }

    public function paginate(int $branchId, int $page, int $perPage): array {
        $offset = ($page - 1) * $perPage;
        $total  = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE branch_id = ? AND deleted_at IS NULL",
            [$branchId]
        );
        $rows   = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE branch_id = ? AND deleted_at IS NULL ORDER BY name ASC LIMIT {$perPage} OFFSET {$offset}",
            [$branchId]
        );
        return ['total' => $total, 'data' => $rows];
    }
}
```

### 4. Service

```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\MyEntityRepository;

class MyEntityService {
    public function __construct(private readonly MyEntityRepository $repo) {}

    public function paginate(int $branchId, int $page, int $perPage): array {
        return $this->repo->paginate($branchId, $page, $perPage);
    }

    public function create(array $data): \App\Entities\MyEntity {
        // Validate, apply defaults, insert
    }
}
```

### 5. Controller

```php
<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use App\Http\Request;
use App\Services\MyEntityService;

class MyEntityApiController extends BaseApiController {
    public function __construct(private readonly MyEntityService $service) {}

    public function index(Request $request): void {
        [$page, $perPage] = array_values($this->getPaginationParams($request));
        $result = $this->service->paginate($this->getBranchId($request), $page, $perPage);
        $this->paginated([
            'data'       => $result['data'],
            'pagination' => paginate($result['total'], $page, $perPage),
        ]);
    }
}
```

### 6. Register Route

In `routes/api.php`:
```php
$router->group(['prefix' => '/api/v1', 'middleware' => ['jwt']], function ($r) {
    $r->get('/my-entities',     [MyEntityApiController::class, 'index'],   [], 'api.my_entities.index');
    $r->post('/my-entities',    [MyEntityApiController::class, 'store'],   [], 'api.my_entities.store');
    $r->get('/my-entities/{id}',[MyEntityApiController::class, 'show'],    [], 'api.my_entities.show');
    $r->put('/my-entities/{id}',[MyEntityApiController::class, 'update'],  [], 'api.my_entities.update');
    $r->delete('/my-entities/{id}',[MyEntityApiController::class,'destroy'],[],'api.my_entities.destroy');
});
```

### 7. Write Tests

```php
<?php
// tests/Unit/MyEntityServiceTest.php

class MyEntityServiceTest extends TestCase {
    public function test_can_create_entity(): void {
        $service = $this->app->make(MyEntityService::class);
        $entity  = $service->create([
            'branch_id' => 1,
            'name'      => 'Test Entity',
        ]);
        $this->assertInstanceOf(MyEntity::class, $entity);
        $this->assertEquals('Test Entity', $entity->name);
    }
}
```

## Common Patterns

### Caching
```php
// Cache for 5 minutes, auto-invalidate on data change
$data = $this->cache->remember("my_key_{$id}", 300, fn() => $this->db->fetchAll($query));

// Invalidate
$this->cache->forget("my_key_{$id}");
```

### Transactions
```php
$this->db->transaction(function () use ($data) {
    $id = $this->db->table('invoices')->insert($header);
    foreach ($data['items'] as $item) {
        $this->db->table('invoice_items')->insert(array_merge($item, ['invoice_id' => $id]));
    }
    // Exception here → auto-rollback
});
```

### Soft Deletes
```php
// Always filter deleted records
$this->db->table('products')->where('id', $id)->whereNull('deleted_at')->first();

// Soft delete
$this->db->table('products')->where('id', $id)->update([
    'deleted_at' => now(),
    'updated_at' => now(),
]);
```

## Code Style

Run before committing:
```bash
./vendor/bin/phpcs --standard=phpcs.xml app/
./vendor/bin/phpstan analyse --level=8 app/
```

Key conventions:
- `declare(strict_types=1)` in every file
- Return types declared on every method
- `readonly` constructor promotion for entities and DTOs
- Named arguments for clarity when calling services
- No `public` properties on service/controller classes (use `private readonly`)
- PHPDoc only when types cannot be inferred
