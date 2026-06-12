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
 * SalesOrderController
 *
 * Handles the full lifecycle of sales orders:
 * list → create → store → show → edit → update → approve → cancel → createInvoice
 */
final class SalesOrderController extends BaseController
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
        $result = $this->repo->paginateOrders($filters, $page, 20);

        return $this->render('sales/orders/index', [
            'pageTitle'     => 'Sales Orders',
            'breadcrumbs'   => ['Sales' => null, 'Orders' => null],
            'headerActions' => '<a href="/sales/orders/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Order</a>',
            'orders'        => $result['items'],
            'filters'       => $filters,
            'pagination'    => $this->buildPagination($result['total'], $page, 20),
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        return $this->render('sales/orders/create', [
            'pageTitle'   => 'New Sales Order',
            'breadcrumbs' => ['Sales' => null, 'Orders' => '/sales/orders', 'New Order' => null],
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
        $errors = $this->validateOrder($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/sales/orders/create');
        }

        try {
            [$orderData, $items] = $this->prepareOrderPayload($data);
            $orderData['reference_no'] = $this->repo->generateOrderRef();
            $orderData['created_by']   = $this->currentUser()?->id ?? 0;
            $orderData['status']       = 'pending';

            $id = $this->repo->createOrder($orderData, $items);
            $this->success('Sales order created successfully.');
            return $this->redirect('/sales/orders/' . $id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            $this->withInput($request);
            return $this->redirect('/sales/orders/create');
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, int $id): Response
    {
        $order = $this->repo->findOrder($id);

        if ($order === null) {
            $this->error('Sales order not found.');
            return $this->redirect('/sales/orders');
        }

        return $this->render('sales/orders/show', [
            'pageTitle'   => 'Order ' . sanitize($order['reference_no']),
            'breadcrumbs' => ['Sales' => null, 'Orders' => '/sales/orders', sanitize($order['reference_no']) => null],
            'order'       => $order,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit form (only for pending orders)
    // -------------------------------------------------------------------------

    public function edit(Request $request, int $id): Response
    {
        $order = $this->repo->findOrder($id);

        if ($order === null) {
            $this->error('Sales order not found.');
            return $this->redirect('/sales/orders');
        }

        if (!in_array($order['status'], ['pending'], true)) {
            $this->error('Only pending orders can be edited.');
            return $this->redirect('/sales/orders/' . $id);
        }

        return $this->render('sales/orders/create', [
            'pageTitle'   => 'Edit Order ' . sanitize($order['reference_no']),
            'breadcrumbs' => ['Sales' => null, 'Orders' => '/sales/orders', sanitize($order['reference_no']) => '/sales/orders/' . $id, 'Edit' => null],
            'order'       => $order,
            'customers'   => $this->activeCustomers(),
            'products'    => $this->activeProducts(),
            'errors'      => $this->getFlash('errors', []),
            'old'         => $this->getFlash('old', $order),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $order = $this->repo->findOrder($id);

        if ($order === null) {
            $this->error('Sales order not found.');
            return $this->redirect('/sales/orders');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateOrder($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/sales/orders/' . $id . '/edit');
        }

        try {
            [$orderData, $items] = $this->prepareOrderPayload($data);
            $this->repo->updateOrder($id, $orderData, $items);
            $this->success('Sales order updated successfully.');
            return $this->redirect('/sales/orders/' . $id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            $this->withInput($request);
            return $this->redirect('/sales/orders/' . $id . '/edit');
        }
    }

    // -------------------------------------------------------------------------
    // Approve
    // -------------------------------------------------------------------------

    public function approve(Request $request, int $id): Response
    {
        $order = $this->repo->findOrder($id);

        if ($order === null) {
            $this->error('Sales order not found.');
            return $this->redirect('/sales/orders');
        }

        try {
            $this->repo->updateOrderStatus($id, 'confirmed');
            $this->success('Sales order confirmed successfully.');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/sales/orders/' . $id);
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    public function cancel(Request $request, int $id): Response
    {
        $order = $this->repo->findOrder($id);

        if ($order === null) {
            $this->error('Sales order not found.');
            return $this->redirect('/sales/orders');
        }

        try {
            $this->repo->updateOrderStatus($id, 'cancelled');
            $this->success('Sales order cancelled.');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/sales/orders/' . $id);
    }

    // -------------------------------------------------------------------------
    // Create Invoice from Order
    // -------------------------------------------------------------------------

    public function createInvoice(Request $request, int $id): Response
    {
        $order = $this->repo->findOrder($id);

        if ($order === null) {
            $this->error('Sales order not found.');
            return $this->redirect('/sales/orders');
        }

        try {
            $invoiceId = $this->repo->createInvoiceFromOrder($id, $this->currentUser()?->id ?? 0);
            $this->success('Invoice created from sales order successfully.');
            return $this->redirect('/sales/invoices/' . $invoiceId);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return $this->redirect('/sales/orders/' . $id);
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
    private function validateOrder(array $data): array
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
    private function prepareOrderPayload(array $data): array
    {
        $items    = (array) ($data['items'] ?? []);
        $discount = (float) ($data['discount'] ?? 0);
        $taxRate  = (float) ($data['tax_rate'] ?? 0);

        $subtotal = 0.0;
        foreach ($items as &$item) {
            $qty       = (float) ($item['quantity'] ?? 0);
            $price     = (float) ($item['unit_price'] ?? 0);
            $lineTotal = $qty * $price;
            $item['total'] = $lineTotal;
            $subtotal += $lineTotal;
        }
        unset($item);

        $discountAmt = $subtotal * ($discount / 100);
        $taxable     = $subtotal - $discountAmt;
        $taxAmount   = $taxable * ($taxRate / 100);
        $total       = $taxable + $taxAmount;

        $orderData = [
            'customer_id'   => (int) $data['customer_id'],
            'quotation_id'  => isset($data['quotation_id']) && $data['quotation_id'] !== '' ? (int) $data['quotation_id'] : null,
            'issue_date'    => $data['issue_date'],
            'delivery_date' => isset($data['delivery_date']) && $data['delivery_date'] !== '' ? $data['delivery_date'] : null,
            'notes'         => trim((string) ($data['notes'] ?? '')) ?: null,
            'discount'      => $discount,
            'tax_rate'      => $taxRate,
            'subtotal'      => $subtotal,
            'tax_amount'    => $taxAmount,
            'total'         => $total,
            'status'        => $data['status'] ?? 'pending',
        ];

        return [$orderData, $items];
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
