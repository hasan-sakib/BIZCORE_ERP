<?php

declare(strict_types=1);

namespace Database;

use PDO;
use PDOException;

class MigrationRunner
{
    private PDO $pdo;

    public function __construct()
    {
        $host     = $_ENV['DB_HOST'] ?? 'mysql';
        $port     = $_ENV['DB_PORT'] ?? '3306';
        $dbname   = $_ENV['DB_DATABASE'] ?? 'bizcore_erp';
        $username = $_ENV['DB_USERNAME'] ?? 'bizcore';
        $password = $_ENV['DB_PASSWORD'] ?? 'secret';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL DEFAULT 1,
                `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function run(): void
    {
        $migrationFiles = glob(__DIR__ . '/migrations/*.php');
        sort($migrationFiles);

        $ran = $this->getRanMigrations();
        $batch = $this->getNextBatch();
        $count = 0;

        foreach ($migrationFiles as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran, true)) {
                continue;
            }

            require_once $file;
            $className = $this->getClassName($file);

            if (!class_exists($className)) {
                echo "  WARNING: Class {$className} not found in {$file}\n";
                continue;
            }

            $migration = new $className();

            try {
                echo "  Migrating: {$name} ... ";
                $migration->up($this->pdo);
                $this->logMigration($name, $batch);
                echo "done\n";
                $count++;
            } catch (\Throwable $e) {
                echo "FAILED\n";
                echo "  Error: " . $e->getMessage() . "\n";
                throw $e;
            }
        }

        if ($count === 0) {
            echo "  Nothing to migrate.\n";
        } else {
            echo "  Migrated {$count} file(s).\n";
        }
    }

    public function rollback(int $steps = 1): void
    {
        $lastBatch = $this->getLastBatch();

        for ($i = 0; $i < $steps; $i++) {
            $migrations = $this->getMigrationsForBatch($lastBatch - $i);
            foreach (array_reverse($migrations) as $name) {
                $file = __DIR__ . "/migrations/{$name}.php";
                if (!file_exists($file)) continue;

                require_once $file;
                $className = $this->getClassNameFromName($name);
                if (!class_exists($className)) continue;

                $migration = new $className();
                echo "  Rolling back: {$name} ... ";
                $migration->down($this->pdo);
                $this->removeMigration($name);
                echo "done\n";
            }
        }
    }

    public function reset(): void
    {
        $ran = $this->getRanMigrations();
        $migrationFiles = glob(__DIR__ . '/migrations/*.php');
        sort($migrationFiles);

        foreach (array_reverse($migrationFiles) as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $ran, true)) continue;

            require_once $file;
            $className = $this->getClassName($file);
            if (!class_exists($className)) continue;

            $migration = new $className();
            echo "  Rolling back: {$name} ... ";
            try {
                $migration->down($this->pdo);
                $this->removeMigration($name);
                echo "done\n";
            } catch (\Throwable $e) {
                echo "FAILED: " . $e->getMessage() . "\n";
            }
        }
    }

    private function getRanMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getNextBatch(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM migrations");
        return (int)$stmt->fetchColumn() + 1;
    }

    private function getLastBatch(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM migrations");
        return (int)$stmt->fetchColumn();
    }

    private function getMigrationsForBatch(int $batch): array
    {
        $stmt = $this->pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id ASC");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function logMigration(string $name, int $batch): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$name, $batch]);
    }

    private function removeMigration(string $name): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$name]);
    }

    private function getClassName(string $file): string
    {
        $name = basename($file, '.php');
        // Convert 001_create_users => CreateUsers
        $parts = explode('_', preg_replace('/^\d+_/', '', $name));
        return implode('', array_map('ucfirst', $parts));
    }

    private function getClassNameFromName(string $name): string
    {
        $parts = explode('_', preg_replace('/^\d+_/', '', $name));
        return implode('', array_map('ucfirst', $parts));
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
