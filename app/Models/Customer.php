<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'customer_code', 'name', 'email', 'phone',
        'address', 'company_name', 'contact_person',
        'credit_limit', 'outstanding_balance', 'total_purchases',
        'loyalty_points', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'address'             => 'array',
        'credit_limit'        => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'total_purchases'     => 'decimal:2',
    ];

    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function invoices(): HasMany     { return $this->hasMany(Invoice::class); }
    public function salesOrders(): HasMany  { return $this->hasMany(SalesOrder::class); }
    public function quotations(): HasMany   { return $this->hasMany(Quotation::class); }
    public function payments(): HasMany     { return $this->hasMany(Payment::class, 'payer_id')->where('payer_type', 'customer'); }

    public function scopeActive($q)            { return $q->where('status', 'active'); }
    public function scopeByBranch($q, int $id) { return $q->where('branch_id', $id); }

    public function hasAvailableCredit(float $amount): bool
    {
        return $this->credit_limit <= 0 || ($this->outstanding_balance + $amount) <= $this->credit_limit;
    }
}
