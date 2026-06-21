<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOutOrder extends Model
{
    protected $table = 'stock_out_orders';

    protected $fillable = [
        'branch_id', 'warehouse_id', 'order_number',
        'order_date', 'status', 'reason', 'notes', 'created_by',
    ];

    protected $casts = ['order_date' => 'date'];

    public function branch(): BelongsTo    { return $this->belongsTo(Branch::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany       { return $this->hasMany(StockOutItem::class, 'stock_out_order_id'); }
}
