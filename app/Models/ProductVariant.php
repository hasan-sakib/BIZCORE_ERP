<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'attributes',
        'purchase_price', 'selling_price', 'images', 'is_active',
    ];

    protected $casts = [
        'attributes'     => 'array',
        'images'         => 'array',
        'purchase_price' => 'decimal:2',
        'selling_price'  => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
