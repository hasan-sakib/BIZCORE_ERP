<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInItem extends Model
{
    public $timestamps = false;
    protected $table = 'stock_in_items';

    protected $fillable = [
        'stock_in_order_id', 'product_id', 'variant_id',
        'quantity', 'unit_cost', 'total',
    ];

    protected $casts = [
        'quantity'  => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total'     => 'decimal:2',
    ];

    public function stockInOrder(): BelongsTo { return $this->belongsTo(StockInOrder::class); }
    public function product(): BelongsTo      { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo      { return $this->belongsTo(ProductVariant::class); }
}
