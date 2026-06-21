<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTransfer extends Model
{
    protected $table = 'employee_transfers';

    protected $fillable = [
        'employee_id', 'from_branch_id', 'to_branch_id',
        'from_department_id', 'to_department_id',
        'transfer_date', 'reason', 'approved_by', 'status',
    ];

    protected $casts = ['transfer_date' => 'date'];

    public function employee(): BelongsTo    { return $this->belongsTo(Employee::class); }
    public function fromBranch(): BelongsTo  { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch(): BelongsTo    { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function fromDept(): BelongsTo    { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDept(): BelongsTo      { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function approvedBy(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }
}
