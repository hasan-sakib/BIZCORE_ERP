<?php

declare(strict_types=1);

namespace App\Repositories;

use RuntimeException;

/**
 * SalesRepository
 *
 * All SQL for the Sales module: quotations, sales orders, invoices, and payments.
 * No business logic — pure data access only.  Every mutating operation that
 * touches more than one table is wrapped in a transaction.
 */
final class SalesRepository extends BaseRepository
{
    // =========================================================================
    // INVOICES
    // =========================================================================

    /**
     * Return a paginated, filtered list of invoices joined to customers.
     *
     * Supported filters: customer_id, status, date_from, date_to, search (reference_no)
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string,mixed>>, total: int, page: int, lastPage: int, perPage: int}
     */
    public function paginateInvoices(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        [$clauses, $params] = $this->buildInvoiceFilters($filters);
        $where = 'WHERE ' . implode(' AND ', $clauses);

        $total = $this->count(
            "SELECT COUNT(*) AS total
             FROM invoices i
             LEFT JOIN customers c ON c.id = i.customer_id
             {$where}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT i.*, c.name AS customer_name
             FROM invoices i
             LEFT JOIN customers c ON c.id = i.customer_id
             {$where}
             ORDER BY i.invoice_date DESC, i.id DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'perPage'  => $perPage,
        ];
    }

    /**
     * Find a single invoice by ID including its line items and customer details.
     *
     * @return array<string, mixed>|null
     */
    public function findInvoice(int $id): ?array
    {
        $invoice = $this->fetchOne(
            "SELECT i.*, c.name AS customer_name, c.email AS customer_email,
                    c.phone AS customer_phone
             FROM invoices i
             LEFT JOIN customers c ON c.id = i.customer_id
             WHERE i.id = :id AND i.deleted_at IS NULL
             LIMIT 1",
            [':id' => $id],
        );

        if ($invoice === null) {
            return null;
        }

        $invoice['items'] = $this->fetchAll(
            "SELECT ii.*, p.name AS product_name
             FROM invoice_items ii
             LEFT JOIN products p ON p.id = ii.product_id
             WHERE ii.invoice_id = :id",
            [':id' => $id],
        );

        $invoice['payments'] = [];

        return $invoice;
    }

