#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

require_once __DIR__ . '/MigrationRunner.php';

echo "\nBizCore ERP - Database Seeder\n";
echo str_repeat('─', 40) . "\n";

$runner = new Database\MigrationRunner();
$pdo    = $runner->getPdo();

$class = $argv[1] ?? 'Database\\Seeders\\DatabaseSeeder';

$seederFile = __DIR__ . '/seeders/' . basename(str_replace('\\', '/', $class)) . '.php';
if (file_exists($seederFile)) {
    require_once $seederFile;
}

// Load all seeder files
foreach (glob(__DIR__ . '/seeders/*.php') as $file) {
    require_once $file;
}

if (!class_exists($class)) {
    echo "Seeder class [{$class}] not found.\n";
    exit(1);
}

$seeder = new $class($pdo);
echo "Running: {$class}\n";
$seeder->run();

echo str_repeat('─', 40) . "\n";
echo "Seeding completed.\n\n";
