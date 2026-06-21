<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileUpload extends Model
{
    public $updatedAt = false;
    protected $table = 'file_uploads';

    protected $fillable = [
        'user_id', 'original_name', 'stored_name', 'path',
        'mime_type', 'size', 'entity_type', 'entity_id', 'disk',
    ];

    protected $casts = ['size' => 'integer'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function getUrlAttribute(): string
    {
        return $this->disk === 'local'
            ? asset('storage/' . $this->path)
            : $this->path;
    }
}
