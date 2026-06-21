<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    public $timestamps = false;
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'po_id', 'product_id', 'variant_id',
        'quantity', 'received_qty', 'unit_price',
        'vat_rate', 'vat_amount', 'discount', 'total',
    ];

    protected $casts = [
        'quantity'    => 'decimal:4',
        'received_qty' => 'decimal:4',
        'unit_price'  => 'decimal:4',
        'vat_rate'    => 'decimal:2',
        'vat_amount'  => 'decimal:2',
        'discount'    => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function product(): BelongsTo       { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo       { return $this->belongsTo(ProductVariant::class); }
}