    /**
     * Insert a new invoice with its line items inside a transaction.
     * Returns the new invoice ID.
     *
     * @param  array<string, mixed>         $data
     * @param  list<array<string, mixed>>   $items
     */
    public function createInvoice(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $this->execute(
                "INSERT INTO invoices
                    (invoice_number, customer_id, sales_order_id, invoice_date, due_date,
                     notes, discount_amount, subtotal, vat_amount, total_amount,
                     paid_amount, balance, status, created_by, created_at, updated_at)
                 VALUES
                    (:invoice_number, :customer_id, :sales_order_id, :invoice_date, :due_date,
                     :notes, :discount_amount, :subtotal, :vat_amount, :total_amount,
                     0, :total_amount, :status, :created_by, NOW(), NOW())",
                [
                    ':invoice_number'  => $data['invoice_number'],
                    ':customer_id'     => (int) $data['customer_id'],
                    ':sales_order_id'  => isset($data['sales_order_id']) && $data['sales_order_id'] ? (int) $data['sales_order_id'] : null,
                    ':invoice_date'    => $data['invoice_date'],
                    ':due_date'        => $data['due_date'],
                    ':notes'           => $data['notes'] ?? null,
                    ':discount_amount' => (float) ($data['discount_amount'] ?? 0),
                    ':subtotal'        => (float) ($data['subtotal'] ?? 0),
                    ':vat_amount'      => (float) ($data['vat_amount'] ?? 0),
                    ':total_amount'    => (float) ($data['total_amount'] ?? 0),
                    ':status'          => $data['status'] ?? 'draft',
                    ':created_by'      => (int) $data['created_by'],
                ],
            );

            $invoiceId = $this->lastInsertId();
            $this->insertInvoiceItems($invoiceId, $items);

            return $invoiceId;
        });
    }

    /**
     * Update an existing invoice and replace all its line items.
     *
     * @param  array<string, mixed>       $data
     * @param  list<array<string, mixed>> $items
     */
    public function updateInvoice(int $id, array $data, array $items): void
    {
        $this->transaction(function () use ($id, $data, $items): void {
            $this->modify(
                "UPDATE invoices
                 SET customer_id     = :customer_id,
                     invoice_date    = :invoice_date,
                     due_date        = :due_date,
                     notes           = :notes,
                     discount_amount = :discount_amount,
                     subtotal        = :subtotal,
                     vat_amount      = :vat_amount,
                     total_amount    = :total_amount,
                     balance         = (total_amount - paid_amount),
                     status          = :status,
                     updated_at      = NOW()
                 WHERE id = :id AND deleted_at IS NULL",
                [
                    ':id'              => $id,
                    ':customer_id'     => (int) $data['customer_id'],
                    ':invoice_date'    => $data['invoice_date'],
                    ':due_date'        => $data['due_date'],
                    ':notes'           => $data['notes'] ?? null,
                    ':discount_amount' => (float) ($data['discount_amount'] ?? 0),
                    ':subtotal'        => (float) ($data['subtotal'] ?? 0),
                    ':vat_amount'      => (float) ($data['vat_amount'] ?? 0),
                    ':total_amount'    => (float) ($data['total_amount'] ?? 0),
                    ':status'          => $data['status'] ?? 'draft',
                ],
            );

            $this->modify('DELETE FROM invoice_items WHERE invoice_id = :id', [':id' => $id]);
            $this->insertInvoiceItems($id, $items);
        });
    }

    /**
     * Set the status of an invoice to 'void'.
     */
    public function voidInvoice(int $id): void
    {
        $this->modify(
            "UPDATE invoices SET status = 'void', updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id],
        );
    }

    /**
     * Insert a payment record and update the invoice paid_amount / status.
     *
     * @param array<string, mixed> $paymentData
     */
    public function recordPayment(int $invoiceId, array $paymentData): void
    {
        $this->transaction(function () use ($invoiceId, $paymentData): void {
            $invoice = $this->fetchOne(
                'SELECT total_amount, paid_amount, customer_id, branch_id FROM invoices WHERE id = :id AND deleted_at IS NULL',
                [':id' => $invoiceId],
            );

            if ($invoice === null) {
                throw new RuntimeException('Invoice not found.');
            }

            $amount     = (float) $paymentData['amount'];
            $newPaid    = (float) $invoice['paid_amount'] + $amount;
            $total      = (float) $invoice['total_amount'];
            $balanceDue = max(0, $total - $newPaid);

            $status = 'partial';
            if ($balanceDue <= 0) {
                $status = 'paid';
            }

            $this->execute(
                "INSERT INTO payments
                    (branch_id, payment_type, payer_type, payer_id, payment_number,
                     payment_date, amount, payment_method, reference_number, notes,
                     status, created_by, created_at, updated_at)
                 VALUES
                    (:branch_id, 'received', 'customer', :payer_id, :payment_number,
                     :payment_date, :amount, :payment_method, :reference_number, :notes,
                     'completed', :created_by, NOW(), NOW())",
                [
                    ':branch_id'        => (int) ($paymentData['branch_id'] ?? $invoice['branch_id'] ?? 1),
                    ':payer_id'         => (int) ($paymentData['customer_id'] ?? $invoice['customer_id']),
                    ':payment_number'   => $paymentData['payment_number'] ?? $this->generatePaymentRef(),
                    ':payment_date'     => $paymentData['payment_date'],
                    ':amount'           => $amount,
                    ':payment_method'   => $paymentData['payment_method'] ?? 'cash',
                    ':reference_number' => $paymentData['reference_number'] ?? null,
                    ':notes'            => $paymentData['notes'] ?? null,
                    ':created_by'       => (int) $paymentData['created_by'],
                ],
            );

            $this->modify(
                "UPDATE invoices
                 SET paid_amount = :paid, balance = :balance, status = :status, updated_at = NOW()
                 WHERE id = :id",
                [
                    ':paid'    => $newPaid,
                    ':balance' => $balanceDue,
                    ':status'  => $status,
                    ':id'      => $invoiceId,
                ],
            );
        });
    }

    /**
     * Generate a unique invoice reference number in the format INV-YYYYNNNNN.
     */
    public function generateInvoiceRef(): string
    {
        $year = date('Y');
        $row  = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM invoices WHERE invoice_number LIKE :pattern",
            [':pattern' => 'INV-' . $year . '%'],
        );
        $next = ((int) ($row['total'] ?? 0)) + 1;
        return 'INV-' . $year . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // QUOTATIONS
    // =========================================================================

    /**
     * Return a paginated, filtered list of quotations.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string,mixed>>, total: int, page: int, lastPage: int, perPage: int}
     */
    public function paginateQuotations(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        [$clauses, $params] = $this->buildQuotationFilters($filters);
        $where = $clauses !== [] ? 'WHERE ' . implode(' AND ', $clauses) : '';

        $total = $this->count(
            "SELECT COUNT(*) AS total
             FROM quotations q
             LEFT JOIN customers c ON c.id = q.customer_id
             {$where}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT q.*, c.name AS customer_name
             FROM quotations q
             LEFT JOIN customers c ON c.id = q.customer_id
             {$where}
             ORDER BY q.date DESC, q.id DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'perPage'  => $perPage,
        ];
    }

    /**
     * Find a single quotation by ID with its items and customer.
     *
     * @return array<string, mixed>|null
     */
    public function findQuotation(int $id): ?array
    {
        $quotation = $this->fetchOne(
            "SELECT q.*, c.name AS customer_name, c.email AS customer_email,
                    c.phone AS customer_phone
             FROM quotations q
             LEFT JOIN customers c ON c.id = q.customer_id
             WHERE q.id = :id
             LIMIT 1",
            [':id' => $id],
        );

        if ($quotation === null) {
            return null;
        }

        $quotation['items'] = $this->fetchAll(
            "SELECT qi.*, p.name AS product_name
             FROM quotation_items qi
             LEFT JOIN products p ON p.id = qi.product_id
             WHERE qi.quotation_id = :id",
            [':id' => $id],
        );

        return $quotation;
    }

    /**
     * Insert a new quotation with its line items.
     *
     * @param  array<string, mixed>       $data
     * @param  list<array<string, mixed>> $items
     */
    public function createQuotation(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $this->execute(
                "INSERT INTO quotations
                    (quotation_number, customer_id, date, expiry_date,
                     notes, discount_amount, subtotal, vat_amount, total_amount,
                     status, created_by, created_at, updated_at)
                 VALUES
                    (:quotation_number, :customer_id, :date, :expiry_date,
                     :notes, :discount_amount, :subtotal, :vat_amount, :total_amount,
                     :status, :created_by, NOW(), NOW())",
                [
                    ':quotation_number' => $data['quotation_number'],
                    ':customer_id'      => (int) $data['customer_id'],
                    ':date'             => $data['date'],
                    ':expiry_date'      => $data['expiry_date'] ?? null,
                    ':notes'            => $data['notes'] ?? null,
                    ':discount_amount'  => (float) ($data['discount_amount'] ?? 0),
                    ':subtotal'         => (float) ($data['subtotal'] ?? 0),
                    ':vat_amount'       => (float) ($data['vat_amount'] ?? 0),
                    ':total_amount'     => (float) ($data['total_amount'] ?? 0),
                    ':status'           => $data['status'] ?? 'draft',
                    ':created_by'       => (int) $data['created_by'],
                ],
            );

            $quotationId = $this->lastInsertId();
            $this->insertQuotationItems($quotationId, $items);

            return $quotationId;
        });
    }

    /**
     * Update an existing quotation and replace its line items.
     *
     * @param  array<string, mixed>       $data
     * @param  list<array<string, mixed>> $items
     */
    public function updateQuotation(int $id, array $data, array $items): void
    {
        $this->transaction(function () use ($id, $data, $items): void {
            $this->modify(
                "UPDATE quotations
                 SET customer_id     = :customer_id,
                     date            = :date,
                     expiry_date     = :expiry_date,
                     notes           = :notes,
                     discount_amount = :discount_amount,
                     subtotal        = :subtotal,
                     vat_amount      = :vat_amount,
                     total_amount    = :total_amount,
                     status          = :status,
                     updated_at      = NOW()
                 WHERE id = :id",
                [
                    ':id'              => $id,
                    ':customer_id'     => (int) $data['customer_id'],
                    ':date'            => $data['date'],
                    ':expiry_date'     => $data['expiry_date'] ?? null,
                    ':notes'           => $data['notes'] ?? null,
                    ':discount_amount' => (float) ($data['discount_amount'] ?? 0),
                    ':subtotal'        => (float) ($data['subtotal'] ?? 0),
                    ':vat_amount'      => (float) ($data['vat_amount'] ?? 0),
                    ':total_amount'    => (float) ($data['total_amount'] ?? 0),
                    ':status'          => $data['status'] ?? 'draft',
                ],
            );

            $this->modify('DELETE FROM quotation_items WHERE quotation_id = :id', [':id' => $id]);
            $this->insertQuotationItems($id, $items);
        });
    }

    /**
     * Soft-delete a quotation.
     */
    public function deleteQuotation(int $id): void
    {
        $this->modify(
            "DELETE FROM quotations WHERE id = :id",
            [':id' => $id],
        );
    }

    /**
     * Convert a quotation to a sales order.
     * Marks the quotation as 'accepted' and returns the new order ID.
     */
    public function convertToOrder(int $quotationId, int $createdBy): int
    {
        return $this->transaction(function () use ($quotationId, $createdBy): int {
            $quotation = $this->findQuotation($quotationId);

            if ($quotation === null) {
                throw new RuntimeException('Quotation not found.');
            }

            $ref = $this->generateOrderRef();

            $this->execute(
                "INSERT INTO sales_orders
                    (order_number, customer_id, quotation_id, order_date, warehouse_id,
                     notes, discount_amount, subtotal, vat_amount, total_amount,
                     status, created_by, created_at, updated_at)
                 VALUES
                    (:order_number, :customer_id, :quotation_id, :order_date, :warehouse_id,
                     :notes, :discount_amount, :subtotal, :vat_amount, :total_amount,
                     'draft', :created_by, NOW(), NOW())",
                [
                    ':order_number'    => $ref,
                    ':customer_id'     => (int) $quotation['customer_id'],
                    ':quotation_id'    => $quotationId,
                    ':order_date'      => date('Y-m-d'),
                    ':warehouse_id'    => (int) ($quotation['warehouse_id'] ?? 1),
                    ':notes'           => $quotation['notes'],
                    ':discount_amount' => (float) ($quotation['discount_amount'] ?? 0),
                    ':subtotal'        => (float) $quotation['subtotal'],
                    ':vat_amount'      => (float) ($quotation['vat_amount'] ?? 0),
                    ':total_amount'    => (float) $quotation['total_amount'],
                    ':created_by'      => $createdBy,
                ],
            );

            $orderId = $this->lastInsertId();

            foreach ($quotation['items'] as $item) {
                $this->execute(
                    "INSERT INTO sales_order_items
                        (order_id, product_id, description, quantity, unit_price, total)
                     VALUES
                        (:order_id, :product_id, :description, :quantity, :unit_price, :total)",
                    [
                        ':order_id'    => $orderId,
                        ':product_id'  => (int) $item['product_id'],
                        ':description' => $item['description'],
                        ':quantity'    => (float) $item['quantity'],
                        ':unit_price'  => (float) $item['unit_price'],
                        ':total'       => (float) $item['total'],
                    ],
                );
            }

            $this->modify(
                "UPDATE quotations SET status = 'accepted', updated_at = NOW() WHERE id = :id",
                [':id' => $quotationId],
            );

            return $orderId;
        });
    }

    /**
     * Generate a unique quotation reference number in the format QUO-YYYYNNNNN.
     */
    public function generateQuotationRef(): string
    {
        $year = date('Y');
        $row  = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM quotations WHERE quotation_number LIKE :pattern",
            [':pattern' => 'QUO-' . $year . '%'],
        );
        $next = ((int) ($row['total'] ?? 0)) + 1;
        return 'QUO-' . $year . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // SALES ORDERS
    // =========================================================================

    /**
     * Return a paginated, filtered list of sales orders.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string,mixed>>, total: int, page: int, lastPage: int, perPage: int}
     */
    public function paginateOrders(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        [$clauses, $params] = $this->buildOrderFilters($filters);
        $where = 'WHERE ' . implode(' AND ', $clauses);

        $total = $this->count(
            "SELECT COUNT(*) AS total
             FROM sales_orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             {$where}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT o.*, c.name AS customer_name
             FROM sales_orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             {$where}
             ORDER BY o.order_date DESC, o.id DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'perPage'  => $perPage,
        ];
    }

    /**
     * Find a single sales order by ID with its items and customer.
     *
     * @return array<string, mixed>|null
     */
    public function findOrder(int $id): ?array
    {
        $order = $this->fetchOne(
            "SELECT o.*, c.name AS customer_name, c.email AS customer_email,
                    c.phone AS customer_phone
             FROM sales_orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             WHERE o.id = :id AND o.deleted_at IS NULL
             LIMIT 1",
            [':id' => $id],
        );

        if ($order === null) {
            return null;
        }

        $order['items'] = $this->fetchAll(
            "SELECT soi.*, p.name AS product_name
             FROM sales_order_items soi
             LEFT JOIN products p ON p.id = soi.product_id
             WHERE soi.order_id = :id",
            [':id' => $id],
        );

        return $order;
    }

    /**
     * Insert a new sales order with its line items.
     *
     * @param  array<string, mixed>       $data
     * @param  list<array<string, mixed>> $items
     */
    public function createOrder(array $data, array $items): int
    {
        return $this->transaction(function () use ($data, $items): int {
            $this->execute(
                "INSERT INTO sales_orders
                    (order_number, customer_id, quotation_id, order_date, expected_delivery,
                     warehouse_id, notes, discount_amount, subtotal, vat_amount, total_amount,
                     status, created_by, created_at, updated_at)
                 VALUES
                    (:order_number, :customer_id, :quotation_id, :order_date, :expected_delivery,
                     :warehouse_id, :notes, :discount_amount, :subtotal, :vat_amount, :total_amount,
                     :status, :created_by, NOW(), NOW())",
                [
                    ':order_number'     => $data['order_number'],
                    ':customer_id'      => (int) $data['customer_id'],
                    ':quotation_id'     => isset($data['quotation_id']) && $data['quotation_id'] ? (int) $data['quotation_id'] : null,
                    ':order_date'       => $data['order_date'],
                    ':expected_delivery'=> $data['expected_delivery'] ?? null,
                    ':warehouse_id'     => (int) ($data['warehouse_id'] ?? 1),
                    ':notes'            => $data['notes'] ?? null,
                    ':discount_amount'  => (float) ($data['discount_amount'] ?? 0),
                    ':subtotal'         => (float) ($data['subtotal'] ?? 0),
                    ':vat_amount'       => (float) ($data['vat_amount'] ?? 0),
                    ':total_amount'     => (float) ($data['total_amount'] ?? 0),
                    ':status'           => $data['status'] ?? 'draft',
                    ':created_by'       => (int) $data['created_by'],
                ],
            );

            $orderId = $this->lastInsertId();
            $this->insertOrderItems($orderId, $items);

            return $orderId;
        });
    }

    /**
     * Update an existing sales order and replace its line items.
     *
     * @param  array<string, mixed>       $data
     * @param  list<array<string, mixed>> $items
     */
    public function updateOrder(int $id, array $data, array $items): void
    {
        $this->transaction(function () use ($id, $data, $items): void {
            $this->modify(
                "UPDATE sales_orders
                 SET customer_id       = :customer_id,
                     order_date        = :order_date,
                     expected_delivery = :expected_delivery,
                     notes             = :notes,
                     discount_amount   = :discount_amount,
                     subtotal          = :subtotal,
                     vat_amount        = :vat_amount,
                     total_amount      = :total_amount,
                     updated_at        = NOW()
                 WHERE id = :id AND deleted_at IS NULL",
                [
                    ':id'               => $id,
                    ':customer_id'      => (int) $data['customer_id'],
                    ':order_date'       => $data['order_date'],
                    ':expected_delivery'=> $data['expected_delivery'] ?? null,
                    ':notes'            => $data['notes'] ?? null,
                    ':discount_amount'  => (float) ($data['discount_amount'] ?? 0),
                    ':subtotal'         => (float) ($data['subtotal'] ?? 0),
                    ':vat_amount'       => (float) ($data['vat_amount'] ?? 0),
                    ':total_amount'     => (float) ($data['total_amount'] ?? 0),
                ],
            );

            $this->modify('DELETE FROM sales_order_items WHERE order_id = :id', [':id' => $id]);
            $this->insertOrderItems($id, $items);
        });
    }

    /**
     * Update the status of a sales order.
     */
    public function updateOrderStatus(int $id, string $status): void
    {
        $this->modify(
            "UPDATE sales_orders SET status = :status, updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL",
            [':status' => $status, ':id' => $id],
        );
    }

    /**
     * Create an invoice from a sales order and return the new invoice ID.
     */
    public function createInvoiceFromOrder(int $orderId, int $createdBy): int
    {
        return $this->transaction(function () use ($orderId, $createdBy): int {
            $order = $this->findOrder($orderId);

            if ($order === null) {
                throw new RuntimeException('Sales order not found.');
            }

            $ref = $this->generateInvoiceRef();

            $this->execute(
                "INSERT INTO invoices
                    (invoice_number, customer_id, sales_order_id, invoice_date, due_date,
                     notes, discount_amount, subtotal, vat_amount, total_amount,
                     paid_amount, balance, status, created_by, created_at, updated_at)
                 VALUES
                    (:invoice_number, :customer_id, :sales_order_id, :invoice_date, :due_date,
                     :notes, :discount_amount, :subtotal, :vat_amount, :total_amount,
                     0, :total_amount, 'draft', :created_by, NOW(), NOW())",
                [
                    ':invoice_number'  => $ref,
                    ':customer_id'     => (int) $order['customer_id'],
                    ':sales_order_id'  => $orderId,
                    ':invoice_date'    => date('Y-m-d'),
                    ':due_date'        => date('Y-m-d', strtotime('+30 days')),
                    ':notes'           => $order['notes'],
                    ':discount_amount' => (float) ($order['discount_amount'] ?? 0),
                    ':subtotal'        => (float) $order['subtotal'],
                    ':vat_amount'      => (float) ($order['vat_amount'] ?? 0),
                    ':total_amount'    => (float) $order['total_amount'],
                    ':created_by'      => $createdBy,
                ],
            );

            $invoiceId = $this->lastInsertId();

            foreach ($order['items'] as $item) {
                $this->execute(
                    "INSERT INTO invoice_items
                        (invoice_id, product_id, description, quantity, unit_price, discount, total)
                     VALUES
                        (:invoice_id, :product_id, :description, :quantity, :unit_price, 0, :total)",
                    [
                        ':invoice_id'   => $invoiceId,
                        ':product_id'   => (int) $item['product_id'],
                        ':description'  => $item['description'],
                        ':quantity'     => (float) $item['quantity'],
                        ':unit_price'   => (float) $item['unit_price'],
                        ':total'        => (float) $item['total'],
                    ],
                );
            }

            return $invoiceId;
        });
    }

    /**
     * Generate a unique sales order reference number in the format ORD-YYYYNNNNN.
     */
    public function generateOrderRef(): string
    {
        $year = date('Y');
        $row  = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM sales_orders WHERE order_number LIKE :pattern",
            [':pattern' => 'ORD-' . $year . '%'],
        );
        $next = ((int) ($row['total'] ?? 0)) + 1;
        return 'ORD-' . $year . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // PAYMENTS
    // =========================================================================

    /**
     * Return a paginated, filtered list of payments with invoice and customer info.
     *
     * @param  array<string, mixed> $filters
     * @return array{items: list<array<string,mixed>>, total: int, page: int, lastPage: int, perPage: int}
     */
    public function paginatePayments(array $filters, int $page, int $perPage = 20): array
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        $clauses = [];
        $params  = [];

        if (!empty($filters['customer_id'])) {
            $clauses[]              = "p.payer_id = :customer_id AND p.payer_type = 'customer'";
            $params[':customer_id'] = (int) $filters['customer_id'];
        }

        if (!empty($filters['payment_method'])) {
            $clauses[]                  = 'p.payment_method = :payment_method';
            $params[':payment_method']  = $filters['payment_method'];
        }

        if (!empty($filters['date_from'])) {
            $clauses[]           = 'p.payment_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[]         = 'p.payment_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $where = $clauses !== [] ? 'WHERE ' . implode(' AND ', $clauses) : '';

        $total = $this->count(
            "SELECT COUNT(*) AS total
             FROM payments p
             LEFT JOIN customers c ON c.id = p.payer_id AND p.payer_type = 'customer'
             {$where}",
            $params,
        );

        $items = $this->fetchAll(
            "SELECT p.*, c.name AS customer_name
             FROM payments p
             LEFT JOIN customers c ON c.id = p.payer_id AND p.payer_type = 'customer'
             {$where}
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'perPage'  => $perPage,
        ];
    }

    /**
     * Find a single payment by ID with its invoice and customer details.
     *
     * @return array<string, mixed>|null
     */
    public function findPayment(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT p.*, c.name AS customer_name,
                    c.email AS customer_email, c.phone AS customer_phone
             FROM payments p
             LEFT JOIN customers c ON c.id = p.payer_id AND p.payer_type = 'customer'
             WHERE p.id = :id
             LIMIT 1",
            [':id' => $id],
        );
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * @param  list<array<string, mixed>> $items
     */
    private function insertInvoiceItems(int $invoiceId, array $items): void
    {
        foreach ($items as $item) {
            $qty       = (float) ($item['quantity'] ?? 0);
            $price     = (float) ($item['unit_price'] ?? 0);
            $discount  = (float) ($item['discount'] ?? 0);
            $lineTotal = $qty * $price * (1 - $discount / 100);

            $this->execute(
                "INSERT INTO invoice_items
                    (invoice_id, product_id, description, quantity, unit_price, discount, total)
                 VALUES
                    (:invoice_id, :product_id, :description, :quantity, :unit_price, :discount, :total)",
                [
                    ':invoice_id'  => $invoiceId,
                    ':product_id'  => isset($item['product_id']) && $item['product_id'] !== '' ? (int) $item['product_id'] : null,
                    ':description' => $item['description'] ?? '',
                    ':quantity'    => $qty,
                    ':unit_price'  => $price,
                    ':discount'    => $discount,
                    ':total'       => $lineTotal,
                ],
            );
        }
    }

    /**
     * @param  list<array<string, mixed>> $items
     */
    private function insertQuotationItems(int $quotationId, array $items): void
    {
        foreach ($items as $item) {
            $qty       = (float) ($item['quantity'] ?? 0);
            $price     = (float) ($item['unit_price'] ?? 0);
            $discount  = (float) ($item['discount'] ?? 0);
            $lineTotal = $qty * $price * (1 - $discount / 100);

            $this->execute(
                "INSERT INTO quotation_items
                    (quotation_id, product_id, description, quantity, unit_price, discount, total)
                 VALUES
                    (:quotation_id, :product_id, :description, :quantity, :unit_price, :discount, :total)",
                [
                    ':quotation_id' => $quotationId,
                    ':product_id'   => isset($item['product_id']) && $item['product_id'] !== '' ? (int) $item['product_id'] : null,
                    ':description'  => $item['description'] ?? '',
                    ':quantity'     => $qty,
                    ':unit_price'   => $price,
                    ':discount'     => $discount,
                    ':total'        => $lineTotal,
                ],
            );
        }
    }

    /**
     * @param  list<array<string, mixed>> $items
     */
    private function insertOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $qty       = (float) ($item['quantity'] ?? 0);
            $price     = (float) ($item['unit_price'] ?? 0);
            $lineTotal = $qty * $price;

            $this->execute(
                "INSERT INTO sales_order_items
                    (order_id, product_id, description, quantity, unit_price, total)
                 VALUES
                    (:order_id, :product_id, :description, :quantity, :unit_price, :total)",
                [
                    ':order_id'    => $orderId,
                    ':product_id'  => isset($item['product_id']) && $item['product_id'] !== '' ? (int) $item['product_id'] : null,
                    ':description' => $item['description'] ?? '',
                    ':quantity'    => $qty,
                    ':unit_price'  => $price,
                    ':total'       => $lineTotal,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>                         $filters
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function buildInvoiceFilters(array $filters): array
    {
        $clauses = ['i.deleted_at IS NULL'];
        $params  = [];

        if (!empty($filters['customer_id'])) {
            $clauses[]              = 'i.customer_id = :customer_id';
            $params[':customer_id'] = (int) $filters['customer_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'i.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $clauses[]           = 'i.invoice_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[]         = 'i.invoice_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $clauses[]        = 'i.invoice_number LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [$clauses, $params];
    }

    /**
     * @param  array<string, mixed>                         $filters
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function buildQuotationFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['customer_id'])) {
            $clauses[]              = 'q.customer_id = :customer_id';
            $params[':customer_id'] = (int) $filters['customer_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'q.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $clauses[]        = 'q.quotation_number LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [$clauses, $params];
    }

    /**
     * @param  array<string, mixed>                         $filters
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function buildOrderFilters(array $filters): array
    {
        $clauses = ['o.deleted_at IS NULL'];
        $params  = [];

        if (!empty($filters['customer_id'])) {
            $clauses[]              = 'o.customer_id = :customer_id';
            $params[':customer_id'] = (int) $filters['customer_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'o.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $clauses[]        = 'o.order_number LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [$clauses, $params];
    }

    /**
     * Generate a unique payment reference number in the format PAY-YYYYNNNNN.
     */
    private function generatePaymentRef(): string
    {
        $year = date('Y');
        $row  = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM payments WHERE payment_number LIKE :pattern",
            [':pattern' => 'PAY-' . $year . '%'],
        );
        $next = ((int) ($row['total'] ?? 0)) + 1;
        return 'PAY-' . $year . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
