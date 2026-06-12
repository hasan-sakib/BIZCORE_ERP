<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use App\Core\Database;
use App\Http\Request;
use App\Services\SalesService;

class SalesApiController extends BaseApiController
{
    public function __construct(
        private readonly SalesService $salesService,
        private readonly Database $db
    ) {}

    // ── Orders ─────────────────────────────────────────────────────────────────

    public function orders(Request $request): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));
        $branchId = $this->getBranchId($request);

        $where    = ['so.branch_id = ?', 'so.deleted_at IS NULL'];
        $bindings = [$branchId];

        if ($status = $request->query('status')) {
            $where[]    = 'so.status = ?';
            $bindings[] = $status;
        }
        if ($customerId = $request->query('customer_id')) {
            $where[]    = 'so.customer_id = ?';
            $bindings[] = (int)$customerId;
        }
        if ($from = $request->query('from')) {
            $where[]    = 'so.order_date >= ?';
            $bindings[] = $from;
        }
        if ($to = $request->query('to')) {
            $where[]    = 'so.order_date <= ?';
            $bindings[] = $to;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM sales_orders so {$whereClause}",
            $bindings
        );

        $rows = $this->db->fetchAll(
            "SELECT so.*, c.name AS customer_name, c.customer_code
             FROM sales_orders so
             LEFT JOIN customers c ON c.id = so.customer_id
             {$whereClause}
             ORDER BY so.order_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $this->paginated(['data' => $rows, 'pagination' => paginate($total, $page, $perPage)]);
    }

    public function showOrder(Request $request, int $id): void
    {
        $order = $this->db->fetchOne(
            "SELECT so.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email
             FROM sales_orders so LEFT JOIN customers c ON c.id = so.customer_id
             WHERE so.id = ? AND so.deleted_at IS NULL",
            [$id]
        );

        if (!$order) {
            $this->error('Order not found.', 404);
        }

        $order['items'] = $this->db->fetchAll(
            "SELECT soi.*, p.name AS product_name, p.sku FROM sales_order_items soi
             JOIN products p ON p.id = soi.product_id WHERE soi.sales_order_id = ?",
            [$id]
        );

        $this->success($order);
    }

    public function createOrder(Request $request): void
    {
        $data = $request->all();

        $required = ['customer_id', 'items'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }
        if (!is_array($data['items']) || count($data['items']) === 0) {
            $this->error('Order must have at least one item.', 422);
        }

        try {
            $user     = $this->currentUser($request);
            $order = $this->salesService->createOrder(
                branchId:   $this->getBranchId($request),
                customerId: (int)$data['customer_id'],
                items:      $data['items'],
                notes:      $data['notes'] ?? null,
                discount:   (float)($data['discount_amount'] ?? 0),
                createdBy:  $user?->id ?? 0
            );
            $this->success($order, 'Sales order created.', 201);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    // ── Invoices ────────────────────────────────────────────────────────────────

    public function invoices(Request $request): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));
        $branchId = $this->getBranchId($request);

        $where    = ['i.branch_id = ?', 'i.deleted_at IS NULL'];
        $bindings = [$branchId];

        if ($status = $request->query('status')) {
            $where[]    = 'i.status = ?';
            $bindings[] = $status;
        }
        if ($customerId = $request->query('customer_id')) {
            $where[]    = 'i.customer_id = ?';
            $bindings[] = (int)$customerId;
        }
        if ($overdue = $request->query('overdue')) {
            $where[] = "i.status IN ('sent','partial') AND i.due_date < CURDATE()";
        }
        if ($from = $request->query('from')) {
            $where[]    = 'i.invoice_date >= ?';
            $bindings[] = $from;
        }
        if ($to = $request->query('to')) {
            $where[]    = 'i.invoice_date <= ?';
            $bindings[] = $to;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices i {$whereClause}",
            $bindings
        );

        $rows = $this->db->fetchAll(
            "SELECT i.*, c.name AS customer_name, c.phone AS customer_phone
             FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id
             {$whereClause}
             ORDER BY i.invoice_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $this->paginated(['data' => $rows, 'pagination' => paginate($total, $page, $perPage)]);
    }

    public function showInvoice(Request $request, int $id): void
    {
        $invoice = $this->db->fetchOne(
            "SELECT i.*, c.name AS customer_name, c.phone AS customer_phone,
                    c.email AS customer_email, c.billing_address
             FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id
             WHERE i.id = ? AND i.deleted_at IS NULL",
            [$id]
        );

        if (!$invoice) {
            $this->error('Invoice not found.', 404);
        }

        $invoice['items'] = $this->db->fetchAll(
            "SELECT ii.*, p.name AS product_name, p.sku FROM invoice_items ii
             JOIN products p ON p.id = ii.product_id WHERE ii.invoice_id = ?",
            [$id]
        );

        $invoice['payments'] = $this->db->fetchAll(
            "SELECT pa.*, p.payment_number, p.method, p.amount AS payment_total
             FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id
             WHERE pa.invoice_id = ? ORDER BY p.payment_date DESC",
            [$id]
        );

        $this->success($invoice);
    }

    public function createInvoice(Request $request): void
    {
        $data = $request->all();

        $required = ['customer_id', 'items'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        try {
            $user    = $this->currentUser($request);
            $invoice = $this->salesService->createInvoice(
                branchId:    $this->getBranchId($request),
                customerId:  (int)$data['customer_id'],
                items:       $data['items'],
                warehouseId: (int)($data['warehouse_id'] ?? 0),
                dueDate:     $data['due_date'] ?? null,
                notes:       $data['notes'] ?? null,
                discount:    (float)($data['discount_amount'] ?? 0),
                orderId:     (int)($data['sales_order_id'] ?? 0) ?: null,
                createdBy:   $user?->id ?? 0
            );
            $this->success($invoice, 'Invoice created.', 201);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    // ── Payments ────────────────────────────────────────────────────────────────

    public function payments(Request $request): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));
        $branchId = $this->getBranchId($request);

        $where    = ['p.branch_id = ?', 'p.deleted_at IS NULL'];
        $bindings = [$branchId];

        if ($customerId = $request->query('customer_id')) {
            $where[]    = 'p.customer_id = ?';
            $bindings[] = (int)$customerId;
        }
        if ($method = $request->query('method')) {
            $where[]    = 'p.method = ?';
            $bindings[] = $method;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM payments p {$whereClause}",
            $bindings
        );

        $rows = $this->db->fetchAll(
            "SELECT p.*, c.name AS customer_name FROM payments p
             LEFT JOIN customers c ON c.id = p.customer_id
             {$whereClause}
             ORDER BY p.payment_date DESC LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $this->paginated(['data' => $rows, 'pagination' => paginate($total, $page, $perPage)]);
    }

    public function receivePayment(Request $request): void
    {
        $data = $request->all();

        $required = ['customer_id', 'amount', 'method'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        try {
            $user    = $this->currentUser($request);
            $payment = $this->salesService->receivePayment(
                branchId:   $this->getBranchId($request),
                customerId: (int)$data['customer_id'],
                amount:     (float)$data['amount'],
                method:     $data['method'],
                reference:  $data['reference'] ?? null,
                notes:      $data['notes'] ?? null,
                createdBy:  $user?->id ?? 0
            );
            $this->success($payment, 'Payment recorded.', 201);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }
}
