<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    protected $table = 'salary_components';

    protected $fillable = [
        'salary_structure_id', 'component_type', 'name',
        'amount', 'percentage', 'is_percentage', 'is_taxable',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'percentage'    => 'decimal:2',
        'is_percentage' => 'boolean',
        'is_taxable'    => 'boolean',
    ];

    public function salaryStructure(): BelongsTo { return $this->belongsTo(SalaryStructure::class); }

    public function scopeAllowances($q) { return $q->where('component_type', 'allowance'); }
    public function scopeDeductions($q) { return $q->where('component_type', 'deduction'); }
}
