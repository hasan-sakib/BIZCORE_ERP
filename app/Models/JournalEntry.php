<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'branch_id', 'entry_number', 'date', 'reference_type',
        'reference_id', 'description', 'total_debit', 'total_credit',
        'status', 'posted_by', 'posted_at', 'reversed_by', 'reversed_at', 'created_by',
    ];

    protected $casts = [
        'date'         => 'date',
        'posted_at'    => 'datetime',
        'reversed_at'  => 'datetime',
        'total_debit'  => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function lines(): HasMany        { return $this->hasMany(JournalEntryLine::class, 'journal_entry_id'); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function postedBy(): BelongsTo   { return $this->belongsTo(User::class, 'posted_by'); }
    public function reversedBy(): BelongsTo { return $this->belongsTo(User::class, 'reversed_by'); }

    public function scopePosted($q)            { return $q->where('status', 'posted'); }
    public function scopeDraft($q)             { return $q->where('status', 'draft'); }
    public function scopeByBranch($q, int $id) { return $q->where('branch_id', $id); }
}
