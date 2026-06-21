<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'branch_id', 'category_id', 'expense_number', 'date',
        'amount', 'vat_amount', 'total_amount', 'description',
        'payment_method', 'reference_number', 'approved_by',
        'status', 'receipt_path', 'created_by',
    ];

    protected $casts = [
        'date'         => 'date',
        'amount'       => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status'       => ExpenseStatus::class,
    ];

    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function category(): BelongsTo { return $this->belongsTo(ExpenseCategory::class, 'category_id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }

    public function scopePending($q)  { return $q->where('status', ExpenseStatus::Draft); }
    public function scopeApproved($q) { return $q->where('status', ExpenseStatus::Approved); }
}
