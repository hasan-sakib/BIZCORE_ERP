<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Entities\Invoice;

class SalesService
{
    public function __construct(
        private readonly Database $db,
        private readonly InventoryService $inventoryService
    ) {}

    public function createOrder(
        int     $branchId,
        int     $customerId,
        array   $items,
        ?string $notes = null,
        float   $discount = 0,
        int     $createdBy = 0,
        ?int    $warehouseId = null,
        ?string $orderDate = null
    ): array {
        return $this->db->transaction(function () use (
            $branchId, $customerId, $items, $notes, $discount, $createdBy, $warehouseId, $orderDate
        ) {
            $orderNumber = $this->generateNumber('ORD', 'sales_orders', 'order_number');
            $subtotal = 0.0;
            $vatTotal = 0.0;

            foreach ($items as $item) {
                $lineTotal = (float)$item['quantity'] * (float)$item['unit_price'];
                $subtotal += $lineTotal;
                $vatTotal += $lineTotal * ((float)($item['vat_rate'] ?? 0) / 100);
            }

            $totalAmount = $subtotal + $vatTotal - $discount;

            $orderId = $this->db->table('sales_orders')->insert([
                'branch_id'       => $branchId,
                'customer_id'     => $customerId,
                'order_number'    => $orderNumber,
                'order_date'      => $orderDate ?? date('Y-m-d'),
                'warehouse_id'    => $warehouseId,
                'status'          => 'confirmed',
                'subtotal'        => round($subtotal, 2),
                'vat_amount'      => round($vatTotal, 2),
                'discount_amount' => round($discount, 2),
                'total_amount'    => round($totalAmount, 2),
                'paid_amount'     => 0,
                'notes'           => $notes,
                'created_by'      => $createdBy,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($items as $item) {
                $lineTotal = (float)$item['quantity'] * (float)$item['unit_price'];
                $lineVat   = $lineTotal * ((float)($item['vat_rate'] ?? 0) / 100);
                $lineDisc  = (float)($item['discount'] ?? 0);

                $this->db->table('sales_order_items')->insert([
                    'sales_order_id' => $orderId,
                    'product_id'     => (int)$item['product_id'],
                    'variant_id'     => $item['variant_id'] ?? null,
                    'quantity'       => (float)$item['quantity'],
                    'unit_price'     => (float)$item['unit_price'],
                    'vat_rate'       => (float)($item['vat_rate'] ?? 0),
                    'vat_amount'     => round($lineVat, 2),
                    'discount'       => round($lineDisc, 2),
                    'total'          => round($lineTotal + $lineVat - $lineDisc, 2),
                ]);
            }

            return [
                'id'           => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
            ];
        });
    }

    public function createInvoice(
        int     $branchId,
        int     $customerId,
        array   $items,
        int     $warehouseId = 0,
        ?string $dueDate = null,
        ?string $notes = null,
        float   $discount = 0,
        ?int    $orderId = null,
        int     $createdBy = 0
    ): array {
        return $this->db->transaction(function () use (
            $branchId, $customerId, $items, $warehouseId, $dueDate, $notes, $discount, $orderId, $createdBy
        ) {
            $invoiceNumber = $this->generateNumber('INV', 'invoices', 'invoice_number');
            $subtotal = 0.0;
            $vatTotal = 0.0;

            foreach ($items as $item) {
                $lineTotal = (float)$item['quantity'] * (float)$item['unit_price'];
                $subtotal += $lineTotal;
                $vatTotal += $lineTotal * ((float)($item['vat_rate'] ?? 0) / 100);
            }

            $totalAmount = $subtotal + $vatTotal - $discount;

            // Deduct stock for each item
            foreach ($items as $item) {
                if ($warehouseId > 0) {
                    $this->inventoryService->stockOut(
                        productId:   (int)$item['product_id'],
                        warehouseId: $warehouseId,
                        quantity:    (float)$item['quantity'],
                        variantId:   isset($item['variant_id']) ? (int)$item['variant_id'] : null,
                        reference:   $invoiceNumber,
                        notes:       "Invoice {$invoiceNumber}",
                        createdBy:   $createdBy
                    );
                }
            }

            $invoiceId = $this->db->table('invoices')->insert([
                'branch_id'       => $branchId,
                'customer_id'     => $customerId,
                'sales_order_id'  => $orderId,
                'invoice_number'  => $invoiceNumber,
                'invoice_date'    => date('Y-m-d'),
                'due_date'        => $dueDate,
                'warehouse_id'    => $warehouseId ?: null,
                'subtotal'        => round($subtotal, 2),
                'vat_amount'      => round($vatTotal, 2),
                'discount_amount' => round($discount, 2),
                'total_amount'    => round($totalAmount, 2),
                'paid_amount'     => 0,
                'balance'         => round($totalAmount, 2),
                'status'          => 'sent',
                'notes'           => $notes,
                'created_by'      => $createdBy,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($items as $item) {
                $lineTotal = (float)$item['quantity'] * (float)$item['unit_price'];
                $lineVat   = $lineTotal * ((float)($item['vat_rate'] ?? 0) / 100);
                $lineDisc  = (float)($item['discount'] ?? 0);

                $this->db->table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'product_id' => (int)$item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => (float)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'vat_rate'   => (float)($item['vat_rate'] ?? 0),
                    'vat_amount' => round($lineVat, 2),
                    'discount'   => round($lineDisc, 2),
                    'total'      => round($lineTotal + $lineVat - $lineDisc, 2),
                ]);
            }

            // Update customer balance
            $this->db->execute(
                "UPDATE customers SET balance = balance + ?, updated_at = ? WHERE id = ?",
                [round($totalAmount, 2), now(), $customerId]
            );

            return $this->db->table('invoices')->where('id', $invoiceId)->first();
        });
    }

    public function receivePayment(
        int     $branchId,
        int     $customerId,
        float   $amount,
        string  $method = 'cash',
        ?string $reference = null,
        ?string $notes = null,
        int     $createdBy = 0
    ): array {
        return $this->db->transaction(function () use (
            $branchId, $customerId, $amount, $method, $reference, $notes, $createdBy
        ) {
            $paymentNumber = $this->generateNumber('PAY', 'payments', 'payment_number');

            $paymentId = $this->db->table('payments')->insert([
                'branch_id'      => $branchId,
                'customer_id'    => $customerId,
                'payment_number' => $paymentNumber,
                'payment_date'   => date('Y-m-d'),
                'amount'         => $amount,
                'method'         => $method,
                'reference'      => $reference,
                'notes'          => $notes,
                'status'         => 'completed',
                'created_by'     => $createdBy,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // FIFO: allocate to oldest unpaid invoices first
            $remaining = $amount;
            $invoices  = $this->db->fetchAll(
                "SELECT * FROM invoices WHERE customer_id = ? AND status IN ('sent','partial')
                 AND deleted_at IS NULL ORDER BY invoice_date ASC, id ASC",
                [$customerId]
            );

            foreach ($invoices as $invoice) {
                if ($remaining <= 0.0) break;

                $balance   = (float)$invoice['balance'];
                $allocated = min($remaining, $balance);
                $remaining -= $allocated;

                $newPaid    = (float)$invoice['paid_amount'] + $allocated;
                $newBalance = max(0, (float)$invoice['total_amount'] - $newPaid);
                $newStatus  = $newBalance <= 0.01 ? 'paid' : 'partial';

                $this->db->table('invoices')->where('id', (int)$invoice['id'])->update([
                    'paid_amount' => round($newPaid, 2),
                    'balance'     => round($newBalance, 2),
                    'status'      => $newStatus,
                    'updated_at'  => now(),
                ]);

                $this->db->table('payment_allocations')->insert([
                    'payment_id' => $paymentId,
                    'invoice_id' => (int)$invoice['id'],
                    'amount'     => round($allocated, 2),
                    'created_at' => now(),
                ]);
            }

            // Reduce customer outstanding balance by amount actually allocated
            $allocated = $amount - $remaining;
            $this->db->execute(
                "UPDATE customers SET balance = GREATEST(0, balance - ?), updated_at = ? WHERE id = ?",
                [round($allocated, 2), now(), $customerId]
            );

            return [
                'id'             => $paymentId,
                'payment_number' => $paymentNumber,
                'amount'         => $amount,
                'allocated'      => $allocated,
                'unallocated'    => $remaining,
            ];
        });
    }

    private function generateNumber(string $prefix, string $table, string $column): string
    {
        $year  = date('Y');
        $count = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$table} WHERE {$column} LIKE ?",
            ["{$prefix}-{$year}-%"]
        );
        return "{$prefix}-{$year}-" . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
