<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function create(array $data): Customer
    {
        if (!empty($data['email']) && Customer::where('email', $data['email'])->exists()) {
            throw new \InvalidArgumentException('A customer with this email already exists.');
        }

        $data['customer_code'] ??= $this->generateCode();

        return Customer::create($data);
    }

    public function update(int $id, array $data): Customer
    {
        $customer = Customer::findOrFail($id);

        if (!empty($data['email'])) {
            $conflict = Customer::where('email', $data['email'])->where('id', '!=', $id)->exists();
            if ($conflict) {
                throw new \InvalidArgumentException('A customer with this email already exists.');
            }
        }

        $customer->update($data);
        return $customer->fresh();
    }

    public function delete(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $outstanding = DB::table('invoices')
            ->where('customer_id', $id)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->count();

        if ($outstanding > 0) {
            throw new \RuntimeException("Cannot delete customer with {$outstanding} outstanding invoice(s).");
        }

        $customer->delete();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Customer::orderBy('name');

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(fn($q) => $q
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('customer_code', 'like', $term)
                ->orWhere('phone', 'like', $term)
            );
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function getLedger(int $customerId, string $from, string $to): array
    {
        $customer = Customer::findOrFail($customerId);

        $invoices = Invoice::where('customer_id', $customerId)
            ->whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date')
            ->get(['id', 'invoice_number', 'invoice_date', 'total_amount', 'paid_amount', 'balance', 'status']);

        $payments = Payment::where('payer_type', Customer::class)
            ->where('payer_id', $customerId)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')
            ->get(['id', 'payment_number', 'payment_date', 'amount', 'method']);

        return [
            'customer'  => $customer,
            'invoices'  => $invoices,
            'payments'  => $payments,
            'balance'   => (float) $customer->balance,
        ];
    }

    public function checkCreditLimit(int $customerId, float $amount): bool
    {
        $customer = Customer::findOrFail($customerId);

        if (!$customer->credit_limit || $customer->credit_limit <= 0) {
            return true;
        }

        return ((float) $customer->balance + $amount) <= (float) $customer->credit_limit;
    }

    private function generateCode(): string
    {
        $count = Customer::count();
        return 'CUST-' . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
