<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    public $timestamps = false;
    protected $table = 'payment_allocations';

    protected $fillable = [
        'payment_id', 'invoice_type', 'invoice_id', 'allocated_amount',
    ];

    protected $casts = ['allocated_amount' => 'decimal:2'];

    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
