<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'ip_address', 'user_agent',
        'status', 'failure_reason', 'location',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeSuccessful($q) { return $q->where('status', 'success'); }
    public function scopeFailed($q)     { return $q->where('status', 'failed'); }
}
