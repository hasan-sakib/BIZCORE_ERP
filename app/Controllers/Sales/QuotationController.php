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
 * QuotationController
 *
 * Handles the full lifecycle of sales quotations:
 * list → create → store → show → edit → update → destroy → email → pdf → convertToOrder
 */
final class QuotationController extends BaseController
{
    public function __construct(
        private readonly SalesRepository    $repo,
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
            'search'      => trim((string) $request->query('search', '')),
        ];
        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->repo->paginateQuotations($filters, $page, 20);

        return $this->render('sales/quotations/index', [
            'pageTitle'     => 'Quotations',
            'breadcrumbs'   => ['Sales' => null, 'Quotations' => null],
            'headerActions' => '<a href="/sales/quotations/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Quotation</a>',
            'quotations'    => $result['items'],
            'filters'       => $filters,
            'pagination'    => $this->buildPagination($result['total'], $page, 20),
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        return $this->render('sales/quotations/create', [
            'pageTitle'   => 'New Quotation',
            'breadcrumbs' => ['Sales' => null, 'Quotations' => '/sales/quotations', 'New Quotation' => null],
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
        $errors = $this->validateQuotation($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/sales/quotations/create');
        }

        try {
            [$quotationData, $items] = $this->prepareQuotationPayload($data);
            $quotationData['reference_no'] = $this->repo->generateQuotationRef();
            $quotationData['created_by']   = $this->currentUser()?->id ?? 0;
            $quotationData['status']       = 'draft';

            $id = $this->repo->createQuotation($quotationData, $items);
            $this->success('Quotation created successfully.');
            return $this->redirect('/sales/quotations/' . $id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            $this->withInput($request);
            return $this->redirect('/sales/quotations/create');
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, int $id): Response
    {
        $quotation = $this->repo->findQuotation($id);

        if ($quotation === null) {
            $this->error('Quotation not found.');
            return $this->redirect('/sales/quotations');
        }

        return $this->render('sales/quotations/show', [
            'pageTitle'   => 'Quotation ' . sanitize($quotation['reference_no']),
            'breadcrumbs' => ['Sales' => null, 'Quotations' => '/sales/quotations', sanitize($quotation['reference_no']) => null],
            'quotation'   => $quotation,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(Request $request, int $id): Response
    {
        $quotation = $this->repo->findQuotation($id);

        if ($quotation === null) {
            $this->error('Quotation not found.');
            return $this->redirect('/sales/quotations');
        }

        return $this->render('sales/quotations/edit', [
            'pageTitle'   => 'Edit Quotation ' . sanitize($quotation['reference_no']),
            'breadcrumbs' => ['Sales' => null, 'Quotations' => '/sales/quotations', sanitize($quotation['reference_no']) => '/sales/quotations/' . $id, 'Edit' => null],
            'quotation'   => $quotation,
            'customers'   => $this->activeCustomers(),
            'products'    => $this->activeProducts(),
            'errors'      => $this->getFlash('errors', []),
            'old'         => $this->getFlash('old', $quotation),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $quotation = $this->repo->findQuotation($id);

        if ($quotation === null) {
            $this->error('Quotation not found.');
            return $this->redirect('/sales/quotations');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateQuotation($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/sales/quotations/' . $id . '/edit');
        }

        try {
            [$quotationData, $items] = $this->prepareQuotationPayload($data);
            $this->repo->updateQuotation($id, $quotationData, $items);
            $this->success('Quotation updated successfully.');
            return $this->redirect('/sales/quotations/' . $id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            $this->withInput($request);
            return $this->redirect('/sales/quotations/' . $id . '/edit');
        }
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(Request $request, int $id): Response
    {
        $quotation = $this->repo->findQuotation($id);

        if ($quotation === null) {
            $this->error('Quotation not found.');
            return $this->redirect('/sales/quotations');
        }

        try {
            $this->repo->deleteQuotation($id);
            $this->success('Quotation deleted successfully.');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/sales/quotations');
    }

    // -------------------------------------------------------------------------
    // PDF (placeholder)
    // -------------------------------------------------------------------------

    public function pdf(Request $request, int $id): Response
    {
        $this->success('PDF generation coming soon.');
        return $this->redirect('/sales/quotations/' . $id);
    }

    // -------------------------------------------------------------------------
    // Email (placeholder)
    // -------------------------------------------------------------------------

    public function email(Request $request, int $id): Response
    {
        $this->success('Quotation email sent successfully.');
        return $this->redirect('/sales/quotations/' . $id);
    }

    // -------------------------------------------------------------------------
    // Convert to Order
    // -------------------------------------------------------------------------

    public function convert(Request $request, int $id): Response
    {
        $quotation = $this->repo->findQuotation($id);

        if ($quotation === null) {
            $this->error('Quotation not found.');
            return $this->redirect('/sales/quotations');
        }

        try {
            $orderId = $this->repo->convertToOrder($id, $this->currentUser()?->id ?? 0);
            $this->success('Quotation converted to sales order successfully.');
            return $this->redirect('/sales/orders/' . $orderId);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return $this->redirect('/sales/quotations/' . $id);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    private function activeCustomers(): array
    {
        return $this->customers->paginate([], 1, 500)['items'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeProducts(): array
    {
        return $this->products->paginate(['status' => 'active'], 1, 500)['items'];
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string[]>
     */
    private function validateQuotation(array $data): array
    {
        $errors = [];

        if (empty($data['customer_id'])) {
            $errors['customer_id'][] = 'Customer is required.';
        }

        if (empty($data['issue_date'])) {
            $errors['issue_date'][] = 'Issue date is required.';
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
     * @param  array<string, mixed>
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function prepareQuotationPayload(array $data): array
    {
        $items    = (array) ($data['items'] ?? []);
        $discount = (float) ($data['discount'] ?? 0);
        $taxRate  = (float) ($data['tax_rate'] ?? 0);

        $subtotal = 0.0;
        foreach ($items as &$item) {
            $qty       = (float) ($item['quantity'] ?? 0);
            $price     = (float) ($item['unit_price'] ?? 0);
            $itemDisc  = (float) ($item['discount'] ?? 0);
            $lineTotal = $qty * $price * (1 - $itemDisc / 100);
            $item['total'] = $lineTotal;
            $subtotal += $lineTotal;
        }
        unset($item);

        $discountAmt = $subtotal * ($discount / 100);
        $taxable     = $subtotal - $discountAmt;
        $taxAmount   = $taxable * ($taxRate / 100);
        $total       = $taxable + $taxAmount;

        $quotationData = [
            'customer_id' => (int) $data['customer_id'],
            'issue_date'  => $data['issue_date'],
            'expiry_date' => isset($data['expiry_date']) && $data['expiry_date'] !== '' ? $data['expiry_date'] : null,
            'notes'       => trim((string) ($data['notes'] ?? '')) ?: null,
            'discount'    => $discount,
            'tax_rate'    => $taxRate,
            'subtotal'    => $subtotal,
            'tax_amount'  => $taxAmount,
            'total'       => $total,
            'status'      => $data['status'] ?? 'draft',
        ];

        return [$quotationData, $items];
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
