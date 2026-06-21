<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'branch_id', 'supplier_id', 'po_number', 'order_date', 'expected_date',
        'status', 'subtotal', 'vat_amount', 'discount_amount', 'total_amount',
        'notes', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'order_date'      => 'date',
        'expected_date'   => 'date',
        'subtotal'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'status'          => PurchaseOrderStatus::class,
    ];

    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function supplier(): BelongsTo     { return $this->belongsTo(Supplier::class); }
    public function items(): HasMany          { return $this->hasMany(PurchaseOrderItem::class, 'po_id'); }
    public function goodsReceipts(): HasMany  { return $this->hasMany(GoodsReceipt::class, 'po_id'); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy(): BelongsTo   { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeByBranch($q, int $id) { return $q->where('branch_id', $id); }
    public function scopePending($q)  { return $q->where('status', PurchaseOrderStatus::Draft); }
}
