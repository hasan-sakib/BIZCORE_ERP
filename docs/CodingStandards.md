# Coding Standards — BizCore ERP

## PSR-12 + Project Conventions

All code must pass `./vendor/bin/phpcs --standard=phpcs.xml` and `./vendor/bin/phpstan analyse --level=8`.

---

## File Structure Rules

```php
<?php                          // Always first line — no BOM

declare(strict_types=1);       // Always second line

namespace App\Services;        // PSR-4 namespace matching directory

use App\Core\Database;         // Grouped, alphabetical imports
use App\Entities\Employee;
use App\Repositories\EmployeeRepository;
                               // One blank line between use groups
class EmployeeService          // PascalCase class name
{
    // ...
}
```

## Class Design

### Constructor Injection (Mandatory)
```php
// CORRECT — readonly constructor promotion
class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepository $employeeRepo,
        private readonly Database           $db,
        private readonly Cache              $cache
    ) {}
}

// WRONG — property assignment
class EmployeeService
{
    private EmployeeRepository $repo;

    public function __construct(EmployeeRepository $repo)
    {
        $this->repo = $repo;  // verbose, avoidable
    }
}
```

### Method Signatures
```php
// All parameters and return types declared
public function paginate(int $branchId, int $page, int $perPage): array
{
    // ...
}

// Nullable types for optional params
public function findByEmail(string $email): ?User
{
    // ...
}

// Union types for flexibility
private function resolveId(int|string $id): int
{
    return is_string($id) ? (int)$id : $id;
}
```

## Entities

Entities are immutable value objects. Use readonly properties and factory methods:

```php
class Employee
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $firstName,
        public readonly string  $lastName,
        public readonly ?string $email,
        // ...
    ) {}

    // Factory — always fromArray, never new Entity(col, col, ...)
    public static function fromArray(array $data): self
    {
        return new self(
            id:        (int)$data['id'],
            firstName: $data['first_name'],
            lastName:  $data['last_name'],
            email:     $data['email'] ?? null,
        );
    }

    // Serialization
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->firstName,
            // ...
        ];
    }

    // Business methods (not getters/setters for their own sake)
    public function getFullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }
}
```

## Services

Services contain business logic, NOT:
- Direct HTTP/session access
- Raw SQL (use repositories or Database class)
- View rendering

```php
class SalesService
{
    public function createInvoice(
        int     $branchId,
        int     $customerId,
        array   $items,
        int     $warehouseId,
        ?string $dueDate,
        ?string $notes,
        float   $discount,
        ?int    $orderId,
        int     $createdBy
    ): array {
        // Named parameters at call site make intent clear
        return $this->db->transaction(function () use (
            $branchId, $customerId, $items, $warehouseId, $dueDate, $notes, $discount, $orderId, $createdBy
        ) {
            // business logic
        });
    }
}
```

## Controllers

Controllers are thin orchestrators:

```php
class InvoiceController extends BaseController
{
    public function store(Request $request): void
    {
        // 1. Validate surface input
        $data = $request->all();
        if (empty($data['customer_id'])) {
            $this->withErrors(['customer_id' => 'Customer is required.'])->back();
            return;
        }

        // 2. Call service
        try {
            $invoice = $this->salesService->createInvoice(/* named args */);
            $this->success('Invoice created.')->redirect("/invoices/{$invoice['id']}");
        } catch (\Throwable $e) {
            $this->error($e->getMessage())->withInput($data)->back();
        }
    }
}
```

## Database Queries

```php
// Preferred: query builder for simple queries
$rows = $this->db
    ->table('products')
    ->where('branch_id', $branchId)
    ->where('is_active', 1)
    ->whereNull('deleted_at')
    ->orderBy('name')
    ->limit($perPage)
    ->offset($offset)
    ->get();

// Acceptable: raw query for complex JOINs/aggregates
$rows = $this->db->fetchAll(
    "SELECT p.id, p.name, SUM(i.quantity) AS stock
     FROM products p
     LEFT JOIN inventory i ON i.product_id = p.id AND i.warehouse_id = ?
     WHERE p.branch_id = ? AND p.deleted_at IS NULL
     GROUP BY p.id, p.name
     ORDER BY p.name ASC
     LIMIT {$perPage} OFFSET {$offset}",
    [$warehouseId, $branchId]
);

// FORBIDDEN: string interpolation with user data
$this->db->fetchAll("SELECT * FROM products WHERE name = '{$name}'");
```

## Comments

Write comments only when the WHY is non-obvious:

```php
// GOOD — explains non-obvious invariant
// Tax is calculated annually then divided by 12 for monthly payslips
$monthlyTax = $this->calculateAnnualTax($annualSalary) / 12;

// GOOD — documents a specific external constraint
// bKash requires amount in BDT (no decimal) — multiply by 100
$bkashAmount = (int)($amount * 100);

// BAD — describes what the code does (obvious from reading it)
// Get all active employees
$employees = $this->db->table('employees')->where('status', 'active')->get();

// BAD — PHPDoc that adds no type information beyond the signature
/**
 * Get employee by ID
 * @param int $id Employee ID
 * @return Employee|null The employee or null
 */
public function findById(int $id): ?Employee
```

## Error Handling

Domain exceptions communicate specific failure modes:

```php
// Define specific exceptions
class InsufficientStockException extends \RuntimeException {}

// Throw with context
if ($available < $requested) {
    throw new InsufficientStockException(
        "Insufficient stock for {$product->name}: requested {$requested}, available {$available}"
    );
}

// Catch specifically in controllers
try {
    $this->inventoryService->stockOut($productId, $warehouseId, $qty);
} catch (InsufficientStockException $e) {
    $this->error($e->getMessage())->back();
} catch (\Throwable $e) {
    $this->error('An unexpected error occurred.')->back();
}
```

## Testing Conventions

```php
class InventoryServiceTest extends TestCase
{
    // Test method names: test_{what}_{when}_{expected}
    public function test_stock_in_updates_weighted_average_cost(): void
    {
        // Arrange
        $this->seedProduct(['id' => 1, 'avg_cost' => 50.00]);
        $this->seedInventory(['product_id' => 1, 'quantity' => 100]);

        // Act
        $this->inventoryService->stockIn(
            productId: 1,
            warehouseId: 1,
            quantity: 50,
            unitCost: 80.00,
        );

        // Assert
        $inv = $this->db->table('inventory')->where('product_id', 1)->first();
        // (100 * 50 + 50 * 80) / 150 = 60.00
        $this->assertEqualsWithDelta(60.00, (float)$inv['avg_cost'], 0.01);
    }
}
```
