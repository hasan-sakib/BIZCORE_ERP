<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\Role;

/**
 * RoleRepository
 *
 * All SQL queries related to the `roles` and `permissions` tables.
 */
final class RoleRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Reads
    // -------------------------------------------------------------------------

    /**
     * Find a role by its primary key.
     */
    public function findById(int $id): ?Role
    {
        $row = $this->fetchOne(
            'SELECT * FROM roles WHERE id = :id LIMIT 1',
            [':id' => $id],
        );

        return $row !== null ? Role::fromArray($row) : null;
    }

    /**
     * Find a role by its URL-friendly slug.
     */
    public function findBySlug(string $slug): ?Role
    {
        $row = $this->fetchOne(
            'SELECT * FROM roles WHERE slug = :slug LIMIT 1',
            [':slug' => $slug],
        );

        return $row !== null ? Role::fromArray($row) : null;
    }

    /**
     * Find a role and eagerly load its permissions as an array within the entity.
     */
    public function findWithPermissions(int $id): ?Role
    {
        $row = $this->fetchOne(
            'SELECT * FROM roles WHERE id = :id LIMIT 1',
            [':id' => $id],
        );

        if ($row === null) {
            return null;
        }

        $row['permissions'] = $this->getPermissionsForRole($id);

        return Role::fromArray($row);
    }

    /**
     * Return all roles, optionally ordered by name.
     *
     * @return Role[]
     */
    public function findAll(): array
    {
        $rows = $this->fetchAll('SELECT * FROM roles ORDER BY name ASC');

        return array_map(static fn (array $row) => Role::fromArray($row), $rows);
    }

    /**
     * Return the permission slugs assigned to the given role.
     *
     * @return string[]
     */
    public function getPermissionsForRole(int $roleId): array
    {
        $rows = $this->fetchAll(
            <<<SQL
            SELECT p.slug
            FROM permissions p
            INNER JOIN role_permissions rp ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id
            ORDER BY p.slug ASC
            SQL,
            [':role_id' => $roleId],
        );

        return array_column($rows, 'slug');
    }

    /**
     * Return all available permissions grouped by module.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getAllPermissionsGrouped(): array
    {
        $rows = $this->fetchAll(
            'SELECT id, slug, name, description, module FROM permissions ORDER BY module ASC, name ASC',
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['module']][] = $row;
        }

        return $grouped;
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Insert a new role and return its generated ID.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            <<<SQL
            INSERT INTO roles (name, slug, description, is_system, created_at, updated_at)
            VALUES (:name, :slug, :description, :is_system, NOW(), NOW())
            SQL,
            [
                ':name'        => $data['name'],
                ':slug'        => $data['slug'],
                ':description' => $data['description'] ?? '',
                ':is_system'   => (int) ($data['is_system'] ?? 0),
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing role record.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params     = [':id' => $id];

        $allowed = ['name', 'slug', 'description'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[]        = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if ($setClauses === []) {
            return false;
        }

        $setClauses[] = 'updated_at = NOW()';
        $setSQL       = implode(', ', $setClauses);

        return $this->modify(
            "UPDATE roles SET {$setSQL} WHERE id = :id AND is_system = 0",
            $params,
        ) > 0;
    }

    /**
     * Delete a non-system role.
     */
    public function delete(int $id): bool
    {
        return $this->modify(
            'DELETE FROM roles WHERE id = :id AND is_system = 0',
            [':id' => $id],
        ) > 0;
    }

    /**
     * Replace the full permission set for a role (diff-based sync).
     *
     * Existing permissions not in $permissionIds are removed; new ones are added.
     *
     * @param  int[]  $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->transaction(function () use ($roleId, $permissionIds): void {
            // Remove all current assignments for this role.
            $this->modify(
                'DELETE FROM role_permissions WHERE role_id = :role_id',
                [':role_id' => $roleId],
            );

            if ($permissionIds === []) {
                return;
            }

            // Bulk-insert new assignments.
            $placeholders = implode(
                ', ',
                array_map(static fn (int $i) => "(:role_id_{$i}, :perm_{$i})", array_keys($permissionIds)),
            );

            $params = [];
            foreach (array_values($permissionIds) as $i => $permId) {
                $params[":role_id_{$i}"] = $roleId;
                $params[":perm_{$i}"]    = $permId;
            }

            $this->execute(
                "INSERT INTO role_permissions (role_id, permission_id) VALUES {$placeholders}",
                $params,
            );
        });
    }

    /**
     * Checks whether a slug is already taken (optionally excluding a specific role ID).
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $row = $this->fetchOne(
                'SELECT id FROM roles WHERE slug = :slug AND id != :id LIMIT 1',
                [':slug' => $slug, ':id' => $excludeId],
            );
        } else {
            $row = $this->fetchOne(
                'SELECT id FROM roles WHERE slug = :slug LIMIT 1',
                [':slug' => $slug],
            );
        }

        return $row !== null;
    }

    /**
     * Count users currently assigned to the given role.
     */
    public function countUsersWithRole(int $roleId): int
    {
        return $this->count(
            'SELECT COUNT(*) AS total FROM users WHERE role_id = :role_id AND deleted_at IS NULL',
            [':role_id' => $roleId],
        );
    }
}
