<?php

declare(strict_types=1);

namespace App\Controllers\Purchasing;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PurchasingRepository;

/**
 * PurchaseOrderController
 *
 * Full CRUD for purchase orders plus approve / cancel status transitions.
 * Routes mounted at /purchasing/orders.
 */
final class PurchaseOrderController extends BaseController
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
        $result = $this->purchasing->paginateOrders($filters, $page);

        $pagination = $this->buildPagination($result['total'], $page, 20);
        $suppliers  = $this->purchasing->allActiveSuppliers();

        return $this->render('purchase/orders/index', [
            'pageTitle'     => 'Purchase Orders',
            'breadcrumbs'   => ['Purchasing' => null, 'Purchase Orders' => null],
            'headerActions' => '<a href="/purchasing/orders/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Order</a>',
            'orders'        => $result['items'],
            'filters'       => $filters,
            'pagination'    => $pagination,
            'suppliers'     => $suppliers,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        return $this->render('purchase/orders/create', [
            'pageTitle'   => 'New Purchase Order',
            'breadcrumbs' => ['Purchase Orders' => '/purchasing/orders', 'New Order' => null],
            'suppliers'   => $this->purchasing->allActiveSuppliers(),
            'products'    => $this->purchasing->allActiveProducts(),
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
        $errors = $this->validateOrder($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/purchasing/orders/create');
        }

        $items     = $this->extractItems($data);
        $totals    = $this->calcTotals($items, (float) ($data['discount'] ?? 0), (float) ($data['tax_rate'] ?? 0));
        $user      = $this->currentUser();

        $orderData = [
            'po_number'       => $this->purchasing->generateOrderRef(),
            'supplier_id'     => (int) $data['supplier_id'],
            'order_date'      => $data['order_date'],
            'expected_date'   => $data['expected_date'] ?? '',
            'notes'           => $data['notes'] ?? null,
            'discount_amount' => $totals['discount_amount'],
            'vat_amount'      => $totals['tax_amount'],
            'subtotal'        => $totals['subtotal'],
            'total_amount'    => $totals['total'],
            'status'          => 'draft',
            'created_by'      => $user?->id ?? 1,
            'branch_id'       => session()->get('active_branch_id', 1),
        ];

        $id = $this->purchasing->createOrder($orderData, $items);

        $this->success('Purchase order created successfully.');
        return $this->redirect('/purchasing/orders/' . $id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, int $id): Response
    {
        $order = $this->purchasing->findOrder($id);

        if ($order === null) {
            $this->error('Purchase order not found.');
            return $this->redirect('/purchasing/orders');
        }

        return $this->render('purchase/orders/show', [
            'pageTitle'   => sanitize($order['po_number']),
            'breadcrumbs' => ['Purchase Orders' => '/purchasing/orders', sanitize($order['po_number']) => null],
            'order'       => $order,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(Request $request, int $id): Response
    {
        $order = $this->purchasing->findOrder($id);

        if ($order === null) {
            $this->error('Purchase order not found.');
            return $this->redirect('/purchasing/orders');
        }

        if (!in_array($order['status'], ['draft', 'sent'], true)) {
            $this->error('Only draft or sent orders can be edited.');
            return $this->redirect('/purchasing/orders/' . $id);
        }

        return $this->render('purchase/orders/edit', [
            'pageTitle'   => 'Edit — ' . sanitize($order['po_number']),
            'breadcrumbs' => [
                'Purchase Orders'                 => '/purchasing/orders',
                sanitize($order['po_number'])     => '/purchasing/orders/' . $id,
                'Edit'                            => null,
            ],
            'order'     => $order,
            'suppliers' => $this->purchasing->allActiveSuppliers(),
            'products'  => $this->purchasing->allActiveProducts(),
            'errors'    => session()->getFlash('errors', []),
            'old'       => session()->getFlash('old', $order),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $order = $this->purchasing->findOrder($id);

        if ($order === null) {
            $this->error('Purchase order not found.');
            return $this->redirect('/purchasing/orders');
        }

        if (!in_array($order['status'], ['draft', 'sent'], true)) {
            $this->error('Only draft or sent orders can be edited.');
            return $this->redirect('/purchasing/orders/' . $id);
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateOrder($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/purchasing/orders/' . $id . '/edit');
        }

        $items  = $this->extractItems($data);
        $totals = $this->calcTotals($items, (float) ($data['discount'] ?? 0), (float) ($data['tax_rate'] ?? 0));

        $this->purchasing->updateOrder($id, [
            'supplier_id'     => (int) $data['supplier_id'],
            'order_date'      => $data['order_date'],
            'expected_date'   => $data['expected_date'] ?? '',
            'notes'           => $data['notes'] ?? null,
            'discount_amount' => $totals['discount_amount'],
            'vat_amount'      => $totals['tax_amount'],
            'subtotal'        => $totals['subtotal'],
            'total_amount'    => $totals['total'],
        ], $items);

        $this->success('Purchase order updated successfully.');
        return $this->redirect('/purchasing/orders/' . $id);
    }

    // -------------------------------------------------------------------------
    // Destroy (soft delete)
    // -------------------------------------------------------------------------

    public function destroy(Request $request, int $id): Response
    {
        $order = $this->purchasing->findOrder($id);

        if ($order === null) {
            $this->error('Purchase order not found.');
            return $this->redirect('/purchasing/orders');
        }

        if (!in_array($order['status'], ['draft', 'cancelled'], true)) {
            $this->error('Only draft or cancelled orders can be deleted.');
            return $this->redirect('/purchasing/orders/' . $id);
        }

        $this->purchasing->softDeleteOrder($id);
        $this->success('Purchase order deleted.');
        return $this->redirect('/purchasing/orders');
    }

    // -------------------------------------------------------------------------
    // Approve
    // -------------------------------------------------------------------------

    public function approve(Request $request, int $id): Response
    {
        $order = $this->purchasing->findOrder($id);

        if ($order === null) {
            $this->error('Purchase order not found.');
            return $this->redirect('/purchasing/orders');
        }

        if (!in_array($order['status'], ['draft', 'sent'], true)) {
            $this->error('Only draft or sent orders can be approved.');
            return $this->redirect('/purchasing/orders/' . $id);
        }

        $this->purchasing->updateOrderStatus($id, 'approved');
        $this->success('Purchase order approved.');
        return $this->redirect('/purchasing/orders/' . $id);
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    public function cancel(Request $request, int $id): Response
    {
        $order = $this->purchasing->findOrder($id);

        if ($order === null) {
            $this->error('Purchase order not found.');
            return $this->redirect('/purchasing/orders');
        }

        if (in_array($order['status'], ['received', 'cancelled'], true)) {
            $this->error('This order cannot be cancelled.');
            return $this->redirect('/purchasing/orders/' . $id);
        }

        $this->purchasing->updateOrderStatus($id, 'cancelled');
        $this->success('Purchase order cancelled.');
        return $this->redirect('/purchasing/orders/' . $id);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Validate order header fields.
     *
     * @param  array<string, mixed>    $data
     * @return array<string, string[]>
     */
    private function validateOrder(array $data): array
    {
        $errors = [];

        if (empty($data['supplier_id'])) {
            $errors['supplier_id'][] = 'Supplier is required.';
        }

        if (empty($data['order_date'])) {
            $errors['order_date'][] = 'Order date is required.';
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
     * Extract line items from raw POST data.
     * Expects items[0][product_id], items[0][quantity], etc.
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
            $qty   = (float) ($row['quantity']   ?? 0);
            $price = (float) ($row['unit_price']  ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $items[] = [
                'product_id'  => (int) $row['product_id'],
                'quantity'    => $qty,
                'unit_price'  => $price,
                'vat_rate'    => (float) ($row['vat_rate']   ?? 0),
                'vat_amount'  => round($qty * $price * (float) ($row['vat_rate'] ?? 0) / 100, 2),
                'discount'    => (float) ($row['discount']   ?? 0),
            ];
        }

        return $items;
    }

    /**
     * Compute subtotal, discount amount, tax amount, and total.
     *
     * @param  array<int, array<string, mixed>> $items
     * @return array{subtotal: float, discount_amount: float, tax_amount: float, total: float}
     */
    private function calcTotals(array $items, float $discountPct, float $taxRate): array
    {
        $subtotal = array_sum(array_map(
            static fn (array $i) => (float) $i['quantity'] * (float) $i['unit_price'],
            $items,
        ));

        $discountAmount = round($subtotal * $discountPct / 100, 2);
        $taxableAmount  = $subtotal - $discountAmount;
        $taxAmount      = round($taxableAmount * $taxRate / 100, 2);
        $total          = round($taxableAmount + $taxAmount, 2);

        return [
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => $discountAmount,
            'tax_amount'      => $taxAmount,
            'total'           => $total,
        ];
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
