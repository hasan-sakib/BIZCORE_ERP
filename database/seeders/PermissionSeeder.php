<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;

/**
 * PermissionSeeder
 *
 * No-op in this schema version.
 * Permissions are stored as a JSON array in `roles.permissions` — there is no
 * separate `permissions` table.  The list of module.action strings is built and
 * persisted entirely by RoleSeeder.
 */
final class PermissionSeeder
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function run(): void
    {
        // Nothing to do — permissions live in roles.permissions (JSON column).
    }
}
