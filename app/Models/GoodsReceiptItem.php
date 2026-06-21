<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    public $timestamps = false;
    protected $table = 'goods_receipt_items';

    protected $fillable = [
        'grn_id', 'product_id', 'variant_id', 'po_item_id',
        'quantity', 'unit_cost', 'vat_amount', 'total',
        'batch_number', 'expiry_date',
    ];

    protected $casts = [
        'quantity'    => 'decimal:4',
        'unit_cost'   => 'decimal:4',
        'vat_amount'  => 'decimal:2',
        'total'       => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function goodsReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class, 'grn_id'); }
    public function product(): BelongsTo      { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo      { return $this->belongsTo(ProductVariant::class); }
}
