<?php

declare(strict_types=1);

namespace App\Entities;

use JsonSerializable;

/**
 * Role Entity
 *
 * Represents a named set of permissions that can be assigned to users.
 */
final class Role implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $description,
        public readonly array $permissions,
        public readonly bool $isSystem,
    ) {}

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Construct a Role from a raw associative array (e.g. PDO fetch result).
     *
     * The `permissions` key may arrive as a JSON string or a PHP array already.
     */
    public static function fromArray(array $data): self
    {
        $permissions = $data['permissions'] ?? [];

        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            $permissions = is_array($decoded) ? $decoded : [];
        }

        return new self(
            id:          (int) $data['id'],
            name:        (string) $data['name'],
            slug:        (string) $data['slug'],
            description: (string) ($data['description'] ?? ''),
            permissions: $permissions,
            isSystem:    (bool) ($data['is_system'] ?? false),
        );
    }

    // -------------------------------------------------------------------------
    // Domain logic
    // -------------------------------------------------------------------------

    /**
     * Checks whether this role includes the given permission string.
     *
     * A super-admin wildcard '*' grants all permissions.
     *
     * @param  string $permission  e.g. 'users.create', 'reports.view'
     */
    public function hasPermission(string $permission): bool
    {
        if (in_array('*', $this->permissions, true)) {
            return true;
        }

        if (in_array($permission, $this->permissions, true)) {
            return true;
        }

        // Support wildcard module-level permissions: e.g. 'users.*'
        $parts = explode('.', $permission, 2);
        if (count($parts) === 2) {
            $moduleWildcard = $parts[0] . '.*';
            if (in_array($moduleWildcard, $this->permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether this role has all of the given permissions.
     */
    public function hasAllPermissions(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks whether this role has at least one of the given permissions.
     */
    public function hasAnyPermission(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /**
     * Convert entity to a plain associative array.
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'permissions' => $this->permissions,
            'is_system'   => $this->isSystem,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
