<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    protected $table = 'stock_adjustments';

    protected $fillable = [
        'branch_id', 'warehouse_id', 'adjustment_number',
        'adjustment_date', 'reason', 'status', 'notes',
        'approved_by', 'created_by',
    ];

    protected $casts = ['adjustment_date' => 'date'];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany       { return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
