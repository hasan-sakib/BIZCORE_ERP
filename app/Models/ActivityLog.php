<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $table = 'activity_log';

    protected $fillable = [
        'user_id', 'branch_id', 'loggable_type', 'loggable_id',
        'event', 'description', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function loggable(): MorphTo   { return $this->morphTo(); }
}
