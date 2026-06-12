<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use App\Core\Database;
use App\Http\Request;

class CustomerApiController extends BaseApiController
{
    public function __construct(
        private readonly Database $db
    ) {}

    public function index(Request $request): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));
        $branchId = $this->getBranchId($request);

        $where    = ['c.branch_id = ?', 'c.deleted_at IS NULL'];
        $bindings = [$branchId];

        if ($search = $request->query('search')) {
            $where[]    = "(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.customer_code LIKE ?)";
            $term       = '%' . $search . '%';
            $bindings   = array_merge($bindings, [$term, $term, $term, $term]);
        }
        if ($type = $request->query('type')) {
            $where[]    = 'c.type = ?';
            $bindings[] = $type;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM customers c {$whereClause}",
            $bindings
        );

        $rows = $this->db->fetchAll(
            "SELECT c.*,
                    COUNT(DISTINCT i.id) AS invoice_count,
                    COALESCE(SUM(i.total_amount), 0) AS total_revenue
             FROM customers c
             LEFT JOIN invoices i ON i.customer_id = c.id AND i.status != 'cancelled'
             {$whereClause}
             GROUP BY c.id
             ORDER BY c.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $this->paginated(['data' => $rows, 'pagination' => paginate($total, $page, $perPage)]);
    }

    public function show(Request $request, int $id): void
    {
        $customer = $this->db->fetchOne(
            "SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        if (!$customer) {
            $this->error('Customer not found.', 404);
        }

        $customer['recent_invoices'] = $this->db->fetchAll(
            "SELECT id, invoice_number, invoice_date, total_amount, status, balance
             FROM invoices WHERE customer_id = ? ORDER BY invoice_date DESC LIMIT 10",
            [$id]
        );

        $customer['stats'] = $this->db->fetchOne(
            "SELECT COUNT(*) AS total_invoices,
                    COALESCE(SUM(total_amount), 0) AS total_revenue,
                    COALESCE(SUM(balance), 0) AS outstanding_balance
             FROM invoices WHERE customer_id = ? AND status != 'cancelled'",
            [$id]
        );

        $this->success($customer);
    }

    public function store(Request $request): void
    {
        $data = $request->all();

        if (empty($data['name'])) {
            $this->error('Customer name is required.', 422);
        }

        $code  = 'CUST-' . strtoupper(substr(md5($data['name'] . time()), 0, 6));
        $userId = $this->currentUser($request)?->id ?? 0;

        $id = $this->db->table('customers')->insert([
            'branch_id'       => $this->getBranchId($request),
            'customer_code'   => $code,
            'name'            => $data['name'],
            'email'           => $data['email'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'type'            => $data['type'] ?? 'individual',
            'billing_address' => $data['billing_address'] ?? null,
            'shipping_address'=> $data['shipping_address'] ?? null,
            'credit_limit'    => (float)($data['credit_limit'] ?? 0),
            'credit_period'   => (int)($data['credit_period'] ?? 30),
            'vat_number'      => $data['vat_number'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'is_active'       => 1,
            'created_by'      => $userId,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $row = $this->db->table('customers')->where('id', $id)->first();
        $this->success($row, 'Customer created.', 201);
    }

    public function update(Request $request, int $id): void
    {
        $row = $this->db->table('customers')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$row) {
            $this->error('Customer not found.', 404);
        }

        $data = $request->all();
        unset($data['id'], $data['customer_code'], $data['branch_id'], $data['created_at'], $data['created_by']);
        $data['updated_at'] = now();

        $this->db->table('customers')->where('id', $id)->update($data);
        $this->success($this->db->table('customers')->where('id', $id)->first(), 'Customer updated.');
    }

    public function destroy(Request $request, int $id): void
    {
        $row = $this->db->table('customers')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$row) {
            $this->error('Customer not found.', 404);
        }

        $outstanding = (float)$this->db->fetchColumn(
            "SELECT COALESCE(SUM(balance), 0) FROM invoices WHERE customer_id = ? AND status NOT IN ('paid','cancelled')",
            [$id]
        );

        if ($outstanding > 0) {
            $this->error("Cannot delete customer with outstanding balance of " . number_format($outstanding, 2), 422);
        }

        $this->db->table('customers')->where('id', $id)->update([
            'deleted_at' => now(),
            'is_active'  => 0,
            'updated_at' => now(),
        ]);

        $this->success(null, 'Customer deleted.');
    }

    public function ledger(Request $request, int $id): void
    {
        $customer = $this->db->table('customers')->where('id', $id)->first();
        if (!$customer) {
            $this->error('Customer not found.', 404);
        }

        $from = $request->query('from', date('Y-m-01'));
        $to   = $request->query('to', date('Y-m-d'));

        $invoices = $this->db->fetchAll(
            "SELECT 'invoice' AS type, invoice_number AS reference,
                    invoice_date AS date, total_amount AS debit, 0 AS credit, balance
             FROM invoices
             WHERE customer_id = ? AND invoice_date BETWEEN ? AND ? AND status != 'cancelled'",
            [$id, $from, $to]
        );

        $payments = $this->db->fetchAll(
            "SELECT 'payment' AS type, p.payment_number AS reference,
                    p.payment_date AS date, 0 AS debit, pa.amount AS credit, NULL AS balance
             FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id
             WHERE p.customer_id = ? AND p.payment_date BETWEEN ? AND ?",
            [$id, $from, $to]
        );

        $ledger = array_merge($invoices, $payments);
        usort($ledger, fn($a, $b) => strcmp($a['date'], $b['date']));

        $this->success([
            'customer' => $customer,
            'ledger'   => $ledger,
            'period'   => compact('from', 'to'),
        ]);
    }
}
