<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $table = 'sales_orders';

    protected $fillable = [
        'branch_id', 'customer_id', 'quotation_id', 'order_number',
        'order_date', 'expected_delivery', 'status', 'warehouse_id',
        'subtotal', 'vat_amount', 'discount_amount', 'total_amount',
        'paid_amount', 'notes', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'order_date'       => 'date',
        'expected_delivery' => 'date',
        'subtotal'         => 'decimal:2',
        'vat_amount'       => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'paid_amount'      => 'decimal:2',
    ];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo  { return $this->belongsTo(Customer::class); }
    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany       { return $this->hasMany(SalesOrderItem::class, 'order_id'); }
    public function invoice(): HasOne      { return $this->hasOne(Invoice::class, 'sales_order_id'); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopePending($q)           { return $q->whereIn('status', ['draft', 'confirmed']); }
    public function scopeByBranch($q, int $id) { return $q->where('branch_id', $id); }
}
