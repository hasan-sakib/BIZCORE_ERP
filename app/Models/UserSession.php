<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $table = 'user_sessions';

    protected $fillable = [
        'user_id', 'session_token', 'refresh_token',
        'ip_address', 'user_agent', 'expires_at', 'revoked_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'revoked_at'  => 'datetime',
        'created_at'  => 'datetime',
    ];

    public $timestamps = false;

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }
}
