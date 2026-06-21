<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id', 'name', 'code', 'level', 'description',
    ];

    protected $casts = ['level' => 'integer'];

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function employees(): HasMany    { return $this->hasMany(Employee::class); }
}
