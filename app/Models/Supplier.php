<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'supplier_code', 'name', 'email', 'phone',
        'address', 'company_name', 'contact_person',
        'credit_terms', 'outstanding_balance', 'total_purchases',
        'bank_details', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'address'             => 'array',
        'bank_details'        => 'array',
        'outstanding_balance' => 'decimal:2',
        'total_purchases'     => 'decimal:2',
    ];

    public function branch(): BelongsTo         { return $this->belongsTo(Branch::class); }
    public function purchaseOrders(): HasMany    { return $this->hasMany(PurchaseOrder::class); }
    public function goodsReceipts(): HasMany     { return $this->hasMany(GoodsReceipt::class); }
    public function payments(): HasMany          { return $this->hasMany(Payment::class, 'payer_id')->where('payer_type', 'supplier'); }

    public function scopeActive($q)            { return $q->where('status', 'active'); }
    public function scopeByBranch($q, int $id) { return $q->where('branch_id', $id); }
}
