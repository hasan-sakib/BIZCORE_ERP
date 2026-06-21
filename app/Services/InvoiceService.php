<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function paginate(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Invoice::with(['customer', 'salesOrder'])
            ->where('branch_id', $branchId)
            ->orderByDesc('invoice_date');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('invoice_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('invoice_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['search'])) {
            $query->where('invoice_number', 'like', '%' . $filters['search'] . '%');
        }
        if (!empty($filters['overdue'])) {
            $query->whereIn('status', ['sent', 'partial'])->where('due_date', '<', now());
        }

        return $query->paginate($perPage);
    }

    public function cancel(int $id, int $cancelledBy): Invoice
    {
        $invoice = Invoice::findOrFail($id);

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            throw new \RuntimeException('Cannot cancel a paid or already cancelled invoice.');
        }

        $invoice->update(['status' => 'cancelled']);

        // Reverse customer balance
        DB::table('customers')->where('id', $invoice->customer_id)
            ->update(['balance' => DB::raw('GREATEST(0, balance - ' . (float) $invoice->balance . ')')]);

        return $invoice->fresh();
    }

    public function generateInvoiceNumber(int $branchId): string
    {
        $year  = date('Y');
        $count = Invoice::where('branch_id', $branchId)->whereYear('invoice_date', $year)->count();
        return 'INV-' . $year . '-' . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function getAgingReport(int $branchId): array
    {
        $invoices = Invoice::where('branch_id', $branchId)
            ->whereIn('status', ['sent', 'partial'])
            ->where('due_date', '<', now())
            ->with('customer')
            ->get();

        $aging = ['0-30' => [], '31-60' => [], '61-90' => [], '90+' => []];
        $today = now();

        foreach ($invoices as $invoice) {
            $days = $today->diffInDays($invoice->due_date);
            $bucket = match (true) {
                $days <= 30  => '0-30',
                $days <= 60  => '31-60',
                $days <= 90  => '61-90',
                default      => '90+',
            };
            $aging[$bucket][] = $invoice;
        }

        return $aging;
    }
}
