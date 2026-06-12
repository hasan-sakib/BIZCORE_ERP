<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Http\Request;
use App\Services\SalesService;

class InvoiceController extends BaseController
{
    public function __construct(
        private readonly SalesService $salesService,
        private readonly Database $db
    ) {}

    public function index(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;
        $page     = max(1, (int)$request->query('page', 1));
        $perPage  = 20;

        $status     = $request->query('status');
        $customerId = $request->query('customer_id');
        $from       = $request->query('from', date('Y-m-01'));
        $to         = $request->query('to', date('Y-m-d'));

        $where    = ['i.branch_id = ?', 'i.deleted_at IS NULL'];
        $bindings = [$branchId];

        if ($status) {
            $where[]    = 'i.status = ?';
            $bindings[] = $status;
        }
        if ($customerId) {
            $where[]    = 'i.customer_id = ?';
            $bindings[] = (int)$customerId;
        }
        if ($from) {
            $where[]    = 'i.invoice_date >= ?';
            $bindings[] = $from;
        }
        if ($to) {
            $where[]    = 'i.invoice_date <= ?';
            $bindings[] = $to;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices i {$whereClause}",
            $bindings
        );

        $invoices = $this->db->fetchAll(
            "SELECT i.*, c.name AS customer_name FROM invoices i
             LEFT JOIN customers c ON c.id = i.customer_id
             {$whereClause}
             ORDER BY i.invoice_date DESC LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS total_count, SUM(total_amount) AS total_amount,
                    SUM(paid_amount) AS total_paid, SUM(balance) AS outstanding
             FROM invoices i {$whereClause}",
            $bindings
        );

        $this->view('sales/invoices/index', [
            'pageTitle'   => 'Invoices',
            'breadcrumbs' => ['Invoices' => null],
            'invoices'    => $invoices,
            'pagination'  => paginate($total, $page, $perPage),
            'summary'     => $summary,
            'currentUser' => $user,
            'filters'     => compact('status', 'customerId', 'from', 'to'),
            'headerActions' => '<a href="/invoices/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Invoice</a>',
        ]);
    }

    public function create(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;

        $customers  = $this->db->fetchAll(
            "SELECT id, name, customer_code, credit_period, credit_limit, balance
             FROM customers WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );
        $warehouses = $this->db->fetchAll(
            "SELECT id, name FROM warehouses WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );

        $this->view('sales/invoices/create', [
            'pageTitle'   => 'Create Invoice',
            'breadcrumbs' => ['Invoices' => '/invoices', 'Create' => null],
            'customers'   => $customers,
            'warehouses'  => $warehouses,
            'vatRate'     => config('vat.standard_rate', 15),
            'currentUser' => $user,
        ]);
    }

    public function store(Request $request): void
    {
        $data = $request->all();
        $user = $this->currentUser();

        $required = ['customer_id', 'items'];
        $errors   = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucwords(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!empty($errors)) {
            $this->withErrors($errors)->withInput($data)->back();
            return;
        }

        if (is_string($data['items'])) {
            $data['items'] = json_decode($data['items'], true) ?? [];
        }

        try {
            $invoice = $this->salesService->createInvoice(
                branchId:    $user?->branchId ?? 0,
                customerId:  (int)$data['customer_id'],
                items:       $data['items'],
                warehouseId: (int)($data['warehouse_id'] ?? 0),
                dueDate:     $data['due_date'] ?? null,
                notes:       $data['notes'] ?? null,
                discount:    (float)($data['discount_amount'] ?? 0),
                orderId:     (int)($data['sales_order_id'] ?? 0) ?: null,
                createdBy:   $user?->id ?? 0
            );
            $this->success('Invoice created successfully.')->redirect('/invoices/' . $invoice['id']);
        } catch (\Throwable $e) {
            $this->error($e->getMessage())->withInput($data)->back();
        }
    }

    public function show(Request $request, int $id): void
    {
        $invoice = $this->db->fetchOne(
            "SELECT i.*, c.name AS customer_name, c.phone AS customer_phone,
                    c.email AS customer_email, c.billing_address, c.vat_number AS customer_vat
             FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id
             WHERE i.id = ? AND i.deleted_at IS NULL",
            [$id]
        );

        if (!$invoice) {
            $this->error('Invoice not found.')->redirect('/invoices');
            return;
        }

        $invoice['items'] = $this->db->fetchAll(
            "SELECT ii.*, p.name AS product_name, p.sku FROM invoice_items ii
             JOIN products p ON p.id = ii.product_id WHERE ii.invoice_id = ?",
            [$id]
        );
        $invoice['payments'] = $this->db->fetchAll(
            "SELECT pa.*, p.payment_number, p.method, p.payment_date
             FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id
             WHERE pa.invoice_id = ? ORDER BY p.payment_date DESC",
            [$id]
        );

        $settings = $this->db->fetchAll("SELECT `key`, value FROM settings WHERE `group` = 'company'");
        $company  = array_column($settings, 'value', 'key');

        $this->view('sales/invoices/show', [
            'pageTitle'   => 'Invoice #' . $invoice['invoice_number'],
            'breadcrumbs' => ['Invoices' => '/invoices', $invoice['invoice_number'] => null],
            'invoice'     => $invoice,
            'company'     => $company,
            'currentUser' => $this->currentUser(),
        ]);
    }

    public function recordPayment(Request $request, int $id): void
    {
        $data = $request->all();
        $user = $this->currentUser();

        if (empty($data['amount']) || empty($data['method'])) {
            $this->error('Amount and payment method are required.')->back();
            return;
        }

        try {
            $invoice = $this->db->table('invoices')->where('id', $id)->first();
            $this->salesService->receivePayment(
                branchId:   $invoice['branch_id'],
                customerId: $invoice['customer_id'],
                amount:     (float)$data['amount'],
                method:     $data['method'],
                reference:  $data['reference'] ?? null,
                notes:      $data['notes'] ?? null,
                createdBy:  $user?->id ?? 0
            );
            $this->success('Payment recorded successfully.')->redirect("/invoices/{$id}");
        } catch (\Throwable $e) {
            $this->error($e->getMessage())->back();
        }
    }
}
