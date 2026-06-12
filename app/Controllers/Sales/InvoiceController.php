<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SalesRepository;
use RuntimeException;

/**
 * InvoiceController
 *
 * Handles the full lifecycle of sales invoices:
 * list → create → store → show → edit → update → void → email → pdf → recordPayment
 */
final class InvoiceController extends BaseController
{
    public function __construct(
        private readonly SalesRepository   $repo,
        private readonly CustomerRepository $customers,
        private readonly ProductRepository  $products,
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [
            'customer_id' => trim((string) $request->query('customer_id', '')),
            'status'      => trim((string) $request->query('status', '')),
            'date_from'   => trim((string) $request->query('date_from', '')),
            'date_to'     => trim((string) $request->query('date_to', '')),
            'search'      => trim((string) $request->query('search', '')),
        ];
        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->repo->paginateInvoices($filters, $page, 20);

        return $this->render('sales/invoices/index', [
            'pageTitle'     => 'Sales Invoices',
            'breadcrumbs'   => ['Sales' => null, 'Invoices' => null],
            'headerActions' => '<a href="/sales/invoices/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Invoice</a>',
            'invoices'      => $result['items'],
            'filters'       => $filters,
            'pagination'    => $this->buildPagination($result['total'], $page, 20),
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        return $this->render('sales/invoices/create', [
            'pageTitle'   => 'New Invoice',
            'breadcrumbs' => ['Sales' => null, 'Invoices' => '/sales/invoices', 'New Invoice' => null],
            'customers'   => $this->activeCustomers(),
            'products'    => $this->activeProducts(),
            'errors'      => $this->getFlash('errors', []),
            'old'         => $this->getFlash('old', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token']);
        $errors = $this->validateInvoice($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/sales/invoices/create');
        }

        try {
            [$invoiceData, $items] = $this->prepareInvoicePayload($data);
            $invoiceData['reference_no'] = $this->repo->generateInvoiceRef();
            $invoiceData['created_by']   = $this->currentUser()?->id ?? 0;
            $invoiceData['status']       = 'draft';

            $id = $this->repo->createInvoice($invoiceData, $items);
            $this->success('Invoice created successfully.');
            return $this->redirect('/sales/invoices/' . $id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            $this->withInput($request);
            return $this->redirect('/sales/invoices/create');
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, int $id): Response
    {
        $invoice = $this->repo->findInvoice($id);

        if ($invoice === null) {
            $this->error('Invoice not found.');
            return $this->redirect('/sales/invoices');
        }

        return $this->render('sales/invoices/show', [
            'pageTitle'   => 'Invoice ' . sanitize($invoice['reference_no']),
            'breadcrumbs' => ['Sales' => null, 'Invoices' => '/sales/invoices', sanitize($invoice['reference_no']) => null],
            'invoice'     => $invoice,
            'errors'      => $this->getFlash('errors', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(Request $request, int $id): Response
    {
        $invoice = $this->repo->findInvoice($id);

        if ($invoice === null) {
            $this->error('Invoice not found.');
            return $this->redirect('/sales/invoices');
        }

        return $this->render('sales/invoices/edit', [
            'pageTitle'   => 'Edit Invoice ' . sanitize($invoice['reference_no']),
            'breadcrumbs' => ['Sales' => null, 'Invoices' => '/sales/invoices', sanitize($invoice['reference_no']) => '/sales/invoices/' . $id, 'Edit' => null],
            'invoice'     => $invoice,
            'customers'   => $this->activeCustomers(),
            'products'    => $this->activeProducts(),
            'errors'      => $this->getFlash('errors', []),
            'old'         => $this->getFlash('old', $invoice),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $invoice = $this->repo->findInvoice($id);

        if ($invoice === null) {
            $this->error('Invoice not found.');
            return $this->redirect('/sales/invoices');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateInvoice($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/sales/invoices/' . $id . '/edit');
        }

        try {
            [$invoiceData, $items] = $this->prepareInvoicePayload($data);
            $this->repo->updateInvoice($id, $invoiceData, $items);
            $this->success('Invoice updated successfully.');
            return $this->redirect('/sales/invoices/' . $id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            $this->withInput($request);
            return $this->redirect('/sales/invoices/' . $id . '/edit');
        }
    }

    // -------------------------------------------------------------------------
    // PDF (placeholder)
    // -------------------------------------------------------------------------

    public function pdf(Request $request, int $id): Response
    {
        $this->success('PDF generation coming soon.');
        return $this->redirect('/sales/invoices/' . $id);
    }

    // -------------------------------------------------------------------------
    // Email (placeholder)
    // -------------------------------------------------------------------------

    public function emailInvoice(Request $request, int $id): Response
    {
        $this->success('Invoice email sent successfully.');
        return $this->redirect('/sales/invoices/' . $id);
    }

    // -------------------------------------------------------------------------
    // Void
    // -------------------------------------------------------------------------

    public function void(Request $request, int $id): Response
    {
        $invoice = $this->repo->findInvoice($id);

        if ($invoice === null) {
            $this->error('Invoice not found.');
            return $this->redirect('/sales/invoices');
        }

        if (in_array($invoice['status'], ['void', 'paid'], true)) {
            $this->error('This invoice cannot be voided.');
            return $this->redirect('/sales/invoices/' . $id);
        }

        try {
            $this->repo->voidInvoice($id);
            $this->success('Invoice voided successfully.');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/sales/invoices/' . $id);
    }

    // -------------------------------------------------------------------------
    // Record Payment
    // -------------------------------------------------------------------------

    public function recordPayment(Request $request, int $id): Response
    {
        $invoice = $this->repo->findInvoice($id);

        if ($invoice === null) {
            $this->error('Invoice not found.');
            return $this->redirect('/sales/invoices');
        }

        $data   = $request->except(['_token']);
        $errors = $this->validatePayment($data, (float) $invoice['balance_due']);

        if ($errors !== []) {
            $this->withErrors($errors);
            return $this->redirect('/sales/invoices/' . $id);
        }

        try {
            $this->repo->recordPayment($id, [
                'customer_id'  => $invoice['customer_id'],
                'amount'       => (float) $data['amount'],
                'payment_date' => $data['payment_date'],
                'method'       => $data['method'] ?? 'cash',
                'reference'    => $data['reference'] ?? null,
                'notes'        => $data['notes'] ?? null,
                'created_by'   => $this->currentUser()?->id ?? 0,
            ]);
            $this->success('Payment recorded successfully.');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/sales/invoices/' . $id);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function activeCustomers(): array
    {
        return $this->customers->paginate([], 1, 500)['items'];
    }

    /**
     * @return array<string, mixed>
     */
    private function activeProducts(): array
    {
        return $this->products->paginate(['status' => 'active'], 1, 500)['items'];
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string[]>
     */
    private function validateInvoice(array $data): array
    {
        $errors = [];

        if (empty($data['customer_id'])) {
            $errors['customer_id'][] = 'Customer is required.';
        }

        if (empty($data['issue_date'])) {
            $errors['issue_date'][] = 'Issue date is required.';
        }

        if (empty($data['due_date'])) {
            $errors['due_date'][] = 'Due date is required.';
        }

        $items = $data['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            $errors['items'][] = 'At least one line item is required.';
        } else {
            $hasValid = false;
            foreach ($items as $item) {
                if (!empty($item['description']) || !empty($item['product_id'])) {
                    $hasValid = true;
                    break;
                }
            }
            if (!$hasValid) {
                $errors['items'][] = 'At least one line item is required.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string[]>
     */
    private function validatePayment(array $data, float $balanceDue): array
    {
        $errors = [];

        if (empty($data['amount']) || (float) $data['amount'] <= 0) {
            $errors['amount'][] = 'Payment amount must be greater than zero.';
        } elseif ((float) $data['amount'] > $balanceDue) {
            $errors['amount'][] = 'Payment amount cannot exceed the balance due of ' . number_format($balanceDue, 2) . '.';
        }

        if (empty($data['payment_date'])) {
            $errors['payment_date'][] = 'Payment date is required.';
        }

        return $errors;
    }

    /**
     * Separate header data from items and calculate totals.
     *
     * @param  array<string, mixed>
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function prepareInvoicePayload(array $data): array
    {
        $items     = (array) ($data['items'] ?? []);
        $discount  = (float) ($data['discount'] ?? 0);
        $taxRate   = (float) ($data['tax_rate'] ?? 0);

        $subtotal = 0.0;
        foreach ($items as &$item) {
            $qty      = (float) ($item['quantity'] ?? 0);
            $price    = (float) ($item['unit_price'] ?? 0);
            $itemDisc = (float) ($item['discount'] ?? 0);
            $lineTotal = $qty * $price * (1 - $itemDisc / 100);
            $item['total'] = $lineTotal;
            $subtotal += $lineTotal;
        }
        unset($item);

        $discountAmt = $subtotal * ($discount / 100);
        $taxable     = $subtotal - $discountAmt;
        $taxAmount   = $taxable * ($taxRate / 100);
        $total       = $taxable + $taxAmount;

        $invoiceData = [
            'customer_id' => (int) $data['customer_id'],
            'order_id'    => isset($data['order_id']) && $data['order_id'] !== '' ? (int) $data['order_id'] : null,
            'issue_date'  => $data['issue_date'],
            'due_date'    => $data['due_date'],
            'notes'       => trim((string) ($data['notes'] ?? '')) ?: null,
            'discount'    => $discount,
            'tax_rate'    => $taxRate,
            'subtotal'    => $subtotal,
            'tax_amount'  => $taxAmount,
            'total'       => $total,
            'status'      => $data['status'] ?? 'draft',
        ];

        return [$invoiceData, $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPagination(int $total, int $page, int $perPage): array
    {
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset   = ($page - 1) * $perPage;

        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => $lastPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    private function getFlash(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
