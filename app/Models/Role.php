<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'permissions', 'is_system'];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
    ];

    public function users(): HasMany { return $this->hasMany(User::class); }

    /**
     * Check if this role has the given permission string.
     * Uses the same 3-part logic as PermissionMiddleware.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        if (in_array('*', $permissions, true)) {
            return true;
        }

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        $withAccess = rtrim($permission, '.access') . '.access';
        if ($withAccess !== $permission && in_array($withAccess, $permissions, true)) {
            return true;
        }

        $parts = explode('.', $permission);
        if (count($parts) > 2) {
            $stripped = implode('.', array_slice($parts, 1));
            if (in_array($stripped, $permissions, true)) {
                return true;
            }
        }

        return false;
    }
}
