<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_number', 'user_id', 'branch_id', 'department_id', 'designation_id',
        'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'gender', 'blood_group', 'nid_number',
        'religion', 'marital_status', 'address', 'emergency_contact',
        'bank_details', 'join_date', 'confirmation_date',
        'status', 'avatar', 'documents', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'address'           => 'array',
        'emergency_contact' => 'array',
        'documents'         => 'array',
        'date_of_birth'     => 'date',
        'join_date'         => 'date',
        'confirmation_date' => 'date',
    ];

    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo  { return $this->belongsTo(Department::class); }
    public function designation(): BelongsTo { return $this->belongsTo(Designation::class); }

    public function attendance(): HasMany       { return $this->hasMany(Attendance::class); }
    public function leaveRequests(): HasMany    { return $this->hasMany(LeaveRequest::class); }
    public function transfers(): HasMany        { return $this->hasMany(EmployeeTransfer::class); }
    public function salaryStructures(): HasMany { return $this->hasMany(SalaryStructure::class); }
    public function payrolls(): HasMany         { return $this->hasMany(Payroll::class); }

    public function activeSalaryStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class)->where('is_active', true)->latestOfMany('effective_date');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeActive($q)             { return $q->where('status', 'active'); }
    public function scopeByBranch($q, int $id)  { return $q->where('branch_id', $id); }
    public function scopeByDept($q, int $id)    { return $q->where('department_id', $id); }
}
