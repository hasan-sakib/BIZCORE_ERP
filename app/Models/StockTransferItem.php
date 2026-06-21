<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    public $timestamps = false;
    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'transfer_id', 'product_id', 'variant_id',
        'requested_qty', 'transferred_qty', 'received_qty', 'unit_cost',
    ];

    protected $casts = [
        'requested_qty'   => 'decimal:4',
        'transferred_qty' => 'decimal:4',
        'received_qty'    => 'decimal:4',
        'unit_cost'       => 'decimal:4',
    ];

    public function transfer(): BelongsTo { return $this->belongsTo(StockTransfer::class); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo  { return $this->belongsTo(ProductVariant::class); }
}
