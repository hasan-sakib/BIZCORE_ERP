<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    public $timestamps = false;
    protected $table = 'stock_adjustment_items';

    protected $fillable = [
        'adjustment_id', 'product_id', 'variant_id',
        'quantity_before', 'quantity_adjusted', 'quantity_after',
        'unit_cost', 'reason',
    ];

    protected $casts = [
        'quantity_before'    => 'decimal:4',
        'quantity_adjusted'  => 'decimal:4',
        'quantity_after'     => 'decimal:4',
        'unit_cost'          => 'decimal:4',
    ];

    public function adjustment(): BelongsTo { return $this->belongsTo(StockAdjustment::class); }
    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo    { return $this->belongsTo(ProductVariant::class); }
}
