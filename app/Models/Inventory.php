<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'product_id', 'variant_id', 'warehouse_id', 'branch_id',
        'quantity', 'reserved_quantity', 'avg_cost', 'last_restock_date',
    ];

    protected $casts = [
        'quantity'          => 'decimal:4',
        'reserved_quantity' => 'decimal:4',
        'avg_cost'          => 'decimal:4',
        'last_restock_date' => 'date',
    ];

    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo   { return $this->belongsTo(ProductVariant::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) $this->quantity - (float) $this->reserved_quantity;
    }

    public function scopeLowStock($q, float $threshold = 0) {
        return $q->where('quantity', '<=', $threshold);
    }
}
