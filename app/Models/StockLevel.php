<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    protected $table = 'stock_levels';

    protected $fillable = [
        'product_id', 'variant_id', 'warehouse_id', 'branch_id',
        'quantity', 'reserved_quantity', 'reorder_point',
    ];

    protected $casts = [
        'quantity'          => 'decimal:4',
        'reserved_quantity' => 'decimal:4',
    ];

    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }

    public function getAvailableAttribute(): float
    {
        return (float) $this->quantity - (float) $this->reserved_quantity;
    }

    public function scopeLowStock($q) {
        return $q->whereRaw('quantity <= reorder_point');
    }
}
