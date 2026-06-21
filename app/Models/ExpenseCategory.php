<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $table = 'expense_categories';

    protected $fillable = [
        'name', 'code', 'description', 'is_active',
        'color', 'status',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function expenses(): HasMany { return $this->hasMany(Expense::class, 'category_id'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
