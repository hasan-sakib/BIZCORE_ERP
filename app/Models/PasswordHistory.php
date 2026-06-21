<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordHistory extends Model
{
    public $timestamps = false;
    protected $table = 'password_history';

    protected $fillable = ['user_id', 'password', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    protected $hidden = ['password'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
