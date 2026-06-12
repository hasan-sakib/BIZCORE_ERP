#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

require_once __DIR__ . '/MigrationRunner.php';

$command = $argv[1] ?? 'run';
$runner  = new Database\MigrationRunner();

echo "\nBizCore ERP - Database Migration\n";
echo str_repeat('─', 40) . "\n";

match ($command) {
    'run', 'migrate' => $runner->run(),
    'rollback'       => $runner->rollback((int)($argv[2] ?? 1)),
    'reset'          => $runner->reset(),
    'fresh'          => (function () use ($runner) {
        echo "Resetting all migrations...\n";
        $runner->reset();
        echo "Running all migrations...\n";
        $runner->run();
    })(),
    default => (function () {
        echo "Usage: php migrate.php [run|rollback|reset|fresh]\n";
    })(),
};

echo str_repeat('─', 40) . "\n";
echo "Done.\n\n";
