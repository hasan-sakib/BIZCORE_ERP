<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $table = 'stock_transfers';

    protected $fillable = [
        'from_warehouse_id', 'to_warehouse_id', 'from_branch_id', 'to_branch_id',
        'transfer_number', 'transfer_date', 'status', 'notes',
        'requested_by', 'approved_by', 'received_by',
    ];

    protected $casts = ['transfer_date' => 'date'];

    public function fromWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse(): BelongsTo   { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function fromBranch(): BelongsTo    { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch(): BelongsTo      { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function requestedBy(): BelongsTo   { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo    { return $this->belongsTo(User::class, 'approved_by'); }
    public function receivedBy(): BelongsTo    { return $this->belongsTo(User::class, 'received_by'); }
    public function items(): HasMany           { return $this->hasMany(StockTransferItem::class, 'transfer_id'); }
}
