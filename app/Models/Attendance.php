<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id', 'branch_id', 'date',
        'check_in', 'check_out', 'working_hours',
        'overtime_hours', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'date'           => 'date',
        'check_in'       => 'datetime',
        'check_out'      => 'datetime',
        'working_hours'  => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'status'         => AttendanceStatus::class,
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }

    public function scopePresent($q)             { return $q->where('status', AttendanceStatus::Present); }
    public function scopeByDate($q, string $date) { return $q->whereDate('date', $date); }
    public function scopeByMonth($q, int $month, int $year) {
        return $q->whereMonth('date', $month)->whereYear('date', $year);
    }
}
