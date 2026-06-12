<?php

declare(strict_types=1);

namespace App\Controllers\CRM;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\CustomerRepository;

/**
 * CustomerController
 *
 * Handles CRUD for CRM customers plus placeholder ledger and orders views.
 * Thin controller: validate → repository → render / redirect.
 */
final class CustomerController extends BaseController
{
    public function __construct(
        private readonly CustomerRepository $customers,
    ) {}

    // -------------------------------------------------------------------------
    // Index — paginated list
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => trim((string) $request->query('status', '')),
        ];
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->customers->paginate($filters, $page, 20);

        $pagination = $this->buildPagination($result['total'], $page, 20);

        return $this->render('customers/index', [
            'pageTitle'   => 'Customers',
            'breadcrumbs' => ['Customers' => null],
            'headerActions' => '<a href="/customers/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Customer</a>',
            'customers'   => $result['items'],
            'filters'     => $filters,
            'pagination'  => $pagination,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        return $this->render('customers/create', [
            'pageTitle'   => 'Add Customer',
            'breadcrumbs' => ['Customers' => '/customers', 'Add Customer' => null],
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
        $errors = $this->validateCustomer($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/customers/create');
        }

        $id = $this->customers->create($this->sanitizeData($data));
        $this->success('Customer created successfully.');
        return $this->redirect('/customers/' . $id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, int $id): Response
    {
        $customer = $this->customers->findById($id);

        if ($customer === null) {
            return $this->notFound('Customer not found.');
        }

        return $this->render('customers/show', [
            'pageTitle'   => sanitize($customer['name']),
            'breadcrumbs' => ['Customers' => '/customers', sanitize($customer['name']) => null],
            'customer'    => $customer,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(Request $request, int $id): Response
    {
        $customer = $this->customers->findById($id);

        if ($customer === null) {
            return $this->notFound('Customer not found.');
        }

        $errors = $this->getFlash('errors', []);
        $old    = $this->getFlash('old', $customer);

        return $this->render('customers/edit', [
            'pageTitle'   => 'Edit — ' . sanitize($customer['name']),
            'breadcrumbs' => ['Customers' => '/customers', sanitize($customer['name']) => '/customers/' . $id, 'Edit' => null],
            'customer'    => $customer,
            'errors'      => $errors,
            'old'         => $old,
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $customer = $this->customers->findById($id);

        if ($customer === null) {
            return $this->notFound('Customer not found.');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateCustomer($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/customers/' . $id . '/edit');
        }

        $this->customers->update($id, $this->sanitizeData($data));
        $this->success('Customer updated successfully.');
        return $this->redirect('/customers/' . $id);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(Request $request, int $id): Response
    {
        $customer = $this->customers->findById($id);

        if ($customer === null) {
            if ($request->wantsJson()) {
                return $this->json(['message' => 'Customer not found.'], 404);
            }
            $this->error('Customer not found.');
            return $this->redirect('/customers');
        }

        $this->customers->softDelete($id);

        if ($request->wantsJson()) {
            return $this->json(['message' => 'Customer deleted successfully.']);
        }

        $this->success('Customer deleted successfully.');
        return $this->redirect('/customers');
    }

    // -------------------------------------------------------------------------
    // Ledger — placeholder
    // -------------------------------------------------------------------------

    public function ledger(Request $request, int $id): Response
    {
        $customer = $this->customers->findById($id);

        if ($customer === null) {
            return $this->notFound('Customer not found.');
        }

        return $this->render('customers/ledger', [
            'pageTitle'   => sanitize($customer['name']) . ' — Ledger',
            'breadcrumbs' => ['Customers' => '/customers', sanitize($customer['name']) => '/customers/' . $id, 'Ledger' => null],
            'customer'    => $customer,
        ]);
    }

    // -------------------------------------------------------------------------
    // Orders — placeholder
    // -------------------------------------------------------------------------

    public function orders(Request $request, int $id): Response
    {
        $customer = $this->customers->findById($id);

        if ($customer === null) {
            return $this->notFound('Customer not found.');
        }

        return $this->render('customers/orders', [
            'pageTitle'   => sanitize($customer['name']) . ' — Orders',
            'breadcrumbs' => ['Customers' => '/customers', sanitize($customer['name']) => '/customers/' . $id, 'Orders' => null],
            'customer'    => $customer,
        ]);
    }

    // -------------------------------------------------------------------------
    // Credit history — placeholder (route exists in web.php)
    // -------------------------------------------------------------------------

    public function creditHistory(Request $request, int $id): Response
    {
        $customer = $this->customers->findById($id);

        if ($customer === null) {
            return $this->notFound('Customer not found.');
        }

        return $this->render('customers/ledger', [
            'pageTitle'   => sanitize($customer['name']) . ' — Credit History',
            'breadcrumbs' => ['Customers' => '/customers', sanitize($customer['name']) => '/customers/' . $id, 'Credit History' => null],
            'customer'    => $customer,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Validate customer input and return an array of errors keyed by field.
     *
     * @param  array<string, mixed> $data
     * @return array<string, string[]>
     */
    private function validateCustomer(array $data): array
    {
        $errors = [];

        // name — required, max 200
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Customer name is required.';
        } elseif (mb_strlen($name) > 200) {
            $errors['name'][] = 'Customer name must not exceed 200 characters.';
        }

        // email — optional but valid if provided
        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address.';
        }

        // phone — optional, max 30
        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '' && mb_strlen($phone) > 30) {
            $errors['phone'][] = 'Phone number must not exceed 30 characters.';
        }

        // status — must be active or inactive
        $status = (string) ($data['status'] ?? '');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        return $errors;
    }

    /**
     * Strip unwanted keys and cast numeric fields.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeData(array $data): array
    {
        return [
            'name'         => trim((string) ($data['name'] ?? '')),
            'email'        => trim((string) ($data['email'] ?? '')) ?: null,
            'phone'        => trim((string) ($data['phone'] ?? '')) ?: null,
            'address'      => trim((string) ($data['address'] ?? '')) ?: null,
            'city'         => trim((string) ($data['city'] ?? '')) ?: null,
            'country'      => trim((string) ($data['country'] ?? '')) ?: null,
            'tax_number'   => trim((string) ($data['tax_number'] ?? '')) ?: null,
            'credit_limit' => max(0, (float) ($data['credit_limit'] ?? 0)),
            'status'       => in_array($data['status'] ?? '', ['active', 'inactive'], true)
                                  ? $data['status']
                                  : 'active',
            'notes'        => trim((string) ($data['notes'] ?? '')) ?: null,
        ];
    }

    /**
     * Build a pagination array compatible with the pagination component.
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

    /**
     * Read and consume a flash value from the session.
     */
    private function getFlash(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Return a 404 response using the error view.
     */
    private function notFound(string $message): Response
    {
        $this->error($message);
        return $this->redirect('/customers');
    }
}
