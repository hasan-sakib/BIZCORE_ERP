<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payroll';

    protected $fillable = [
        'employee_id', 'branch_id', 'month', 'year',
        'basic_salary', 'total_allowances', 'total_deductions',
        'gross_salary', 'tax_amount', 'net_salary',
        'working_days', 'present_days', 'absent_days',
        'overtime_hours', 'overtime_amount',
        'status', 'payment_date', 'payment_method', 'processed_by',
    ];

    protected $casts = [
        'basic_salary'     => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_salary'     => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'net_salary'       => 'decimal:2',
        'overtime_hours'   => 'decimal:2',
        'overtime_amount'  => 'decimal:2',
        'payment_date'     => 'date',
        'status'           => PayrollStatus::class,
    ];

    public function employee(): BelongsTo    { return $this->belongsTo(Employee::class); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }
    public function processedBy(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }

    public function scopeForMonth($q, int $month, int $year) {
        return $q->where('month', $month)->where('year', $year);
    }
    public function scopeProcessed($q) { return $q->where('status', PayrollStatus::Processed); }
    public function scopePaid($q)      { return $q->where('status', PayrollStatus::Paid); }
}
