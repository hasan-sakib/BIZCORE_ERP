<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'unit_id',
        'name', 'slug', 'sku', 'barcode',
        'description', 'short_description', 'type',
        'purchase_price', 'selling_price', 'min_selling_price',
        'vat_rate', 'is_vat_inclusive', 'reorder_point',
        'is_active', 'images', 'attributes', 'meta', 'created_by',
    ];

    protected $casts = [
        'purchase_price'    => 'decimal:2',
        'selling_price'     => 'decimal:2',
        'min_selling_price' => 'decimal:2',
        'vat_rate'          => 'decimal:2',
        'is_vat_inclusive'  => 'boolean',
        'is_active'         => 'boolean',
        'images'            => 'array',
        'attributes'        => 'array',
        'meta'              => 'array',
    ];

    public function category(): BelongsTo   { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo      { return $this->belongsTo(Brand::class); }
    public function unit(): BelongsTo       { return $this->belongsTo(Unit::class); }
    public function variants(): HasMany     { return $this->hasMany(ProductVariant::class); }
    public function inventory(): HasMany    { return $this->hasMany(Inventory::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }

    public function scopeActive($q)        { return $q->where('is_active', true); }
    public function scopeSearch($q, string $term) {
        return $q->where(function($sq) use ($term) {
            $sq->where('name', 'LIKE', "%{$term}%")
               ->orWhere('sku',  'LIKE', "%{$term}%");
        });
    }

    public function getPriceWithVat(): float
    {
        if ($this->is_vat_inclusive) {
            return (float) $this->selling_price;
        }
        return (float) $this->selling_price * (1 + $this->vat_rate / 100);
    }
}
