<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoleService
{
    public function create(array $data): Role
    {
        $slug = Str::snake(strtolower($data['name']));

        if (Role::where('slug', $slug)->exists()) {
            throw new \InvalidArgumentException('A role with this name already exists.');
        }

        $role = Role::create([
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'] ?? [],
            'is_system'   => false,
        ]);

        Log::info('Role created.', ['role_id' => $role->id, 'slug' => $slug]);

        return $role;
    }

    public function update(int $id, array $data): Role
    {
        $role = $this->findOrFail($id);

        if ($role->is_system) {
            throw new \RuntimeException('System roles cannot be modified.');
        }

        if (!empty($data['name'])) {
            $newSlug = Str::snake(strtolower($data['name']));
            $conflict = Role::where('slug', $newSlug)->where('id', '!=', $id)->exists();
            if ($conflict) {
                throw new \InvalidArgumentException('A role with this name already exists.');
            }
            $data['slug'] = $newSlug;
        }

        $role->update($data);
        Log::info('Role updated.', ['role_id' => $id]);

        return $role->fresh();
    }

    public function delete(int $id): void
    {
        $role = $this->findOrFail($id);

        if ($role->is_system) {
            throw new \RuntimeException('System roles cannot be deleted.');
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            throw new \RuntimeException(
                "Cannot delete this role: {$userCount} user(s) are assigned to it."
            );
        }

        $role->delete();
        Log::info('Role deleted.', ['role_id' => $id]);
    }

    public function syncPermissions(int $roleId, array $permissions): Role
    {
        $role = $this->findOrFail($roleId);

        if ($role->is_system) {
            throw new \RuntimeException('Permissions on system roles are managed in code.');
        }

        $role->update(['permissions' => array_values(array_unique($permissions))]);
        Log::info('Permissions synced.', ['role_id' => $roleId]);

        return $role->fresh();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    private function findOrFail(int $id): Role
    {
        return Role::findOrFail($id);
    }
}
