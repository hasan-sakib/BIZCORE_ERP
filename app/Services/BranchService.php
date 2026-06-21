<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchService
{
    public function create(array $data): Branch
    {
        if (Branch::where('code', $data['code'])->exists()) {
            throw new \InvalidArgumentException('This branch code is already in use.');
        }

        $branch = Branch::create($data);
        Log::info('Branch created.', ['branch_id' => $branch->id, 'code' => $branch->code]);

        return $branch;
    }

    public function update(int $id, array $data): Branch
    {
        $branch = $this->findOrFail($id);

        if (!empty($data['code'])) {
            $conflict = Branch::where('code', $data['code'])->where('id', '!=', $id)->exists();
            if ($conflict) {
                throw new \InvalidArgumentException('This branch code is already in use.');
            }
        }

        unset($data['deleted_at']);
        $branch->update($data);
        Log::info('Branch updated.', ['branch_id' => $id]);

        return $branch->fresh();
    }

    public function disable(int $id): void
    {
        $branch = $this->findOrFail($id);

        if ($branch->is_head) {
            throw new \RuntimeException('The head office branch cannot be disabled.');
        }

        $activeCount = DB::table('sales_orders')
            ->where('branch_id', $id)
            ->whereIn('status', ['confirmed', 'processing'])
            ->count();

        if ($activeCount > 0) {
            throw new \RuntimeException(
                "Cannot disable branch '{$branch->name}': it has {$activeCount} active transaction(s)."
            );
        }

        $branch->update(['status' => 'inactive']);
        Log::info('Branch disabled.', ['branch_id' => $id]);
    }

    public function enable(int $id): void
    {
        $branch = $this->findOrFail($id);
        $branch->update(['status' => 'active']);
        Log::info('Branch enabled.', ['branch_id' => $id]);
    }

    public function getDashboardData(int $branchId): array
    {
        $this->findOrFail($branchId);
        $month = date('n');
        $year  = date('Y');

        $revenue = DB::table('invoices')
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'cancelled')
            ->whereMonth('invoice_date', $month)
            ->whereYear('invoice_date', $year)
            ->sum('total_amount');

        $employees = DB::table('employees')
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        $inventoryValue = DB::table('inventory')
            ->where('branch_id', $branchId)
            ->selectRaw('SUM(quantity * avg_cost) as value')
            ->value('value') ?? 0;

        $pendingOrders = DB::table('sales_orders')
            ->where('branch_id', $branchId)
            ->whereIn('status', ['confirmed', 'processing'])
            ->count();

        return [
            'revenue'         => (float) $revenue,
            'employees'       => (int) $employees,
            'inventory_value' => (float) $inventoryValue,
            'pending_orders'  => (int) $pendingOrders,
        ];
    }

    public function active(): Collection
    {
        return Branch::where('status', 'active')->orderBy('name')->get();
    }

    public function all(int $perPage = 20): LengthAwarePaginator
    {
        return Branch::orderByDesc('is_head')->orderBy('name')->paginate($perPage);
    }

    private function findOrFail(int $id): Branch
    {
        return Branch::findOrFail($id);
    }
}
