<?php

declare(strict_types=1);

namespace Tests;

use App\DTOs\CreateUserDTO;
use App\Entities\User;
use App\Entities\UserStatus;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for BizCore ERP.
 *
 * Provides:
 *  - SQLite in-memory database setup and schema bootstrapping
 *  - Per-test table truncation for isolation
 *  - Factory helpers: createUser(), createEmployee(), createProduct(), createBranch()
 *  - Auth helpers: actingAs(), actingAsAdmin()
 *  - JSON assertion helpers: assertSuccessResponse(), assertErrorResponse()
 *  - Database assertion helpers: assertDatabaseHas(), assertSoftDeleted()
 */
abstract class TestCase extends BaseTestCase
{
    protected PDO $db;

    /** Tracks the authenticated user for feature tests. */
    protected ?array $authContext = null;

    /** Auto-increment counters per entity type to ensure unique values. */
    private static int $userSeq     = 0;
    private static int $branchSeq   = 0;
    private static int $productSeq  = 0;
    private static int $employeeSeq = 0;

    // =========================================================================
    // PHPUnit lifecycle
    // =========================================================================

    /**
     * Called once before any test in the suite.
     * Runs the full schema so all tables exist before tests start.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Counters reset per test class to keep IDs predictable
        self::$userSeq     = 0;
        self::$branchSeq   = 0;
        self::$productSeq  = 0;
        self::$employeeSeq = 0;
    }

    /**
     * Called before each test method.
     * Creates a fresh in-memory SQLite connection and runs the schema.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    /**
     * Called after each test method.
     * Tears down the database connection so the in-memory DB is released.
     */
    protected function tearDown(): void
    {
        $this->authContext = null;
        unset($this->db);
        parent::tearDown();
    }

    // =========================================================================
    // Database bootstrap
    // =========================================================================

    /**
     * Initialise an SQLite :memory: connection and run all schema migrations.
     */
    protected function setUpDatabase(): void
    {
        $this->db = new PDO('sqlite::memory:', options: [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->db->exec('PRAGMA foreign_keys = ON;');
        $this->runMigrations();
    }

    /**
     * Run the DDL statements that define the full ERP schema.
     * Each CREATE TABLE is wrapped in IF NOT EXISTS for idempotency.
     */
    protected function runMigrations(): void
    {
        // ── Branches ─────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS branches (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                name        TEXT    NOT NULL,
                code        TEXT    NOT NULL UNIQUE,
                address     TEXT,
                phone       TEXT,
                is_active   INTEGER NOT NULL DEFAULT 1,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Roles ─────────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS roles (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                name        TEXT    NOT NULL,
                slug        TEXT    NOT NULL UNIQUE,
                description TEXT    NOT NULL DEFAULT '',
                permissions TEXT    NOT NULL DEFAULT '[]',
                is_system   INTEGER NOT NULL DEFAULT 0,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Users ─────────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id                      INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id               INTEGER NOT NULL REFERENCES branches(id),
                role_id                 INTEGER NOT NULL REFERENCES roles(id),
                name                    TEXT    NOT NULL,
                email                   TEXT    NOT NULL UNIQUE,
                password_hash           TEXT    NOT NULL,
                phone                   TEXT,
                avatar                  TEXT,
                status                  TEXT    NOT NULL DEFAULT 'active',
                failed_login_attempts   INTEGER NOT NULL DEFAULT 0,
                locked_until            TEXT,
                last_login_at           TEXT,
                created_at              TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at              TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Password history ──────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS password_history (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id       INTEGER NOT NULL REFERENCES users(id),
                password_hash TEXT    NOT NULL,
                created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Password reset tokens ─────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL REFERENCES users(id),
                token      TEXT    NOT NULL UNIQUE,
                expires_at TEXT    NOT NULL,
                used_at    TEXT,
                created_at TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Auth sessions ─────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS auth_sessions (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL REFERENCES users(id),
                token_hash TEXT    NOT NULL UNIQUE,
                ip_address TEXT,
                user_agent TEXT,
                expires_at TEXT    NOT NULL,
                created_at TEXT    NOT NULL DEFAULT (datetime('now')),
                revoked_at TEXT
            )
        SQL);

        // ── Products ─────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS products (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id    INTEGER NOT NULL REFERENCES branches(id),
                name         TEXT    NOT NULL,
                sku          TEXT    NOT NULL,
                barcode      TEXT,
                category     TEXT,
                unit         TEXT    NOT NULL DEFAULT 'pcs',
                cost_price   REAL    NOT NULL DEFAULT 0,
                sale_price   REAL    NOT NULL DEFAULT 0,
                reorder_level INTEGER NOT NULL DEFAULT 0,
                description  TEXT,
                is_active    INTEGER NOT NULL DEFAULT 1,
                deleted_at   TEXT,
                created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                UNIQUE (branch_id, sku)
            )
        SQL);

        // ── Inventory ─────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS inventory (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id     INTEGER NOT NULL REFERENCES branches(id),
                product_id    INTEGER NOT NULL REFERENCES products(id),
                quantity      REAL    NOT NULL DEFAULT 0,
                average_cost  REAL    NOT NULL DEFAULT 0,
                updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
                UNIQUE (branch_id, product_id)
            )
        SQL);

