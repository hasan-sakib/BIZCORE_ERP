<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'branch_id', 'customer_id', 'sales_order_id', 'invoice_number',
        'invoice_date', 'due_date', 'warehouse_id', 'status',
        'subtotal', 'vat_amount', 'discount_amount', 'total_amount',
        'paid_amount', 'balance', 'notes', 'terms', 'created_by',
    ];

    protected $casts = [
        'invoice_date'    => 'date',
        'due_date'        => 'date',
        'subtotal'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'balance'         => 'decimal:2',
        'status'          => InvoiceStatus::class,
    ];

    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo    { return $this->belongsTo(Customer::class); }
    public function salesOrder(): BelongsTo  { return $this->belongsTo(SalesOrder::class, 'sales_order_id'); }
    public function warehouse(): BelongsTo   { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany         { return $this->hasMany(InvoiceItem::class); }
    public function payments(): HasMany      { return $this->hasMany(PaymentAllocation::class); }
    public function createdBy(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeUnpaid($q) {
        return $q->whereNotIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value]);
    }

    public function scopeOverdue($q) {
        return $q->where('due_date', '<', now())->unpaid();
    }

    public function scopeByBranch($q, int $id) { return $q->where('branch_id', $id); }
}
