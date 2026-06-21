<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    protected $table = 'salary_structures';

    protected $fillable = [
        'employee_id', 'basic_salary', 'gross_salary', 'net_salary',
        'effective_date', 'is_active', 'created_by',
    ];

    protected $casts = [
        'basic_salary'   => 'decimal:2',
        'gross_salary'   => 'decimal:2',
        'net_salary'     => 'decimal:2',
        'effective_date' => 'date',
        'is_active'      => 'boolean',
    ];

    public function employee(): BelongsTo   { return $this->belongsTo(Employee::class); }
    public function components(): HasMany   { return $this->hasMany(SalaryComponent::class); }
    public function allowances(): HasMany   { return $this->hasMany(SalaryComponent::class)->where('component_type', 'allowance'); }
    public function deductions(): HasMany   { return $this->hasMany(SalaryComponent::class)->where('component_type', 'deduction'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
