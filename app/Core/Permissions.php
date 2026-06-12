<?php

declare(strict_types=1);

namespace App\Core;

use App\Entities\User;
use App\Repositories\RoleRepository;

class Permissions
{
    public function __construct(
        private readonly Cache $cache,
        private readonly RoleRepository $roleRepository
    ) {}

    public function can(User $user, string $permission): bool
    {
        $permissions = $this->getPermissions($user);
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function cannot(User $user, string $permission): bool
    {
        return !$this->can($user, $permission);
    }

    public function hasRole(User $user, string $roleSlug): bool
    {
        $role = $this->roleRepository->findById($user->roleId);
        return $role !== null && $role->slug === $roleSlug;
    }

    public function hasAnyRole(User $user, array $roleSlugs): bool
    {
        $role = $this->roleRepository->findById($user->roleId);
        return $role !== null && in_array($role->slug, $roleSlugs, true);
    }

    public function isSuperAdmin(User $user): bool
    {
        return $this->hasRole($user, 'super_admin');
    }

    public function getPermissions(User $user): array
    {
        return $this->cache->remember(
            "user_permissions_{$user->id}",
            300,
            function () use ($user) {
                $role = $this->roleRepository->findById($user->roleId);
                if ($role === null) {
                    return [];
                }
                if ($role->slug === 'super_admin') {
                    return ['*'];
                }
                return $role->permissions ?? [];
            }
        );
    }

    public function clearCache(int $userId): void
    {
        $this->cache->forget("user_permissions_{$userId}");
    }
}
