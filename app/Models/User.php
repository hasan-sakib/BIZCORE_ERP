<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'branch_id', 'role_id', 'name', 'email', 'password',
        'phone', 'avatar', 'status', 'email_verified_at',
        'remember_token', 'last_login_at', 'last_login_ip', 'failed_login_attempts',
        'locked_until', 'must_change_password', 'two_factor_secret',
        'preferences', 'created_by', 'updated_by',
        'google_id', 'google_token', 'google_refresh_token',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'google_token'];

    protected $casts = [
        'status'                => UserStatus::class,
        'email_verified_at'     => 'datetime',
        'last_login_at'         => 'datetime',
        'locked_until'          => 'datetime',
        'preferences'           => 'array',
        'must_change_password'  => 'boolean',
    ];

    public function role(): BelongsTo   { return $this->belongsTo(Role::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function employee(): HasOne  { return $this->hasOne(Employee::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }
    public function loginHistory(): HasMany { return $this->hasMany(LoginHistory::class); }

    public function scopeActive($q) { return $q->where('status', UserStatus::Active); }
    public function scopeByBranch($q, int $branchId) { return $q->where('branch_id', $branchId); }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function isSuperAdmin(): bool
    {
        $permissions = $this->role?->permissions ?? [];
        return in_array('*', $permissions, true);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isLocked(): bool
    {
        return $this->status === UserStatus::Locked
            || ($this->locked_until !== null && $this->locked_until->isFuture());
    }
}
