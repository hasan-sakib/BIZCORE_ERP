<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;

class SalesService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function createOrder(
        int     $branchId,
        int     $customerId,
        array   $items,
        array   $data = [],
        int     $createdBy = 0,
    ): SalesOrder {
        return DB::transaction(function () use ($branchId, $customerId, $items, $data, $createdBy) {
            $orderNumber = $this->generateNumber('ORD', 'sales_orders', 'order_number');
            $subtotal    = 0.0;
            $vatTotal    = 0.0;

            foreach ($items as $item) {
                $line     = (float) $item['quantity'] * (float) $item['unit_price'];
                $subtotal += $line;
                $vatTotal += $line * ((float) ($item['vat_rate'] ?? 0) / 100);
            }

            $discount    = (float) ($data['discount_amount'] ?? 0);
            $totalAmount = $subtotal + $vatTotal - $discount;

            $order = SalesOrder::create([
                'branch_id'       => $branchId,
                'customer_id'     => $customerId,
                'order_number'    => $orderNumber,
                'order_date'      => $data['order_date'] ?? now()->toDateString(),
                'expected_delivery' => $data['expected_delivery'] ?? null,
                'warehouse_id'    => $data['warehouse_id'] ?? null,
                'status'          => 'confirmed',
                'subtotal'        => round($subtotal, 2),
                'vat_amount'      => round($vatTotal, 2),
                'discount_amount' => round($discount, 2),
                'total_amount'    => round($totalAmount, 2),
                'paid_amount'     => 0,
                'notes'           => $data['notes'] ?? null,
                'created_by'      => $createdBy,
            ]);

            foreach ($items as $item) {
                $line    = (float) $item['quantity'] * (float) $item['unit_price'];
                $lineVat = $line * ((float) ($item['vat_rate'] ?? 0) / 100);
                $lineDisc = (float) ($item['discount'] ?? 0);

                SalesOrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate'   => $item['vat_rate'] ?? 0,
                    'vat_amount' => round($lineVat, 2),
                    'discount'   => round($lineDisc, 2),
                    'total'      => round($line + $lineVat - $lineDisc, 2),
                ]);
            }

            return $order->load('items');
        });
    }

    public function createInvoice(
        int     $branchId,
        int     $customerId,
        array   $items,
        array   $data = [],
        int     $createdBy = 0,
    ): Invoice {
        return DB::transaction(function () use ($branchId, $customerId, $items, $data, $createdBy) {
            $invoiceNumber = $this->generateNumber('INV', 'invoices', 'invoice_number');
            $subtotal      = 0.0;
            $vatTotal      = 0.0;

            foreach ($items as $item) {
                $line     = (float) $item['quantity'] * (float) $item['unit_price'];
                $subtotal += $line;
                $vatTotal += $line * ((float) ($item['vat_rate'] ?? 0) / 100);
            }

            $discount    = (float) ($data['discount_amount'] ?? 0);
            $totalAmount = $subtotal + $vatTotal - $discount;
            $warehouseId = (int) ($data['warehouse_id'] ?? 0);

            if ($warehouseId > 0) {
                foreach ($items as $item) {
                    $this->inventoryService->stockOut(
                        productId:   (int) $item['product_id'],
                        warehouseId: $warehouseId,
                        quantity:    (float) $item['quantity'],
                        variantId:   isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                        reference:   $invoiceNumber,
                        notes:       "Invoice {$invoiceNumber}",
                        createdBy:   $createdBy,
                    );
                }
            }

            $invoice = Invoice::create([
                'branch_id'       => $branchId,
                'customer_id'     => $customerId,
                'sales_order_id'  => $data['sales_order_id'] ?? null,
                'invoice_number'  => $invoiceNumber,
                'invoice_date'    => now()->toDateString(),
                'due_date'        => $data['due_date'] ?? null,
                'warehouse_id'    => $warehouseId ?: null,
                'subtotal'        => round($subtotal, 2),
                'vat_amount'      => round($vatTotal, 2),
                'discount_amount' => round($discount, 2),
                'total_amount'    => round($totalAmount, 2),
                'paid_amount'     => 0,
                'balance'         => round($totalAmount, 2),
                'status'          => 'sent',
                'notes'           => $data['notes'] ?? null,
                'created_by'      => $createdBy,
            ]);

            foreach ($items as $item) {
                $line    = (float) $item['quantity'] * (float) $item['unit_price'];
                $lineVat = $line * ((float) ($item['vat_rate'] ?? 0) / 100);
                $lineDisc = (float) ($item['discount'] ?? 0);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate'   => $item['vat_rate'] ?? 0,
                    'vat_amount' => round($lineVat, 2),
                    'discount'   => round($lineDisc, 2),
                    'total'      => round($line + $lineVat - $lineDisc, 2),
                ]);
            }

            Customer::where('id', $customerId)->increment('balance', round($totalAmount, 2));

            return $invoice->load('items');
        });
    }

    public function receivePayment(
        int     $branchId,
        int     $customerId,
        float   $amount,
        string  $method = 'cash',
        ?string $reference = null,
        ?string $notes = null,
        int     $createdBy = 0,
    ): Payment {
        return DB::transaction(function () use ($branchId, $customerId, $amount, $method, $reference, $notes, $createdBy) {
            $paymentNumber = $this->generateNumber('PAY', 'payments', 'payment_number');

            $payment = Payment::create([
                'branch_id'      => $branchId,
                'payer_type'     => Customer::class,
                'payer_id'       => $customerId,
                'payment_number' => $paymentNumber,
                'payment_date'   => now()->toDateString(),
                'amount'         => $amount,
                'method'         => $method,
                'reference'      => $reference,
                'notes'          => $notes,
                'status'         => 'completed',
                'created_by'     => $createdBy,
            ]);

            // FIFO: allocate to oldest unpaid invoices first
            $remaining = $amount;
            $invoices  = Invoice::where('customer_id', $customerId)
                ->whereIn('status', ['sent', 'partial'])
                ->orderBy('invoice_date')
                ->orderBy('id')
                ->get();

            foreach ($invoices as $invoice) {
                if ($remaining <= 0.0) break;

                $balance   = (float) $invoice->balance;
                $allocated = min($remaining, $balance);
                $remaining -= $allocated;

                $newPaid    = (float) $invoice->paid_amount + $allocated;
                $newBalance = max(0, (float) $invoice->total_amount - $newPaid);
                $newStatus  = $newBalance <= 0.01 ? 'paid' : 'partial';

                $invoice->update([
                    'paid_amount' => round($newPaid, 2),
                    'balance'     => round($newBalance, 2),
                    'status'      => $newStatus,
                ]);

                PaymentAllocation::create([
                    'payment_id'       => $payment->id,
                    'invoice_id'       => $invoice->id,
                    'allocated_amount' => round($allocated, 2),
                    'invoice_type'     => 'sales',
                ]);
            }

            $allocated = $amount - $remaining;
            Customer::where('id', $customerId)->update([
                'balance' => DB::raw("GREATEST(0, balance - " . round($allocated, 2) . ")"),
            ]);

            return $payment;
        });
    }

    private function generateNumber(string $prefix, string $table, string $column): string
    {
        $year  = date('Y');
        $count = DB::table($table)->where($column, 'like', "{$prefix}-{$year}-%")->count();
        return "{$prefix}-{$year}-" . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
