<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'abbreviation', 'base_unit_id', 'conversion_factor',
    ];

    protected $casts = ['conversion_factor' => 'decimal:4'];

    public function baseUnit(): BelongsTo { return $this->belongsTo(Unit::class, 'base_unit_id'); }
    public function products(): HasMany   { return $this->hasMany(Product::class); }
}
