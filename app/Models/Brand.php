<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'logo', 'status'];

    public function products(): HasMany { return $this->hasMany(Product::class); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
}
