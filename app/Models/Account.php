<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'accounts';

    protected $fillable = [
        'parent_id', 'code', 'name', 'type', 'subtype',
        'is_system', 'is_active', 'normal_balance',
        'description', 'balance',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'balance'   => 'decimal:2',
    ];

    public function parent(): BelongsTo    { return $this->belongsTo(Account::class, 'parent_id'); }
    public function children(): HasMany    { return $this->hasMany(Account::class, 'parent_id'); }
    public function journalLines(): HasMany { return $this->hasMany(JournalEntryLine::class, 'account_id'); }

    public function scopeActive($q)               { return $q->where('is_active', true); }
    public function scopeByType($q, string $type) { return $q->where('type', $type); }
}
