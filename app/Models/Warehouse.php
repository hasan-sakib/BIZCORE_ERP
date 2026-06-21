<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'code', 'address', 'is_primary',
        'status', 'location', 'manager_id', 'capacity', 'is_default',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function inventory(): HasMany      { return $this->hasMany(Inventory::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
    public function transfersOut(): HasMany   { return $this->hasMany(StockTransfer::class, 'from_warehouse_id'); }
    public function transfersIn(): HasMany    { return $this->hasMany(StockTransfer::class, 'to_warehouse_id'); }

    public function scopeActive($q)            { return $q->where('status', 'active'); }
    public function scopeByBranch($q, int $id) { return $q->where('branch_id', $id); }
}
