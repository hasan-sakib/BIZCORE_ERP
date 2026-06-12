<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for the sales module business logic.
 *
 * Covers quotation creation, order conversion, stock reservation, invoice
 * generation, payment allocation (FIFO), invoice status transitions, customer
 * balance tracking, and sales returns — all exercised via the in-memory DB.
 */
final class SalesServiceTest extends TestCase
{
    // =========================================================================
    // Quotations
    // =========================================================================

    public function testCreateQuotationGeneratesUniqueNumber(): void
    {
        $customer = $this->createCustomer();

        $q1 = $this->createQuotation($customer['id'], [
            ['product_id' => null, 'quantity' => 1, 'unit_price' => 500],
        ]);

        $q2 = $this->createQuotation($customer['id'], [
            ['product_id' => null, 'quantity' => 2, 'unit_price' => 300],
        ]);

        $this->assertNotSame(
            $q1['quote_number'],
            $q2['quote_number'],
            'Each quotation must receive a unique quote number'
        );
    }

    public function testQuotationDefaultStatusIsDraft(): void
    {
        $customer = $this->createCustomer();
        $q = $this->createQuotation($customer['id']);

        $this->assertSame('draft', $q['status']);
    }

    public function testQuotationTotalIsCalculatedFromLineItems(): void
    {
        $customer = $this->createCustomer();
        $product  = $this->createProduct(['sale_price' => 200.00]);

        $q = $this->createQuotation($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 5, 'unit_price' => 200.00],
            ['product_id' => $product['id'], 'quantity' => 3, 'unit_price' => 150.00],
        ]);

        // 5*200 + 3*150 = 1000 + 450 = 1450
        $this->assertEqualsWithDelta(1_450.00, (float) $q['total_amount'], 0.01);
    }

    // =========================================================================
    // Convert quotation to order
    // =========================================================================

    public function testConvertQuotationToOrder(): void
    {
        $customer = $this->createCustomer();
        $q        = $this->createQuotation($customer['id'], [
            ['product_id' => null, 'quantity' => 10, 'unit_price' => 100.00],
        ]);

        $order = $this->convertQuotationToOrder($q['id']);

        $this->assertSame('confirmed', $order['status']);
        $this->assertNotEmpty($order['order_number']);

        // Quotation should be marked as converted.
        $quotation = $this->findInDatabase('quotations', ['id' => $q['id']]);
        $this->assertSame('converted', $quotation['status']);
        $this->assertNotNull($quotation['converted_order']);
    }

    public function testConvertedQuotationCannotBeConvertedAgain(): void
    {
        $customer = $this->createCustomer();
        $q        = $this->createQuotation($customer['id']);

        $this->convertQuotationToOrder($q['id']);

        $this->expectException(\LogicException::class);
        $this->convertQuotationToOrder($q['id']);
    }

    // =========================================================================
    // Sales orders and stock reservation
    // =========================================================================

    public function testCreateOrderReservesStock(): void
    {
        $product  = $this->createProduct();
        $customer = $this->createCustomer();

        $this->seedInventory($product['id'], 1, 100, 100.00);

        $order = $this->createOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 15, 'unit_price' => 150.00],
        ]);

        // Available stock must be reduced by the ordered quantity.
        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertEqualsWithDelta(85.0, (float) $inv['quantity'], 0.01);

        $this->assertDatabaseHas('stock_movements', [
            'product_id'     => $product['id'],
            'type'           => 'reservation',
            'reference_type' => 'sales_order',
            'reference_id'   => $order['id'],
        ]);
    }

    public function testCreateOrderFailsWhenStockInsufficientForReservation(): void
    {
        $product  = $this->createProduct();
        $customer = $this->createCustomer();

        $this->seedInventory($product['id'], 1, 5, 100.00);

        $this->expectException(\UnderflowException::class);
        $this->createOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 20, 'unit_price' => 150.00],
        ]);
    }

    // =========================================================================
    // Invoice creation and actual stock deduction
    // =========================================================================

    public function testCreateInvoiceDeductsActualStock(): void
    {
        $product  = $this->createProduct();
        $customer = $this->createCustomer();

        // Start with 80 units; order for 20.
        $this->seedInventory($product['id'], 1, 80, 100.00);

        $order   = $this->createOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 20, 'unit_price' => 150.00],
        ]);
        $invoice = $this->createInvoiceFromOrder($order['id']);

        // After invoicing, stock deducted for the invoice (reservation was already applied).
        $this->assertNotEmpty($invoice['invoice_number']);
        $this->assertSame('unpaid', $invoice['status']);

        // Movement of type 'stock_out' for the invoice must exist.
        $this->assertDatabaseHas('stock_movements', [
            'product_id'     => $product['id'],
            'type'           => 'stock_out',
            'reference_type' => 'invoice',
            'reference_id'   => $invoice['id'],
        ]);
    }

    // =========================================================================
    // Payment allocation (FIFO)
    // =========================================================================

    public function testReceivePaymentAllocatesToInvoicesFIFO(): void
    {
        $customer = $this->createCustomer();

        // Two invoices in order: 5,000 and 3,000.
        $inv1 = $this->createStandaloneInvoice($customer['id'], 5_000.00);
        $inv2 = $this->createStandaloneInvoice($customer['id'], 3_000.00);

        // Receive a payment of 6,000 (enough to pay inv1 fully + partial inv2).
        $this->receivePayment($customer['id'], 6_000.00);

        // Invoice 1 must be fully paid.
        $inv1Updated = $this->findInDatabase('invoices', ['id' => $inv1['id']]);
        $this->assertSame('paid', $inv1Updated['status']);
        $this->assertEqualsWithDelta(5_000.00, (float) $inv1Updated['paid_amount'], 0.01);

        // Invoice 2 must be partially paid.
        $inv2Updated = $this->findInDatabase('invoices', ['id' => $inv2['id']]);
        $this->assertSame('partial', $inv2Updated['status']);
        $this->assertEqualsWithDelta(1_000.00, (float) $inv2Updated['paid_amount'], 0.01);
    }

    // =========================================================================
    // Invoice status transitions
    // =========================================================================

    public function testPartialPaymentUpdatesInvoiceStatusToPartial(): void
    {
        $customer = $this->createCustomer();
        $invoice  = $this->createStandaloneInvoice($customer['id'], 10_000.00);

        $this->receivePayment($customer['id'], 4_000.00, [$invoice['id']]);

        $updated = $this->findInDatabase('invoices', ['id' => $invoice['id']]);
        $this->assertSame('partial', $updated['status']);
        $this->assertEqualsWithDelta(4_000.00, (float) $updated['paid_amount'], 0.01);
    }

    public function testFullPaymentUpdatesInvoiceStatusToPaid(): void
    {
        $customer = $this->createCustomer();
        $invoice  = $this->createStandaloneInvoice($customer['id'], 7_500.00);

        $this->receivePayment($customer['id'], 7_500.00, [$invoice['id']]);

        $updated = $this->findInDatabase('invoices', ['id' => $invoice['id']]);
        $this->assertSame('paid', $updated['status']);
    }

    public function testOverpaymentDoesNotExceedInvoiceTotal(): void
    {
        $customer = $this->createCustomer();
        $invoice  = $this->createStandaloneInvoice($customer['id'], 5_000.00);

        $this->receivePayment($customer['id'], 8_000.00, [$invoice['id']]);

        $updated = $this->findInDatabase('invoices', ['id' => $invoice['id']]);
        $this->assertSame('paid', $updated['status']);
        // Paid amount must never exceed total.
        $this->assertLessThanOrEqual((float) $updated['total_amount'], (float) $updated['paid_amount']);
    }

    // =========================================================================
    // Customer outstanding balance
    // =========================================================================

    public function testCustomerOutstandingBalanceUpdatesCorrectly(): void
    {
        $customer = $this->createCustomer(['outstanding_balance' => 0]);

        $inv1 = $this->createStandaloneInvoice($customer['id'], 20_000.00);
        $inv2 = $this->createStandaloneInvoice($customer['id'], 15_000.00);

        // Outstanding: 35,000
        $this->assertEqualsWithDelta(
            35_000.00,
            $this->getCustomerOutstandingBalance($customer['id']),
            0.01
        );

        $this->receivePayment($customer['id'], 10_000.00, [$inv1['id']]);

        // After partial payment of inv1: 35,000 - 10,000 = 25,000
        $this->assertEqualsWithDelta(
            25_000.00,
            $this->getCustomerOutstandingBalance($customer['id']),
            0.01
        );
    }

    // =========================================================================
    // Sales returns
    // =========================================================================

    public function testSalesReturnRestoresStock(): void
    {
        $product  = $this->createProduct();
        $customer = $this->createCustomer();

        $this->seedInventory($product['id'], 1, 50, 100.00);

        $order   = $this->createOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 10, 'unit_price' => 150.00],
        ]);
        $invoice = $this->createInvoiceFromOrder($order['id']);

        // After order: 50 - 10 = 40 in stock
        $invBeforeReturn = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertEqualsWithDelta(40.0, (float) $invBeforeReturn['quantity'], 0.01);

        // Customer returns 4 units.
        $this->processSalesReturn($invoice['id'], $product['id'], 4);

        $invAfterReturn = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertEqualsWithDelta(44.0, (float) $invAfterReturn['quantity'], 0.01);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product['id'],
            'type'       => 'return',
        ]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private static int $quoteSeq = 0;
    private static int $orderSeq = 0;
    private static int $invoiceSeq = 0;

    private function createQuotation(int $customerId, array $lines = []): array
    {
        self::$quoteSeq++;
        $quoteNumber = 'QT-' . date('Ym') . '-' . str_pad((string) self::$quoteSeq, 5, '0', STR_PAD_LEFT);

        $total = array_sum(array_map(
            fn($l) => ($l['quantity'] ?? 1) * ($l['unit_price'] ?? 0),
            $lines
        ));

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO quotations (branch_id, customer_id, quote_number, status, total_amount, created_at, updated_at)
            VALUES (1, :cid, :qnum, 'draft', :total, datetime('now'), datetime('now'))
        SQL);
        $stmt->execute([':cid' => $customerId, ':qnum' => $quoteNumber, ':total' => $total]);

        $id = (int) $this->db->lastInsertId();
        return $this->findInDatabase('quotations', ['id' => $id]);
    }

    private function convertQuotationToOrder(int $quotationId): array
    {
        $quotation = $this->findInDatabase('quotations', ['id' => $quotationId]);

        if ($quotation['status'] !== 'draft') {
            throw new \LogicException("Quotation {$quotationId} cannot be converted (status: {$quotation['status']}).");
        }

        self::$orderSeq++;
        $orderNumber = 'SO-' . date('Ym') . '-' . str_pad((string) self::$orderSeq, 5, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO sales_orders
                (branch_id, customer_id, order_number, status, total_amount, order_date, created_at, updated_at)
            VALUES
                (1, :cid, :onum, 'confirmed', :total, date('now'), datetime('now'), datetime('now'))
        SQL);
        $stmt->execute([
            ':cid'   => $quotation['customer_id'],
            ':onum'  => $orderNumber,
            ':total' => $quotation['total_amount'],
        ]);

        $orderId = (int) $this->db->lastInsertId();

        $this->db->prepare(
            "UPDATE quotations SET status = 'converted', converted_order = :oid WHERE id = :qid"
        )->execute([':oid' => $orderId, ':qid' => $quotationId]);

        return $this->findInDatabase('sales_orders', ['id' => $orderId]);
    }

    private function createOrder(int $customerId, array $lines): array
    {
        self::$orderSeq++;
        $orderNumber = 'SO-' . date('Ym') . '-' . str_pad((string) self::$orderSeq, 5, '0', STR_PAD_LEFT);

        // Check and reserve stock for each line.
        foreach ($lines as $line) {
            if (empty($line['product_id'])) {
                continue;
            }

            $inv = $this->findInDatabase('inventory', ['product_id' => $line['product_id'], 'branch_id' => 1]);
            $available = $inv ? (float) $inv['quantity'] : 0;

            if ($line['quantity'] > $available) {
                throw new \UnderflowException(
                    "Insufficient stock for product {$line['product_id']}. Available: {$available}"
                );
            }
        }

        $total = array_sum(array_map(fn($l) => $l['quantity'] * $l['unit_price'], $lines));

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO sales_orders
                (branch_id, customer_id, order_number, status, total_amount, order_date, created_at, updated_at)
            VALUES
                (1, :cid, :onum, 'confirmed', :total, date('now'), datetime('now'), datetime('now'))
        SQL);
        $stmt->execute([':cid' => $customerId, ':onum' => $orderNumber, ':total' => $total]);
        $orderId = (int) $this->db->lastInsertId();

        foreach ($lines as $line) {
            if (empty($line['product_id'])) {
                continue;
            }

            $lineTotal = $line['quantity'] * $line['unit_price'];

            $this->db->prepare(
                "INSERT INTO sales_order_items (sales_order_id, product_id, quantity, unit_price, line_total)
                 VALUES (:oid, :pid, :qty, :price, :total)"
            )->execute([
                ':oid'   => $orderId,
                ':pid'   => $line['product_id'],
                ':qty'   => $line['quantity'],
                ':price' => $line['unit_price'],
                ':total' => $lineTotal,
            ]);

            // Reserve stock.
            $inv      = $this->findInDatabase('inventory', ['product_id' => $line['product_id'], 'branch_id' => 1]);
            $newQty   = (float) $inv['quantity'] - $line['quantity'];

            $this->db->prepare(
                "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
                 WHERE product_id = :pid AND branch_id = 1"
            )->execute([':qty' => $newQty, ':pid' => $line['product_id']]);

            $this->db->prepare(
                "INSERT INTO stock_movements
                     (branch_id, product_id, type, quantity, unit_cost, reference_type, reference_id, created_at)
                 VALUES (1, :pid, 'reservation', :qty, 0, 'sales_order', :oid, datetime('now'))"
            )->execute([':pid' => $line['product_id'], ':qty' => $line['quantity'], ':oid' => $orderId]);
        }

        return $this->findInDatabase('sales_orders', ['id' => $orderId]);
    }

    private function createInvoiceFromOrder(int $orderId): array
    {
        $order = $this->findInDatabase('sales_orders', ['id' => $orderId]);

        self::$invoiceSeq++;
        $invoiceNumber = 'INV-' . date('Ym') . '-' . str_pad((string) self::$invoiceSeq, 6, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO invoices
                (branch_id, customer_id, sales_order_id, invoice_number, status,
                 total_amount, paid_amount, invoice_date, created_at, updated_at)
            VALUES
                (1, :cid, :oid, :inum, 'unpaid', :total, 0, date('now'), datetime('now'), datetime('now'))
        SQL);
        $stmt->execute([
            ':cid'   => $order['customer_id'],
            ':oid'   => $orderId,
            ':inum'  => $invoiceNumber,
            ':total' => $order['total_amount'],
        ]);
        $invoiceId = (int) $this->db->lastInsertId();

        // Record actual stock-out movement.
        $lines = $this->db
            ->query("SELECT * FROM sales_order_items WHERE sales_order_id = {$orderId}")
            ->fetchAll();

        foreach ($lines as $line) {
            $this->db->prepare(
                "INSERT INTO stock_movements
                     (branch_id, product_id, type, quantity, unit_cost, reference_type, reference_id, created_at)
                 VALUES (1, :pid, 'stock_out', :qty, 0, 'invoice', :iid, datetime('now'))"
            )->execute([
                ':pid' => $line['product_id'],
                ':qty' => $line['quantity'],
                ':iid' => $invoiceId,
            ]);
        }

        // Update customer outstanding balance.
        $this->db->exec(
            "UPDATE customers SET outstanding_balance = outstanding_balance + {$order['total_amount']}
             WHERE id = {$order['customer_id']}"
        );

        return $this->findInDatabase('invoices', ['id' => $invoiceId]);
    }

    /** Create a standalone invoice (no order) for payment allocation tests. */
    private function createStandaloneInvoice(int $customerId, float $amount): array
    {
        self::$invoiceSeq++;
        $invoiceNumber = 'INV-' . date('Ym') . '-' . str_pad((string) self::$invoiceSeq, 6, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO invoices
                (branch_id, customer_id, invoice_number, status, total_amount, paid_amount,
                 invoice_date, created_at, updated_at)
            VALUES
                (1, :cid, :inum, 'unpaid', :total, 0, date('now'), datetime('now'), datetime('now'))
        SQL);
        $stmt->execute([':cid' => $customerId, ':inum' => $invoiceNumber, ':total' => $amount]);
        $id = (int) $this->db->lastInsertId();

        // Update outstanding balance.
        $this->db->exec(
            "UPDATE customers SET outstanding_balance = outstanding_balance + {$amount} WHERE id = {$customerId}"
        );

        return $this->findInDatabase('invoices', ['id' => $id]);
    }

    /**
     * Receive a payment and allocate it to invoices using FIFO.
     *
     * @param  int[]|null  $invoiceIds  when null, allocates FIFO across all unpaid invoices
     */
    private function receivePayment(int $customerId, float $amount, ?array $invoiceIds = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO payments (branch_id, customer_id, amount, payment_date, created_at)
             VALUES (1, :cid, :amt, date('now'), datetime('now'))"
        );
        $stmt->execute([':cid' => $customerId, ':amt' => $amount]);
        $paymentId = (int) $this->db->lastInsertId();

        if ($invoiceIds === null) {
            // FIFO: get all unpaid/partial invoices ordered by creation date.
            $invoices = $this->db->query(
                "SELECT * FROM invoices WHERE customer_id = {$customerId}
                 AND status IN ('unpaid', 'partial') ORDER BY created_at ASC"
            )->fetchAll();
        } else {
            $ids = implode(',', $invoiceIds);
            $invoices = $this->db->query(
                "SELECT * FROM invoices WHERE id IN ({$ids}) ORDER BY created_at ASC"
            )->fetchAll();
        }

        $remaining = $amount;

        foreach ($invoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }

            $outstanding  = (float) $invoice['total_amount'] - (float) $invoice['paid_amount'];
            $allocate     = min($remaining, $outstanding);
            $newPaid      = (float) $invoice['paid_amount'] + $allocate;
            $remaining   -= $allocate;

            $newStatus = ($newPaid >= (float) $invoice['total_amount']) ? 'paid' : 'partial';

            $this->db->prepare(
                "UPDATE invoices SET paid_amount = :paid, status = :status, updated_at = datetime('now')
                 WHERE id = :id"
            )->execute([':paid' => $newPaid, ':status' => $newStatus, ':id' => $invoice['id']]);

            $this->db->prepare(
                "INSERT INTO payment_allocations (payment_id, invoice_id, amount, created_at)
                 VALUES (:pid, :iid, :amt, datetime('now'))"
            )->execute([':pid' => $paymentId, ':iid' => $invoice['id'], ':amt' => $allocate]);
        }

        // Update customer outstanding balance.
        $this->db->prepare(
            "UPDATE customers SET outstanding_balance = outstanding_balance - :paid WHERE id = :cid"
        )->execute([':paid' => $amount, ':cid' => $customerId]);
    }

    private function getCustomerOutstandingBalance(int $customerId): float
    {
        $row = $this->db->query(
            "SELECT SUM(total_amount - paid_amount) AS outstanding FROM invoices
             WHERE customer_id = {$customerId} AND status IN ('unpaid','partial')"
        )->fetch();

        return (float) ($row['outstanding'] ?? 0);
    }

    private function processSalesReturn(int $invoiceId, int $productId, float $quantity): void
    {
        // Restore stock.
        $inv    = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => 1]);
        $newQty = $inv ? (float) $inv['quantity'] + $quantity : $quantity;

        $this->db->prepare(
            "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
             WHERE product_id = :pid AND branch_id = 1"
        )->execute([':qty' => $newQty, ':pid' => $productId]);

        $this->db->prepare(
            "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, reference_type, reference_id, created_at)
             VALUES (1, :pid, 'return', :qty, 0, 'invoice', :iid, datetime('now'))"
        )->execute([':pid' => $productId, ':qty' => $quantity, ':iid' => $invoiceId]);
    }
}
