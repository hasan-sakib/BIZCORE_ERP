<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Role;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\RoleRepository;
use Psr\Log\LoggerInterface;

/**
 * RoleService
 *
 * Business logic for role and permission management.
 */
final class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly LoggerInterface $logger,
    ) {}

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Create a new role.
     *
     * @param  array<string, mixed>  $data  Keys: name, slug, description.
     *
     * @throws ValidationException  Slug already in use or required fields missing.
     */
    public function create(array $data): Role
    {
        $this->validateRoleData($data);

        $slug = $this->buildSlug($data['name']);
        $data['slug'] = $slug;

        if ($this->roleRepository->slugExists($slug)) {
            throw new ValidationException(['slug' => ['A role with this slug already exists.']]);
        }

        $id = $this->roleRepository->create($data);

        $role = $this->roleRepository->findWithPermissions($id);

        if ($role === null) {
            throw new \RuntimeException('Failed to retrieve newly created role.');
        }

        $this->logger->info('Role created.', ['role_id' => $id, 'slug' => $slug]);

        return $role;
    }

    /**
     * Update a non-system role.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws NotFoundException    Role does not exist.
     * @throws ForbiddenException   Attempting to modify a system role.
     * @throws ValidationException  Slug conflict.
     */
    public function update(int $id, array $data): Role
    {
        $role = $this->findOrFail($id);

        if ($role->isSystem) {
            throw new ForbiddenException('System roles cannot be modified.');
        }

        // Re-validate name / slug if name is being changed.
        if (!empty($data['name'])) {
            $newSlug = $this->buildSlug($data['name']);
            $data['slug'] = $newSlug;

            if ($this->roleRepository->slugExists($newSlug, $id)) {
                throw new ValidationException(['slug' => ['A role with this slug already exists.']]);
            }
        }

        $this->roleRepository->update($id, $data);

        $updated = $this->roleRepository->findWithPermissions($id);

        if ($updated === null) {
            throw new \RuntimeException('Failed to retrieve updated role.');
        }

        $this->logger->info('Role updated.', ['role_id' => $id]);

        return $updated;
    }

    /**
     * Delete a non-system role.
     *
     * Refuses deletion when users are still assigned to this role.
     *
     * @throws NotFoundException    Role does not exist.
     * @throws ForbiddenException   Attempting to delete a system role or a role in use.
     */
    public function delete(int $id): void
    {
        $role = $this->findOrFail($id);

        if ($role->isSystem) {
            throw new ForbiddenException('System roles cannot be deleted.');
        }

        $userCount = $this->roleRepository->countUsersWithRole($id);

        if ($userCount > 0) {
            throw new ForbiddenException(
                "Cannot delete this role: {$userCount} user(s) are currently assigned to it. "
                . 'Reassign those users to another role first.',
            );
        }

        $this->roleRepository->delete($id);

        $this->logger->info('Role deleted.', ['role_id' => $id, 'slug' => $role->slug]);
    }

    // -------------------------------------------------------------------------
    // Permissions
    // -------------------------------------------------------------------------

    /**
     * Atomically replace the complete permission set for a role.
     *
     * @param  int[]  $permissionIds
     *
     * @throws NotFoundException   Role does not exist.
     * @throws ForbiddenException  Attempting to modify permissions on a system role via UI.
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $role = $this->findOrFail($roleId);

        if ($role->isSystem) {
            throw new ForbiddenException('Permissions on system roles are managed in code and cannot be changed via the UI.');
        }

        // Ensure all supplied IDs are valid integers.
        $permissionIds = array_map('intval', $permissionIds);
        $permissionIds = array_values(array_unique($permissionIds));

        $this->roleRepository->syncPermissions($roleId, $permissionIds);

        $this->logger->info('Permissions synced for role.', [
            'role_id'        => $roleId,
            'permission_ids' => $permissionIds,
        ]);
    }

    /**
     * Return a role together with its full permission detail.
     *
     * @return array{role: Role, allPermissions: array<string, mixed[]>}
     *
     * @throws NotFoundException
     */
    public function getWithPermissions(int $id): array
    {
        $role = $this->findOrFail($id);
        $role = $this->roleRepository->findWithPermissions($id) ?? $role;

        $allPermissions = $this->roleRepository->getAllPermissionsGrouped();

        return [
            'role'           => $role,
            'allPermissions' => $allPermissions,
        ];
    }

    /**
     * Return all roles (without eager-loading permissions).
     *
     * @return Role[]
     */
    public function getAllRoles(): array
    {
        return $this->roleRepository->findAll();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Find a role or throw NotFoundException.
     *
     * @throws NotFoundException
     */
    private function findOrFail(int $id): Role
    {
        $role = $this->roleRepository->findById($id);

        if ($role === null) {
            throw new NotFoundException('Role', $id);
        }

        return $role;
    }

    /**
     * Validate the required fields for role creation/update.
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    private function validateRoleData(array $data): void
    {
        $errors = [];

        if (empty($data['name']) || mb_strlen(trim($data['name'])) < 2) {
            $errors['name'][] = 'Role name must be at least 2 characters.';
        }

        if (isset($data['name']) && mb_strlen(trim($data['name'])) > 100) {
            $errors['name'][] = 'Role name must not exceed 100 characters.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Convert a human-readable name to a URL-friendly slug.
     */
    private function buildSlug(string $name): string
    {
        $slug = mb_strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? $slug;
        return trim($slug, '_');
    }
}
