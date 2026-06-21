<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;
    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id', 'variant_id', 'warehouse_id', 'branch_id',
        'movement_type', 'reference_type', 'reference_id',
        'quantity', 'unit_cost', 'total_cost', 'balance_after',
        'notes', 'created_by',
    ];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'unit_cost'    => 'decimal:4',
        'total_cost'   => 'decimal:4',
        'balance_after' => 'decimal:4',
        'created_at'   => 'datetime',
    ];

    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo   { return $this->belongsTo(ProductVariant::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeIn($q)  { return $q->whereIn('movement_type', ['purchase','return_in','transfer_in','opening']); }
    public function scopeOut($q) { return $q->whereIn('movement_type', ['sale','return_out','transfer_out']); }
}
