<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'code', 'description', 'status', 'created_by',
    ];

    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function designations(): HasMany   { return $this->hasMany(Designation::class); }
    public function employees(): HasMany      { return $this->hasMany(Employee::class); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeByBranch($q, int $id) { return $q->where('branch_id', $id); }
}