        // ── Stock movements ───────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS stock_movements (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id      INTEGER NOT NULL REFERENCES branches(id),
                product_id     INTEGER NOT NULL REFERENCES products(id),
                type           TEXT    NOT NULL,
                quantity       REAL    NOT NULL,
                unit_cost      REAL    NOT NULL DEFAULT 0,
                reference_type TEXT,
                reference_id   INTEGER,
                notes          TEXT,
                created_by     INTEGER,
                created_at     TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Stock transfers ───────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS stock_transfers (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                from_branch_id  INTEGER NOT NULL REFERENCES branches(id),
                to_branch_id    INTEGER NOT NULL REFERENCES branches(id),
                product_id      INTEGER NOT NULL REFERENCES products(id),
                quantity        REAL    NOT NULL,
                status          TEXT    NOT NULL DEFAULT 'pending',
                transferred_by  INTEGER,
                received_by     INTEGER,
                transferred_at  TEXT,
                received_at     TEXT,
                created_at      TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Employees ─────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS employees (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id       INTEGER NOT NULL REFERENCES branches(id),
                user_id         INTEGER REFERENCES users(id),
                name            TEXT    NOT NULL,
                email           TEXT,
                phone           TEXT,
                department      TEXT,
                designation     TEXT,
                join_date       TEXT    NOT NULL,
                basic_salary    REAL    NOT NULL DEFAULT 0,
                is_active       INTEGER NOT NULL DEFAULT 1,
                deleted_at      TEXT,
                created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at      TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Payroll ───────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS payrolls (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id      INTEGER NOT NULL REFERENCES branches(id),
                employee_id    INTEGER NOT NULL REFERENCES employees(id),
                period_month   INTEGER NOT NULL,
                period_year    INTEGER NOT NULL,
                basic_salary   REAL    NOT NULL,
                allowances     REAL    NOT NULL DEFAULT 0,
                deductions     REAL    NOT NULL DEFAULT 0,
                overtime_pay   REAL    NOT NULL DEFAULT 0,
                income_tax     REAL    NOT NULL DEFAULT 0,
                net_salary     REAL    NOT NULL,
                status         TEXT    NOT NULL DEFAULT 'draft',
                processed_at   TEXT,
                created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
                UNIQUE (employee_id, period_month, period_year)
            )
        SQL);

        // ── Chart of Accounts ─────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS accounts (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id   INTEGER NOT NULL REFERENCES branches(id),
                code        TEXT    NOT NULL,
                name        TEXT    NOT NULL,
                type        TEXT    NOT NULL,
                balance     REAL    NOT NULL DEFAULT 0,
                is_active   INTEGER NOT NULL DEFAULT 1,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at  TEXT    NOT NULL DEFAULT (datetime('now')),
                UNIQUE (branch_id, code)
            )
        SQL);

        // ── Journal entries ───────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS journal_entries (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id      INTEGER NOT NULL REFERENCES branches(id),
                entry_number   TEXT    NOT NULL UNIQUE,
                description    TEXT    NOT NULL,
                reference_type TEXT,
                reference_id   INTEGER,
                is_posted      INTEGER NOT NULL DEFAULT 0,
                is_reversed    INTEGER NOT NULL DEFAULT 0,
                reversed_by    INTEGER,
                entry_date     TEXT    NOT NULL,
                created_by     INTEGER,
                created_at     TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Journal entry lines ───────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS journal_lines (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                journal_entry_id INTEGER NOT NULL REFERENCES journal_entries(id),
                account_id       INTEGER NOT NULL REFERENCES accounts(id),
                debit            REAL    NOT NULL DEFAULT 0,
                credit           REAL    NOT NULL DEFAULT 0,
                description      TEXT
            )
        SQL);

        // ── Customers ─────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS customers (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id        INTEGER NOT NULL REFERENCES branches(id),
                name             TEXT    NOT NULL,
                email            TEXT,
                phone            TEXT,
                address          TEXT,
                outstanding_balance REAL NOT NULL DEFAULT 0,
                deleted_at       TEXT,
                created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at       TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Sales orders ──────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS sales_orders (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id     INTEGER NOT NULL REFERENCES branches(id),
                customer_id   INTEGER NOT NULL REFERENCES customers(id),
                order_number  TEXT    NOT NULL UNIQUE,
                status        TEXT    NOT NULL DEFAULT 'draft',
                total_amount  REAL    NOT NULL DEFAULT 0,
                notes         TEXT,
                order_date    TEXT    NOT NULL,
                created_by    INTEGER,
                created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Sales order items ─────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS sales_order_items (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                sales_order_id INTEGER NOT NULL REFERENCES sales_orders(id),
                product_id     INTEGER NOT NULL REFERENCES products(id),
                quantity       REAL    NOT NULL,
                unit_price     REAL    NOT NULL,
                line_total     REAL    NOT NULL
            )
        SQL);

        // ── Quotations ────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS quotations (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id       INTEGER NOT NULL REFERENCES branches(id),
                customer_id     INTEGER NOT NULL REFERENCES customers(id),
                quote_number    TEXT    NOT NULL UNIQUE,
                status          TEXT    NOT NULL DEFAULT 'draft',
                total_amount    REAL    NOT NULL DEFAULT 0,
                valid_until     TEXT,
                converted_order INTEGER REFERENCES sales_orders(id),
                created_by      INTEGER,
                created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at      TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Invoices ──────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS invoices (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id       INTEGER NOT NULL REFERENCES branches(id),
                customer_id     INTEGER NOT NULL REFERENCES customers(id),
                sales_order_id  INTEGER REFERENCES sales_orders(id),
                invoice_number  TEXT    NOT NULL UNIQUE,
                status          TEXT    NOT NULL DEFAULT 'unpaid',
                total_amount    REAL    NOT NULL DEFAULT 0,
                paid_amount     REAL    NOT NULL DEFAULT 0,
                due_date        TEXT,
                invoice_date    TEXT    NOT NULL,
                created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
                updated_at      TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Payments ─────────────────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS payments (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id    INTEGER NOT NULL REFERENCES branches(id),
                customer_id  INTEGER NOT NULL REFERENCES customers(id),
                amount       REAL    NOT NULL,
                payment_date TEXT    NOT NULL,
                method       TEXT    NOT NULL DEFAULT 'cash',
                reference    TEXT,
                created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // ── Payment allocations ───────────────────────────────────────────────
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS payment_allocations (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                payment_id   INTEGER NOT NULL REFERENCES payments(id),
                invoice_id   INTEGER NOT NULL REFERENCES invoices(id),
                amount       REAL    NOT NULL,
                created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
            )
        SQL);

        // Seed a default branch and role so foreign key constraints pass.
        $this->seedDefaults();
    }

    /**
     * Insert the bare minimum reference data needed for all other factories.
     */
    protected function seedDefaults(): void
    {
        $this->db->exec(<<<SQL
            INSERT OR IGNORE INTO branches (id, name, code) VALUES (1, 'Head Office', 'BR001')
        SQL);

        $this->db->exec(<<<SQL
            INSERT OR IGNORE INTO roles (id, name, slug, permissions, is_system)
            VALUES
                (1, 'Super Administrator', 'super_admin', '["*"]', 1),
                (2, 'Administrator',       'admin',       '["users.*","inventory.*","sales.*","accounting.*"]', 1),
                (3, 'Cashier',             'cashier',     '["sales.view","sales.create"]', 0)
        SQL);
    }

    // =========================================================================
    // Factory helpers
    // =========================================================================

    /**
     * Create and persist a user row, returning the raw associative array
     * that mirrors a PDO FETCH_ASSOC result.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function createUser(array $overrides = []): array
    {
        self::$userSeq++;
        $seq = self::$userSeq;

        $defaults = [
            'branch_id'             => 1,
            'role_id'               => 2,
            'name'                  => "Test User {$seq}",
            'email'                 => "user{$seq}@bizcore-test.local",
            'password'              => 'SecurePass@123',
            'phone'                 => null,
            'avatar'                => null,
            'status'                => 'active',
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'last_login_at'         => null,
        ];

        $data = array_merge($defaults, $overrides);
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO users
                (branch_id, role_id, name, email, password_hash, phone, avatar,
                 status, failed_login_attempts, locked_until, last_login_at,
                 created_at, updated_at)
            VALUES
                (:branch_id, :role_id, :name, :email, :password_hash, :phone,
                 :avatar, :status, :failed_login_attempts, :locked_until,
                 :last_login_at, datetime('now'), datetime('now'))
        SQL);

        $stmt->execute([
            ':branch_id'             => $data['branch_id'],
            ':role_id'               => $data['role_id'],
            ':name'                  => $data['name'],
            ':email'                 => $data['email'],
            ':password_hash'         => $passwordHash,
            ':phone'                 => $data['phone'],
            ':avatar'                => $data['avatar'],
            ':status'                => $data['status'],
            ':failed_login_attempts' => $data['failed_login_attempts'],
            ':locked_until'          => $data['locked_until'],
            ':last_login_at'         => $data['last_login_at'],
        ]);

        $id = (int) $this->db->lastInsertId();

        return $this->db
            ->query("SELECT * FROM users WHERE id = {$id}")
            ->fetch();
    }

    /**
     * Create and persist a branch row.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function createBranch(array $overrides = []): array
    {
        self::$branchSeq++;
        $seq = self::$branchSeq;

        $defaults = [
            'name'      => "Branch {$seq}",
            'code'      => "BR" . str_pad((string)($seq + 1), 3, '0', STR_PAD_LEFT),
            'address'   => "{$seq} Test Street, Dhaka",
            'phone'     => '01700000' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT),
            'is_active' => 1,
        ];

        $data = array_merge($defaults, $overrides);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO branches (name, code, address, phone, is_active, created_at, updated_at)
            VALUES (:name, :code, :address, :phone, :is_active, datetime('now'), datetime('now'))
        SQL);

        $stmt->execute([
            ':name'      => $data['name'],
            ':code'      => $data['code'],
            ':address'   => $data['address'],
            ':phone'     => $data['phone'],
            ':is_active' => $data['is_active'],
        ]);

        $id = (int) $this->db->lastInsertId();

        return $this->db
            ->query("SELECT * FROM branches WHERE id = {$id}")
            ->fetch();
    }

    /**
     * Create and persist a product row.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function createProduct(array $overrides = []): array
    {
        self::$productSeq++;
        $seq = self::$productSeq;

        $defaults = [
            'branch_id'     => 1,
            'name'          => "Product {$seq}",
            'sku'           => "SKU-{$seq}",
            'barcode'       => "880000000" . str_pad((string)$seq, 4, '0', STR_PAD_LEFT),
            'category'      => 'General',
            'unit'          => 'pcs',
            'cost_price'    => 100.00,
            'sale_price'    => 150.00,
            'reorder_level' => 10,
            'description'   => "Test product {$seq}",
            'is_active'     => 1,
        ];

        $data = array_merge($defaults, $overrides);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO products
                (branch_id, name, sku, barcode, category, unit, cost_price,
                 sale_price, reorder_level, description, is_active, created_at, updated_at)
            VALUES
                (:branch_id, :name, :sku, :barcode, :category, :unit, :cost_price,
                 :sale_price, :reorder_level, :description, :is_active, datetime('now'), datetime('now'))
        SQL);

        $stmt->execute([
            ':branch_id'     => $data['branch_id'],
            ':name'          => $data['name'],
            ':sku'           => $data['sku'],
            ':barcode'       => $data['barcode'],
            ':category'      => $data['category'],
            ':unit'          => $data['unit'],
            ':cost_price'    => $data['cost_price'],
            ':sale_price'    => $data['sale_price'],
            ':reorder_level' => $data['reorder_level'],
            ':description'   => $data['description'],
            ':is_active'     => $data['is_active'],
        ]);

        $id = (int) $this->db->lastInsertId();

        return $this->db
            ->query("SELECT * FROM products WHERE id = {$id}")
            ->fetch();
    }

    /**
     * Create and persist an employee row.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function createEmployee(array $overrides = []): array
    {
        self::$employeeSeq++;
        $seq = self::$employeeSeq;

        $defaults = [
            'branch_id'    => 1,
            'user_id'      => null,
            'name'         => "Employee {$seq}",
            'email'        => "employee{$seq}@bizcore-test.local",
            'phone'        => '01800000' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT),
            'department'   => 'General',
            'designation'  => 'Staff',
            'join_date'    => date('Y-m-d', strtotime("-{$seq} months")),
            'basic_salary' => 30000.00,
            'is_active'    => 1,
        ];

        $data = array_merge($defaults, $overrides);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO employees
                (branch_id, user_id, name, email, phone, department, designation,
                 join_date, basic_salary, is_active, created_at, updated_at)
            VALUES
                (:branch_id, :user_id, :name, :email, :phone, :department, :designation,
                 :join_date, :basic_salary, :is_active, datetime('now'), datetime('now'))
        SQL);

        $stmt->execute([
            ':branch_id'    => $data['branch_id'],
            ':user_id'      => $data['user_id'],
            ':name'         => $data['name'],
            ':email'        => $data['email'],
            ':phone'        => $data['phone'],
            ':department'   => $data['department'],
            ':designation'  => $data['designation'],
            ':join_date'    => $data['join_date'],
            ':basic_salary' => $data['basic_salary'],
            ':is_active'    => $data['is_active'],
        ]);

        $id = (int) $this->db->lastInsertId();

        return $this->db
            ->query("SELECT * FROM employees WHERE id = {$id}")
            ->fetch();
    }

    /**
     * Seed an inventory record for a product in a branch.
     *
     * @return array<string, mixed>
     */
    protected function seedInventory(int $productId, int $branchId, float $quantity, float $averageCost = 0): array
    {
        $stmt = $this->db->prepare(<<<SQL
            INSERT OR REPLACE INTO inventory (branch_id, product_id, quantity, average_cost, updated_at)
            VALUES (:branch_id, :product_id, :quantity, :average_cost, datetime('now'))
        SQL);

        $stmt->execute([
            ':branch_id'    => $branchId,
            ':product_id'   => $productId,
            ':quantity'     => $quantity,
            ':average_cost' => $averageCost,
        ]);

        return $this->db
            ->query("SELECT * FROM inventory WHERE product_id = {$productId} AND branch_id = {$branchId}")
            ->fetch();
    }

    /**
     * Create a chart-of-accounts entry.
     *
     * @return array<string, mixed>
     */
    protected function createAccount(array $overrides = []): array
    {
        static $accountSeq = 0;
        $accountSeq++;

        $defaults = [
            'branch_id' => 1,
            'code'      => "1000{$accountSeq}",
            'name'      => "Account {$accountSeq}",
            'type'      => 'asset',
            'balance'   => 0.0,
            'is_active' => 1,
        ];

        $data = array_merge($defaults, $overrides);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO accounts (branch_id, code, name, type, balance, is_active, created_at, updated_at)
            VALUES (:branch_id, :code, :name, :type, :balance, :is_active, datetime('now'), datetime('now'))
        SQL);

        $stmt->execute([
            ':branch_id' => $data['branch_id'],
            ':code'      => $data['code'],
            ':name'      => $data['name'],
            ':type'      => $data['type'],
            ':balance'   => $data['balance'],
            ':is_active' => $data['is_active'],
        ]);

        $id = (int) $this->db->lastInsertId();

        return $this->db
            ->query("SELECT * FROM accounts WHERE id = {$id}")
            ->fetch();
    }

    /**
     * Create a customer row.
     *
     * @return array<string, mixed>
     */
    protected function createCustomer(array $overrides = []): array
    {
        static $customerSeq = 0;
        $customerSeq++;

        $defaults = [
            'branch_id'           => 1,
            'name'                => "Customer {$customerSeq}",
            'email'               => "customer{$customerSeq}@example.com",
            'phone'               => '01900000' . str_pad((string)$customerSeq, 3, '0', STR_PAD_LEFT),
            'address'             => 'Dhaka, Bangladesh',
            'outstanding_balance' => 0.0,
        ];

        $data = array_merge($defaults, $overrides);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO customers (branch_id, name, email, phone, address, outstanding_balance, created_at, updated_at)
            VALUES (:branch_id, :name, :email, :phone, :address, :outstanding_balance, datetime('now'), datetime('now'))
        SQL);

        $stmt->execute([
            ':branch_id'           => $data['branch_id'],
            ':name'                => $data['name'],
            ':email'               => $data['email'],
            ':phone'               => $data['phone'],
            ':address'             => $data['address'],
            ':outstanding_balance' => $data['outstanding_balance'],
        ]);

        $id = (int) $this->db->lastInsertId();

        return $this->db
            ->query("SELECT * FROM customers WHERE id = {$id}")
            ->fetch();
    }

    // =========================================================================
    // Authentication helpers
    // =========================================================================

    /**
     * Set the authenticated user context for the test.
     *
     * @param  array<string, mixed>  $user  Raw user row from createUser()
     */
    protected function actingAs(array $user): static
    {
        $this->authContext = $user;
        return $this;
    }

    /**
     * Create a super-admin user and set it as the authenticated context.
     */
    protected function actingAsAdmin(): static
    {
        $admin = $this->createUser([
            'role_id' => 1,
            'name'    => 'Super Administrator',
            'email'   => 'superadmin@bizcore-test.local',
        ]);

        return $this->actingAs($admin);
    }

    /**
     * Return the currently authenticated user's ID, or null if not set.
     */
    protected function authUserId(): ?int
    {
        return isset($this->authContext['id']) ? (int) $this->authContext['id'] : null;
    }

    // =========================================================================
    // JSON assertion helpers
    // =========================================================================

    /**
     * Assert that $response is a successful API response array.
     *
     * @param  array<string, mixed>  $response
     */
    protected function assertSuccessResponse(array $response, ?string $message = null): void
    {
        $this->assertTrue(
            $response['success'] ?? false,
            $message ?? 'Expected success response, got: ' . json_encode($response)
        );
        $this->assertArrayHasKey('data', $response, 'Success response must contain a "data" key.');
    }

    /**
     * Assert that $response is an error API response array.
     *
     * @param  array<string, mixed>  $response
     */
    protected function assertErrorResponse(array $response, ?int $code = null, ?string $message = null): void
    {
        $this->assertFalse(
            $response['success'] ?? true,
            $message ?? 'Expected error response, got: ' . json_encode($response)
        );
        $this->assertArrayHasKey('message', $response, 'Error response must contain a "message" key.');

        if ($code !== null) {
            $this->assertSame(
                $code,
                $response['code'] ?? null,
                "Expected error code {$code}, got: " . ($response['code'] ?? 'none')
            );
        }
    }

    /**
     * Assert that a JSON string encodes a success response.
     */
    protected function assertJsonSuccessResponse(string $json): array
    {
        $data = json_decode($json, true);
        $this->assertIsArray($data, 'Response is not valid JSON.');
        $this->assertSuccessResponse($data);
        return $data;
    }

    // =========================================================================
    // Database assertion helpers
    // =========================================================================

    /**
     * Assert that at least one row exists in $table matching all $conditions.
     *
     * @param  array<string, mixed>  $conditions
     */
    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        [$whereClause, $params] = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        $this->assertGreaterThan(
            0,
            (int) $row['cnt'],
            "Failed asserting that table [{$table}] contains a row matching: " . json_encode($conditions)
        );
    }

    /**
     * Assert that no row exists in $table matching all $conditions.
     *
     * @param  array<string, mixed>  $conditions
     */
    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        [$whereClause, $params] = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        $this->assertSame(
            0,
            (int) $row['cnt'],
            "Failed asserting that table [{$table}] does not contain a row matching: " . json_encode($conditions)
        );
    }

    /**
     * Assert that a row is soft-deleted (deleted_at IS NOT NULL) in $table.
     *
     * @param  array<string, mixed>  $conditions
     */
    protected function assertSoftDeleted(string $table, array $conditions): void
    {
        [$whereClause, $params] = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$whereClause} AND deleted_at IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        $this->assertGreaterThan(
            0,
            (int) $row['cnt'],
            "Failed asserting that table [{$table}] has a soft-deleted row matching: " . json_encode($conditions)
        );
    }

    /**
     * Fetch a single row from $table matching $conditions, or null.
     *
     * @param  array<string, mixed>  $conditions
     * @return array<string, mixed>|null
     */
    protected function findInDatabase(string $table, array $conditions): ?array
    {
        [$whereClause, $params] = $this->buildWhereClause($conditions);
        $sql = "SELECT * FROM {$table} WHERE {$whereClause} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Count rows in $table optionally matching $conditions.
     *
     * @param  array<string, mixed>  $conditions
     */
    protected function countInDatabase(string $table, array $conditions = []): int
    {
        if (empty($conditions)) {
            $row = $this->db->query("SELECT COUNT(*) as cnt FROM {$table}")->fetch();
            return (int) $row['cnt'];
        }

        [$whereClause, $params] = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) $row['cnt'];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Build a PDO-compatible WHERE clause from a conditions array.
     *
     * Returns [$whereClause, $params].
     *
     * @param  array<string, mixed>  $conditions
     * @return array{string, array<string, mixed>}
     */
    private function buildWhereClause(array $conditions): array
    {
        $parts  = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $placeholder = ':' . str_replace('.', '_', $column);
            if ($value === null) {
                $parts[] = "{$column} IS NULL";
            } else {
                $parts[]                = "{$column} = {$placeholder}";
                $params[$placeholder]   = $value;
            }
        }

        return [implode(' AND ', $parts), $params];
    }
}
