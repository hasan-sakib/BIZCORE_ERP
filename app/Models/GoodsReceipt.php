<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $table = 'goods_receipts';

    protected $fillable = [
        'po_id', 'branch_id', 'supplier_id', 'grn_number',
        'receipt_date', 'warehouse_id', 'status',
        'subtotal', 'vat_amount', 'total_amount', 'notes', 'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'subtotal'     => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function branch(): BelongsTo        { return $this->belongsTo(Branch::class); }
    public function supplier(): BelongsTo      { return $this->belongsTo(Supplier::class); }
    public function warehouse(): BelongsTo     { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany           { return $this->hasMany(GoodsReceiptItem::class, 'grn_id'); }
    public function createdBy(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }
}
