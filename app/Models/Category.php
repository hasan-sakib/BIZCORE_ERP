<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'image',
        'status', 'sort_order',
    ];

    protected $casts = ['sort_order' => 'integer'];

    public function parent(): BelongsTo   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children(): HasMany   { return $this->hasMany(Category::class, 'parent_id'); }
    public function products(): HasMany   { return $this->hasMany(Product::class); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeRoot($q)   { return $q->whereNull('parent_id'); }
}
