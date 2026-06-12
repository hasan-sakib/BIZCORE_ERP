<?php

declare(strict_types=1);

namespace App\Controllers\Purchasing;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PurchasingRepository;

/**
 * GoodsReceiptController
 *
 * Handles GRN index, create (optionally pre-filled from a PO), store, and show.
 * Routes mounted at /purchasing/grn.
 */
final class GoodsReceiptController extends BaseController
{
    public function __construct(
        private readonly PurchasingRepository $purchasing,
    ) {}

    // -------------------------------------------------------------------------
    // Index — paginated list
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [
            'supplier_id' => (int) $request->query('supplier_id', 0) ?: null,
            'status'      => trim((string) $request->query('status', '')),
            'date_from'   => trim((string) $request->query('date_from', '')),
            'date_to'     => trim((string) $request->query('date_to', '')),
            'search'      => trim((string) $request->query('search', '')),
        ];
        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->purchasing->paginateGRN($filters, $page);

        $pagination = $this->buildPagination($result['total'], $page, 20);
        $suppliers  = $this->purchasing->allActiveSuppliers();

        return $this->render('purchase/grn/index', [
            'pageTitle'     => 'Goods Receipts',
            'breadcrumbs'   => ['Purchasing' => null, 'Goods Receipts' => null],
            'headerActions' => '<a href="/purchasing/grn/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New GRN</a>',
            'receipts'      => $result['items'],
            'filters'       => $filters,
            'pagination'    => $pagination,
            'suppliers'     => $suppliers,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form  (optional ?po_id= pre-fill)
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        $poId     = (int) $request->query('po_id', 0);
        $prefill  = null;

        if ($poId > 0) {
            $po = $this->purchasing->findOrder($poId);
            if ($po !== null && in_array($po['status'], ['approved', 'partial'], true)) {
                $prefill = $po;
            }
        }

        return $this->render('purchase/grn/create', [
            'pageTitle'   => 'New Goods Receipt',
            'breadcrumbs' => ['Goods Receipts' => '/purchasing/grn', 'New GRN' => null],
            'suppliers'   => $this->purchasing->allActiveSuppliers(),
            'warehouses'  => $this->purchasing->allWarehouses(),
            'products'    => $this->purchasing->allActiveProducts(),
            'approvedPOs' => $this->purchasing->approvedOrders(),
            'prefill'     => $prefill,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token']);
        $errors = $this->validateGRN($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/purchasing/grn/create');
        }

        $items = $this->extractItems($data);
        $user  = $this->currentUser();

        $subtotal   = array_sum(array_map(
            static fn (array $i) => (float) $i['quantity'] * (float) $i['unit_cost'],
            $items,
        ));
        $vatAmount  = array_sum(array_map(static fn (array $i) => (float) ($i['vat_amount'] ?? 0), $items));
        $totalAmount = round($subtotal + $vatAmount, 2);

        $grnData = [
            'grn_number'   => $this->purchasing->generateGRNRef(),
            'po_id'        => !empty($data['po_id']) ? (int) $data['po_id'] : null,
            'supplier_id'  => (int) $data['supplier_id'],
            'warehouse_id' => (int) $data['warehouse_id'],
            'receipt_date' => $data['receipt_date'],
            'notes'        => $data['notes'] ?? null,
            'subtotal'     => round($subtotal, 2),
            'vat_amount'   => round($vatAmount, 2),
            'total_amount' => $totalAmount,
            'status'       => 'draft',
            'created_by'   => $user?->id ?? 1,
            'branch_id'    => session()->get('active_branch_id', 1),
        ];

        $id = $this->purchasing->createGRN($grnData, $items);

        $this->success('Goods receipt created successfully.');
        return $this->redirect('/purchasing/grn/' . $id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, int $id): Response
    {
        $grn = $this->purchasing->findGRN($id);

        if ($grn === null) {
            $this->error('Goods receipt not found.');
            return $this->redirect('/purchasing/grn');
        }

        return $this->render('purchase/grn/show', [
            'pageTitle'   => sanitize($grn['grn_number']),
            'breadcrumbs' => ['Goods Receipts' => '/purchasing/grn', sanitize($grn['grn_number']) => null],
            'grn'         => $grn,
        ]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Validate GRN header fields.
     *
     * @param  array<string, mixed>    $data
     * @return array<string, string[]>
     */
    private function validateGRN(array $data): array
    {
        $errors = [];

        if (empty($data['supplier_id'])) {
            $errors['supplier_id'][] = 'Supplier is required.';
        }

        if (empty($data['warehouse_id'])) {
            $errors['warehouse_id'][] = 'Warehouse is required.';
        }

        if (empty($data['receipt_date'])) {
            $errors['receipt_date'][] = 'Receipt date is required.';
        }

        $items = $this->extractItems($data);
        if ($items === []) {
            $errors['items'][] = 'At least one line item is required.';
        } else {
            foreach ($items as $i => $item) {
                if (empty($item['product_id'])) {
                    $errors["items.{$i}.product_id"][] = "Line " . ($i + 1) . ": product is required.";
                }
                if ((float) ($item['quantity'] ?? 0) <= 0) {
                    $errors["items.{$i}.quantity"][] = "Line " . ($i + 1) . ": quantity must be greater than 0.";
                }
            }
        }

        return $errors;
    }

    /**
     * Extract validated line items from raw POST data.
     *
     * @param  array<string, mixed>              $data
     * @return array<int, array<string, mixed>>
     */
    private function extractItems(array $data): array
    {
        $raw = $data['items'] ?? [];

        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $row) {
            if (empty($row['product_id'])) {
                continue;
            }
            $qty      = (float) ($row['quantity']  ?? 0);
            $unitCost = (float) ($row['unit_cost']  ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $items[] = [
                'product_id'  => (int) $row['product_id'],
                'po_item_id'  => !empty($row['po_item_id']) ? (int) $row['po_item_id'] : null,
                'quantity'    => $qty,
                'unit_cost'   => $unitCost,
                'vat_amount'  => (float) ($row['vat_amount'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * Build a standard pagination array.
     *
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
}
