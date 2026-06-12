<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;
use RuntimeException;
use Throwable;

/**
 * DatabaseSeeder
 *
 * Master seeder runner. Executes all seeders in dependency order.
 * Supports dry-run and demo-data flags via constructor options.
 */
final class DatabaseSeeder
{
    /** @var list<class-string> Seeder execution order respects FK dependencies. */
    private const SEEDER_SEQUENCE = [
        BranchSeeder::class,
        PermissionSeeder::class,
        RoleSeeder::class,
        UserSeeder::class,
        AccountSeeder::class,
        SettingsSeeder::class,
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly bool $withDemo = false,
        private readonly bool $dryRun = false,
    ) {}

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    /**
     * Run all seeders and, optionally, demo data.
     *
     * @throws RuntimeException  When a seeder fails.
     */
    public function run(): void
    {
        $this->output('=== BizCore ERP — Database Seeder ===');
        $this->output(sprintf(
            'Dry run: %s | Demo data: %s',
            $this->dryRun ? 'yes' : 'no',
            $this->withDemo ? 'yes' : 'no',
        ));
        $this->output('');

        foreach (self::SEEDER_SEQUENCE as $seederClass) {
            $this->runSeeder($seederClass);
        }

        if ($this->withDemo) {
            $this->runSeeder(DemoDataSeeder::class);
        }

        $this->output('');
        $this->output('=== Seeding completed successfully. ===');
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Instantiate and execute a single seeder class.
     *
     * @param class-string $seederClass
     *
     * @throws RuntimeException
     */
    private function runSeeder(string $seederClass): void
    {
        $shortName = basename(str_replace('\\', '/', $seederClass));
        $this->output(sprintf('[%s] Running …', $shortName));

        if ($this->dryRun) {
            $this->output(sprintf('[%s] Skipped (dry-run).', $shortName));
            return;
        }

        try {
            /** @var object $seeder */
            $seeder = new $seederClass($this->pdo);

            if (!method_exists($seeder, 'run')) {
                throw new RuntimeException(
                    sprintf('Seeder %s must implement a run() method.', $seederClass),
                );
            }

            $seeder->run();

            $this->output(sprintf('[%s] Done.', $shortName));
        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf('[%s] Seeder failed: %s', $shortName, $e->getMessage()),
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * Print a line to stdout.
     */
    private function output(string $message): void
    {
        echo $message . PHP_EOL;
    }
}
