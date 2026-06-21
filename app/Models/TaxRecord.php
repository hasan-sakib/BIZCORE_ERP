<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRecord extends Model
{
    protected $table = 'tax_records';

    protected $fillable = [
        'branch_id', 'period_type', 'period_start', 'period_end',
        'total_sales', 'total_purchases', 'vat_collected',
        'vat_paid', 'net_vat', 'status', 'filed_at',
    ];

    protected $casts = [
        'period_start'    => 'date',
        'period_end'      => 'date',
        'filed_at'        => 'datetime',
        'total_sales'     => 'decimal:2',
        'total_purchases' => 'decimal:2',
        'vat_collected'   => 'decimal:2',
        'vat_paid'        => 'decimal:2',
        'net_vat'         => 'decimal:2',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    public function scopeDraft($q)  { return $q->where('status', 'draft'); }
    public function scopeFiled($q)  { return $q->where('status', 'filed'); }
}
