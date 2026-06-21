<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    public $timestamps = false;
    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'journal_entry_id', 'account_id', 'branch_id',
        'debit', 'credit', 'description',
    ];

    protected $casts = [
        'debit'  => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function account(): BelongsTo      { return $this->belongsTo(Account::class); }
    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
}
