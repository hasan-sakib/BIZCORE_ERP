<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'type', 'title', 'message',
        'data', 'read_at', 'expires_at',
    ];

    protected $casts = [
        'data'       => 'array',
        'read_at'    => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeUnread($q)  { return $q->whereNull('read_at'); }
    public function scopeForUser($q, int $userId) { return $q->where('user_id', $userId); }

    public function markRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
