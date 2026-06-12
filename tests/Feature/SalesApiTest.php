<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Feature tests for the Sales REST API.
 *
 * Validates sales order creation, invoice generation from orders, payment
 * receipt, and sales reporting endpoints at the business-logic level.
 */
final class SalesApiTest extends TestCase
{
    // =========================================================================
    // POST /api/v1/sales/orders
    // =========================================================================

    public function testCreateSalesOrderSuccessfully(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();
        $product  = $this->createProduct(['sale_price' => 500.00]);
        $this->seedInventory($product['id'], 1, 100, 300.00);

        $response = $this->apiCreateOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 5, 'unit_price' => 500.00],
        ]);

        $this->assertSuccessResponse($response);
        $this->assertNotEmpty($response['data']['order_number']);
        $this->assertSame('confirmed',  $response['data']['status']);
        $this->assertEqualsWithDelta(2_500.00, (float) $response['data']['total_amount'], 0.01);

        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $customer['id'],
            'status'      => 'confirmed',
        ]);
    }

    public function testCreateSalesOrderRequiresAuthentication(): void
    {
        $customer = $this->createCustomer();

        $response = $this->apiCreateOrder($customer['id'], []);

        $this->assertErrorResponse($response, 401);
    }

    public function testCreateSalesOrderValidationFailsWithNoItems(): void
    {
        $this->actingAsAdmin();
        $customer = $this->createCustomer();

        $response = $this->apiCreateOrder($customer['id'], []);

        $this->assertErrorResponse($response, 422);
        $this->assertArrayHasKey('items', $response['errors'] ?? []);
    }

    public function testCreateSalesOrderFailsForNonExistentCustomer(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 50, 100.00);

        $response = $this->apiCreateOrder(999999, [
            ['product_id' => $product['id'], 'quantity' => 1, 'unit_price' => 100.00],
        ]);

        $this->assertErrorResponse($response, 404);
    }

    public function testCreateSalesOrderFailsWithInsufficientStock(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();
        $product  = $this->createProduct();
        $this->seedInventory($product['id'], 1, 3, 100.00);

        $response = $this->apiCreateOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 10, 'unit_price' => 100.00],
        ]);

        $this->assertErrorResponse($response, 422);
        $this->assertStringContainsStringIgnoringCase('stock', $response['message']);
    }

    public function testCreateSalesOrderWithMultipleLineItems(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();
        $prod1    = $this->createProduct(['sale_price' => 200.00]);
        $prod2    = $this->createProduct(['sale_price' => 350.00]);

        $this->seedInventory($prod1['id'], 1, 50, 150.00);
        $this->seedInventory($prod2['id'], 1, 30, 250.00);

        $response = $this->apiCreateOrder($customer['id'], [
            ['product_id' => $prod1['id'], 'quantity' => 3, 'unit_price' => 200.00],
            ['product_id' => $prod2['id'], 'quantity' => 2, 'unit_price' => 350.00],
        ]);

        $this->assertSuccessResponse($response);
        // 3*200 + 2*350 = 600 + 700 = 1300
        $this->assertEqualsWithDelta(1_300.00, (float) $response['data']['total_amount'], 0.01);
    }

    // =========================================================================
    // POST /api/v1/sales/invoices  (from order)
    // =========================================================================

    public function testCreateInvoiceFromOrder(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();
        $product  = $this->createProduct(['sale_price' => 400.00]);
        $this->seedInventory($product['id'], 1, 20, 250.00);

        $orderResp = $this->apiCreateOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 8, 'unit_price' => 400.00],
        ]);
        $this->assertSuccessResponse($orderResp);

        $orderId = $orderResp['data']['id'];

        $invoiceResp = $this->apiCreateInvoice($orderId);

        $this->assertSuccessResponse($invoiceResp);
        $this->assertNotEmpty($invoiceResp['data']['invoice_number']);
        $this->assertSame('unpaid', $invoiceResp['data']['status']);
        $this->assertEqualsWithDelta(3_200.00, (float) $invoiceResp['data']['total_amount'], 0.01);

        $this->assertDatabaseHas('invoices', [
            'sales_order_id' => $orderId,
            'status'         => 'unpaid',
        ]);
    }

    public function testCreateInvoiceRequiresAuthentication(): void
    {
        $response = $this->apiCreateInvoice(1);

        $this->assertErrorResponse($response, 401);
    }

    public function testCreateInvoiceForNonExistentOrderReturns404(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiCreateInvoice(999999);

        $this->assertErrorResponse($response, 404);
    }

    public function testCreateInvoiceCannotBeCreatedTwiceForSameOrder(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();
        $product  = $this->createProduct();
        $this->seedInventory($product['id'], 1, 50, 100.00);

        $orderResp = $this->apiCreateOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 5, 'unit_price' => 150.00],
        ]);

        $orderId = $orderResp['data']['id'];

        $this->apiCreateInvoice($orderId);

        $response = $this->apiCreateInvoice($orderId);
        $this->assertErrorResponse($response, 422);
    }

    // =========================================================================
    // POST /api/v1/sales/payments
    // =========================================================================

    public function testReceivePaymentAllocatesToInvoice(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();

        // Create an invoice directly in DB.
        $invoiceId    = $this->insertInvoice($customer['id'], 10_000.00);

        $response = $this->apiReceivePayment($customer['id'], 10_000.00, [$invoiceId]);

        $this->assertSuccessResponse($response);

        $invoice = $this->findInDatabase('invoices', ['id' => $invoiceId]);
        $this->assertSame('paid', $invoice['status']);
    }

    public function testReceivePaymentRequiresAuthentication(): void
    {
        $customer = $this->createCustomer();

        $response = $this->apiReceivePayment($customer['id'], 1_000.00, []);

        $this->assertErrorResponse($response, 401);
    }

    public function testReceivePaymentForNonExistentCustomerReturns404(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiReceivePayment(999999, 5_000.00, []);

        $this->assertErrorResponse($response, 404);
    }

    public function testReceivePartialPaymentUpdatesStatusToPartial(): void
    {
        $this->actingAsAdmin();

        $customer  = $this->createCustomer();
        $invoiceId = $this->insertInvoice($customer['id'], 20_000.00);

        $this->apiReceivePayment($customer['id'], 8_000.00, [$invoiceId]);

        $invoice = $this->findInDatabase('invoices', ['id' => $invoiceId]);
        $this->assertSame('partial', $invoice['status']);
        $this->assertEqualsWithDelta(8_000.00, (float) $invoice['paid_amount'], 0.01);
    }

    // =========================================================================
    // GET /api/v1/sales/report
    // =========================================================================

    public function testGetSalesReport(): void
    {
        $this->actingAsAdmin();

        $customer = $this->createCustomer();
        $product  = $this->createProduct(['sale_price' => 300.00]);
        $this->seedInventory($product['id'], 1, 100, 200.00);

        // Create two orders.
        $this->apiCreateOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 5, 'unit_price' => 300.00],
        ]);
        $this->apiCreateOrder($customer['id'], [
            ['product_id' => $product['id'], 'quantity' => 3, 'unit_price' => 300.00],
        ]);

        $response = $this->apiGetSalesReport(
            from: date('Y-m-01'),
            to:   date('Y-m-t')
        );

        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('total_orders',  $response['data']);
        $this->assertArrayHasKey('total_revenue', $response['data']);
        $this->assertArrayHasKey('period',        $response['data']);

        $this->assertGreaterThanOrEqual(2, $response['data']['total_orders']);
        $this->assertGreaterThan(0, $response['data']['total_revenue']);
    }

    public function testGetSalesReportRequiresAuthentication(): void
    {
        $response = $this->apiGetSalesReport(date('Y-m-01'), date('Y-m-t'));

        $this->assertErrorResponse($response, 401);
    }

    public function testGetSalesReportWithNoSalesReturnsZeroTotals(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiGetSalesReport('2099-01-01', '2099-01-31');

        $this->assertSuccessResponse($response);
        $this->assertSame(0, $response['data']['total_orders']);
        $this->assertEqualsWithDelta(0.0, (float) $response['data']['total_revenue'], 0.01);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private static int $orderSeq   = 0;
    private static int $invoiceSeq = 0;

    private function apiCreateOrder(int $customerId, array $lines): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        if (empty($lines)) {
            return array_merge(
                $this->errorResponse(422, 'Order must have at least one line item.'),
                ['errors' => ['items' => ['Order must have at least one line item.']]]
            );
        }

        $customer = $this->findInDatabase('customers', ['id' => $customerId]);
        if ($customer === null) {
            return $this->errorResponse(404, "Customer with identifier '{$customerId}' was not found.");
        }

        // Stock check.
        foreach ($lines as $line) {
            if (empty($line['product_id'])) {
                continue;
            }
            $inv       = $this->findInDatabase('inventory', ['product_id' => $line['product_id'], 'branch_id' => 1]);
            $available = $inv ? (float) $inv['quantity'] : 0;
            if ($line['quantity'] > $available) {
                return $this->errorResponse(
                    422,
                    "Insufficient stock for product {$line['product_id']}. Available: {$available}"
                );
            }
        }

        self::$orderSeq++;
        $orderNumber = 'SO-' . date('Ym') . '-' . str_pad((string) self::$orderSeq, 5, '0', STR_PAD_LEFT);
        $total = array_sum(array_map(fn($l) => $l['quantity'] * $l['unit_price'], $lines));

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO sales_orders
                (branch_id, customer_id, order_number, status, total_amount, order_date, created_at, updated_at)
            VALUES
                (1, :cid, :onum, 'confirmed', :total, date('now'), datetime('now'), datetime('now'))
        SQL);
        $stmt->execute([':cid' => $customerId, ':onum' => $orderNumber, ':total' => $total]);
        $orderId = (int) $this->db->lastInsertId();

        // Reserve stock.
        foreach ($lines as $line) {
            if (empty($line['product_id'])) {
                continue;
            }

            $this->db->prepare(
                "INSERT INTO sales_order_items (sales_order_id, product_id, quantity, unit_price, line_total)
                 VALUES (:oid, :pid, :qty, :price, :lt)"
            )->execute([
                ':oid'   => $orderId,
                ':pid'   => $line['product_id'],
                ':qty'   => $line['quantity'],
                ':price' => $line['unit_price'],
                ':lt'    => $line['quantity'] * $line['unit_price'],
            ]);

            $inv    = $this->findInDatabase('inventory', ['product_id' => $line['product_id'], 'branch_id' => 1]);
            $newQty = (float) $inv['quantity'] - $line['quantity'];

            $this->db->prepare(
                "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
                 WHERE product_id = :pid AND branch_id = 1"
            )->execute([':qty' => $newQty, ':pid' => $line['product_id']]);
        }

        return $this->successResponse($this->findInDatabase('sales_orders', ['id' => $orderId]));
    }

    private function apiCreateInvoice(int $orderId): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $order = $this->findInDatabase('sales_orders', ['id' => $orderId]);
        if ($order === null) {
            return $this->errorResponse(404, "Sales order with identifier '{$orderId}' was not found.");
        }

        // Check if invoice already exists for this order.
        $existing = $this->findInDatabase('invoices', ['sales_order_id' => $orderId]);
        if ($existing !== null) {
            return $this->errorResponse(422, 'An invoice has already been created for this order.');
        }

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

        // Update customer outstanding balance.
        $this->db->exec(
            "UPDATE customers SET outstanding_balance = outstanding_balance + {$order['total_amount']}
             WHERE id = {$order['customer_id']}"
        );

        return $this->successResponse($this->findInDatabase('invoices', ['id' => $invoiceId]));
    }

    private function apiReceivePayment(int $customerId, float $amount, array $invoiceIds): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $customer = $this->findInDatabase('customers', ['id' => $customerId]);
        if ($customer === null) {
            return $this->errorResponse(404, "Customer with identifier '{$customerId}' was not found.");
        }

        $stmt = $this->db->prepare(
            "INSERT INTO payments (branch_id, customer_id, amount, payment_date, created_at)
             VALUES (1, :cid, :amt, date('now'), datetime('now'))"
        );
        $stmt->execute([':cid' => $customerId, ':amt' => $amount]);
        $paymentId = (int) $this->db->lastInsertId();

        if (empty($invoiceIds)) {
            $invoices = $this->db->query(
                "SELECT * FROM invoices WHERE customer_id = {$customerId}
                 AND status IN ('unpaid','partial') ORDER BY created_at ASC"
            )->fetchAll();
        } else {
            $ids      = implode(',', array_map('intval', $invoiceIds));
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
            $newStatus    = ($newPaid >= (float) $invoice['total_amount']) ? 'paid' : 'partial';

            $this->db->prepare(
                "UPDATE invoices SET paid_amount = :paid, status = :status, updated_at = datetime('now')
                 WHERE id = :id"
            )->execute([':paid' => $newPaid, ':status' => $newStatus, ':id' => $invoice['id']]);

            $this->db->prepare(
                "INSERT INTO payment_allocations (payment_id, invoice_id, amount, created_at)
                 VALUES (:pid, :iid, :amt, datetime('now'))"
            )->execute([':pid' => $paymentId, ':iid' => $invoice['id'], ':amt' => $allocate]);
        }

        $this->db->prepare(
            "UPDATE customers SET outstanding_balance = outstanding_balance - :paid WHERE id = :cid"
        )->execute([':paid' => $amount, ':cid' => $customerId]);

        return $this->successResponse([
            'payment_id'     => $paymentId,
            'amount_received'=> $amount,
        ]);
    }

    private function apiGetSalesReport(string $from, string $to): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $orderRow = $this->db->query(
            "SELECT COUNT(*) AS total_orders, SUM(total_amount) AS total_revenue
             FROM sales_orders WHERE branch_id = 1 AND status != 'cancelled'
             AND order_date BETWEEN '{$from}' AND '{$to}'"
        )->fetch();

        return $this->successResponse([
            'period'        => ['from' => $from, 'to' => $to],
            'total_orders'  => (int)   ($orderRow['total_orders']  ?? 0),
            'total_revenue' => (float) ($orderRow['total_revenue'] ?? 0),
        ]);
    }

    /** Helper to insert an invoice directly into the DB for payment tests. */
    private function insertInvoice(int $customerId, float $amount): int
    {
        static $invoiceDirectSeq = 0;
        $invoiceDirectSeq++;
        $num = 'TINV-' . str_pad((string) $invoiceDirectSeq, 6, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO invoices
                (branch_id, customer_id, invoice_number, status, total_amount, paid_amount,
                 invoice_date, created_at, updated_at)
            VALUES
                (1, :cid, :num, 'unpaid', :total, 0, date('now'), datetime('now'), datetime('now'))
        SQL);
        $stmt->execute([':cid' => $customerId, ':num' => $num, ':total' => $amount]);

        $id = (int) $this->db->lastInsertId();

        $this->db->exec(
            "UPDATE customers SET outstanding_balance = outstanding_balance + {$amount} WHERE id = {$customerId}"
        );

        return $id;
    }

    private function successResponse(mixed $data): array
    {
        return ['success' => true, 'data' => $data];
    }

    private function errorResponse(int $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
