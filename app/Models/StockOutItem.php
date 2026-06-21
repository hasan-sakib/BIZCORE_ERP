<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOutItem extends Model
{
    public $timestamps = false;
    protected $table = 'stock_out_items';

    protected $fillable = [
        'stock_out_order_id', 'product_id', 'variant_id',
        'quantity', 'unit_cost',
    ];

    protected $casts = [
        'quantity'  => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function stockOutOrder(): BelongsTo { return $this->belongsTo(StockOutOrder::class); }
    public function product(): BelongsTo       { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo       { return $this->belongsTo(ProductVariant::class); }
}
