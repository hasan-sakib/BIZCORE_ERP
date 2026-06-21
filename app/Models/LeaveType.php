<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $table = 'leave_types';

    protected $fillable = ['name', 'days_per_year', 'carry_forward', 'paid'];

    protected $casts = [
        'carry_forward' => 'boolean',
        'paid'          => 'boolean',
    ];

    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
}
