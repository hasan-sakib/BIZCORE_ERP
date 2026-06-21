<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    protected $table = 'quotations';

    protected $fillable = [
        'branch_id', 'customer_id', 'quotation_number', 'date',
        'expiry_date', 'status', 'subtotal', 'vat_amount',
        'discount_amount', 'total_amount', 'notes', 'terms', 'created_by',
    ];

    protected $casts = [
        'date'            => 'date',
        'expiry_date'     => 'date',
        'subtotal'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo  { return $this->belongsTo(Customer::class); }
    public function items(): HasMany       { return $this->hasMany(QuotationItem::class); }
    public function salesOrder(): HasOne   { return $this->hasOne(SalesOrder::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopePending($q) { return $q->where('status', 'sent'); }
    public function scopeExpired($q) { return $q->where('expiry_date', '<', now())->where('status', 'sent'); }
}
