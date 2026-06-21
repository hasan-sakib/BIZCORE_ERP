<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    public $timestamps = false;
    protected $table = 'sales_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'variant_id',
        'quantity', 'delivered_qty', 'unit_price',
        'vat_rate', 'vat_amount', 'discount', 'total',
    ];

    protected $casts = [
        'quantity'      => 'decimal:4',
        'delivered_qty' => 'decimal:4',
        'unit_price'    => 'decimal:4',
        'vat_rate'      => 'decimal:2',
        'vat_amount'    => 'decimal:2',
        'discount'      => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class, 'order_id'); }
    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo    { return $this->belongsTo(ProductVariant::class); }
}
