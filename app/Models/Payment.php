<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'branch_id', 'payment_type', 'payer_type', 'payer_id',
        'payment_number', 'payment_date', 'amount', 'payment_method',
        'reference_number', 'bank_name', 'cheque_number', 'cheque_date',
        'notes', 'status', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cheque_date'  => 'date',
        'amount'       => 'decimal:2',
    ];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function payer(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'payer_type', 'payer_id');
    }

    public function scopeCompleted($q)           { return $q->where('status', 'completed'); }
    public function scopeByBranch($q, int $id)   { return $q->where('branch_id', $id); }
    public function scopeReceived($q)             { return $q->where('payment_type', 'received'); }
}
