<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;
    private string $table = '';
    private array $wheres = [];
    private array $bindings = [];
    private array $selects = ['*'];
    private ?int $limitVal = null;
    private ?int $offsetVal = null;
    private array $orders = [];
    private array $joins = [];

    public function __construct(private readonly array $config) {}

    public function getConnection(): PDO
    {
        if (static::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                static::$connection = new PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password'],
                    $options
                );
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return static::$connection;
    }

    public function table(string $table): static
    {
        $clone = clone $this;
        $clone->table = $table;
        $clone->wheres = [];
        $clone->bindings = [];
        $clone->selects = ['*'];
        $clone->limitVal = null;
        $clone->offsetVal = null;
        $clone->orders = [];
        $clone->joins = [];
        return $clone;
    }

    public function select(string ...$columns): static
    {
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->wheres[] = "{$column} IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = "{$column} IS NOT NULL";
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereBetween(string $column, mixed $from, mixed $to): static
    {
        $this->wheres[] = "{$column} BETWEEN ? AND ?";
        $this->bindings[] = $from;
        $this->bindings[] = $to;
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        if (!empty($this->wheres)) {
            $last = array_pop($this->wheres);
            $this->wheres[] = "({$last} OR {$column} {$operator} ?)";
        } else {
            $this->wheres[] = "{$column} {$operator} ?";
        }
        $this->bindings[] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limitVal = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offsetVal = $offset;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = "JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    private function buildSelect(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->selects) . ' FROM ' . $this->table;
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limitVal !== null) {
            $sql .= ' LIMIT ' . $this->limitVal;
        }
        if ($this->offsetVal !== null) {
            $sql .= ' OFFSET ' . $this->offsetVal;
        }
        return $sql;
    }

    public function get(): array
    {
        $stmt = $this->execute($this->buildSelect(), $this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limitVal = 1;
        $stmt = $this->execute($this->buildSelect(), $this->bindings);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->table;
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        $stmt = $this->execute($sql, $this->bindings);
        return (int) $stmt->fetchColumn();
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));
        return (int) $this->getConnection()->lastInsertId();
    }

    public function insertGetId(array $data): int
    {
        return $this->insert($data);
    }

    public function update(array $data): int
    {
        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET {$sets}";
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        $bindings = array_merge(array_values($data), $this->bindings);
        $stmt = $this->execute($sql, $bindings);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        $stmt = $this->execute($sql, $this->bindings);
        return $stmt->rowCount();
    }

    public function execute(string $sql, array $bindings = []): PDOStatement
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        } catch (PDOException $e) {
            throw new RuntimeException("Query failed: {$sql} — " . $e->getMessage());
        }
    }

    public function fetchAll(string $sql, array $bindings = []): array
    {
        return $this->execute($sql, $bindings)->fetchAll();
    }

    public function fetchOne(string $sql, array $bindings = []): ?array
    {
        $result = $this->execute($sql, $bindings)->fetch();
        return $result !== false ? $result : null;
    }

    public function fetchColumn(string $sql, array $bindings = []): mixed
    {
        return $this->execute($sql, $bindings)->fetchColumn();
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->getConnection();
        $pdo->beginTransaction();
        try {
            $result = $callback($this);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    public function commit(): void
    {
        $this->getConnection()->commit();
    }

    public function rollBack(): void
    {
        $this->getConnection()->rollBack();
    }

    public function lastInsertId(): int
    {
        return (int) $this->getConnection()->lastInsertId();
    }

    public function getPdo(): PDO
    {
        return $this->getConnection();
    }
}
