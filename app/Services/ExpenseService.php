<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseService
{
    public function create(array $data): Expense
    {
        $vatAmount   = isset($data['amount'])
            ? (float) $data['amount'] * ((float) ($data['vat_rate'] ?? 0) / 100)
            : 0;

        $data['vat_amount']   = round($vatAmount, 2);
        $data['total_amount'] = round((float) ($data['amount'] ?? 0) + $vatAmount, 2);
        $data['status']       ??= 'pending';

        return Expense::create($data);
    }

    public function update(int $id, array $data): Expense
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status === 'paid') {
            throw new \RuntimeException('Cannot edit a paid expense.');
        }

        if (isset($data['amount'])) {
            $vatAmount          = (float) $data['amount'] * ((float) ($data['vat_rate'] ?? $expense->vat_rate ?? 0) / 100);
            $data['vat_amount'] = round($vatAmount, 2);
            $data['total_amount'] = round((float) $data['amount'] + $vatAmount, 2);
        }

        $expense->update($data);
        return $expense->fresh();
    }

    public function delete(int $id): void
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status === 'paid') {
            throw new \RuntimeException('Cannot delete a paid expense.');
        }

        $expense->delete();
    }

    public function approve(int $id, int $approvedBy): Expense
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'pending') {
            throw new \RuntimeException('Only pending expenses can be approved.');
        }

        $expense->update(['status' => 'approved', 'approved_by' => $approvedBy]);
        return $expense->fresh();
    }

    public function reject(int $id, int $rejectedBy): Expense
    {
        $expense = Expense::findOrFail($id);
        $expense->update(['status' => 'rejected']);
        return $expense->fresh();
    }

    public function markPaid(int $id, string $paymentDate): Expense
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'approved') {
            throw new \RuntimeException('Expense must be approved before marking paid.');
        }

        $expense->update(['status' => 'paid', 'payment_date' => $paymentDate]);
        return $expense->fresh();
    }

    public function paginate(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Expense::with(['category', 'createdBy'])
            ->where('branch_id', $branchId)
            ->orderByDesc('date');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('date', '<=', $filters['to_date']);
        }

        return $query->paginate($perPage);
    }
}
