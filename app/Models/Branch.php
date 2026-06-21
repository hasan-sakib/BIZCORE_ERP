<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'address', 'phone', 'email',
        'manager_id', 'status', 'settings', 'is_head',
    ];

    protected $casts = [
        'address'  => 'array',
        'settings' => 'array',
        'is_head'  => 'boolean',
    ];

    public function users(): HasMany      { return $this->hasMany(User::class); }
    public function employees(): HasMany  { return $this->hasMany(Employee::class); }
    public function departments(): HasMany { return $this->hasMany(Department::class); }
    public function warehouses(): HasMany  { return $this->hasMany(Warehouse::class); }
    public function customers(): HasMany   { return $this->hasMany(Customer::class); }
    public function suppliers(): HasMany   { return $this->hasMany(Supplier::class); }
    public function invoices(): HasMany    { return $this->hasMany(Invoice::class); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
}
