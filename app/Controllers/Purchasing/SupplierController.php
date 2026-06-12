<?php

declare(strict_types=1);

namespace App\Controllers\Purchasing;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\SupplierRepository;

/**
 * SupplierController
 *
 * Handles CRUD for suppliers plus placeholder ledger and orders views.
 * Thin controller: validate → repository → render / redirect.
 */
final class SupplierController extends BaseController
{
    public function __construct(
        private readonly SupplierRepository $suppliers,
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

        $result = $this->suppliers->paginate($filters, $page, 20);

        $pagination = $this->buildPagination($result['total'], $page, 20);

        return $this->render('suppliers/index', [
            'pageTitle'     => 'Suppliers',
            'breadcrumbs'   => ['Suppliers' => null],
            'headerActions' => '<a href="/suppliers/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Supplier</a>',
            'suppliers'     => $result['items'],
            'filters'       => $filters,
            'pagination'    => $pagination,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        return $this->render('suppliers/create', [
            'pageTitle'   => 'Add Supplier',
            'breadcrumbs' => ['Suppliers' => '/suppliers', 'Add Supplier' => null],
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
        $errors = $this->validateSupplier($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/suppliers/create');
        }

        $id = $this->suppliers->create($this->sanitizeData($data));
        $this->success('Supplier created successfully.');
        return $this->redirect('/suppliers/' . $id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, int $id): Response
    {
        $supplier = $this->suppliers->findById($id);

        if ($supplier === null) {
            return $this->notFound('Supplier not found.');
        }

        return $this->render('suppliers/show', [
            'pageTitle'   => sanitize($supplier['name']),
            'breadcrumbs' => ['Suppliers' => '/suppliers', sanitize($supplier['name']) => null],
            'supplier'    => $supplier,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(Request $request, int $id): Response
    {
        $supplier = $this->suppliers->findById($id);

        if ($supplier === null) {
            return $this->notFound('Supplier not found.');
        }

        $errors = $this->getFlash('errors', []);
        $old    = $this->getFlash('old', $supplier);

        return $this->render('suppliers/edit', [
            'pageTitle'   => 'Edit — ' . sanitize($supplier['name']),
            'breadcrumbs' => ['Suppliers' => '/suppliers', sanitize($supplier['name']) => '/suppliers/' . $id, 'Edit' => null],
            'supplier'    => $supplier,
            'errors'      => $errors,
            'old'         => $old,
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $supplier = $this->suppliers->findById($id);

        if ($supplier === null) {
            return $this->notFound('Supplier not found.');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateSupplier($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/suppliers/' . $id . '/edit');
        }

        $this->suppliers->update($id, $this->sanitizeData($data));
        $this->success('Supplier updated successfully.');
        return $this->redirect('/suppliers/' . $id);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(Request $request, int $id): Response
    {
        $supplier = $this->suppliers->findById($id);

        if ($supplier === null) {
            if ($request->wantsJson()) {
                return $this->json(['message' => 'Supplier not found.'], 404);
            }
            $this->error('Supplier not found.');
            return $this->redirect('/suppliers');
        }

        $this->suppliers->softDelete($id);

        if ($request->wantsJson()) {
            return $this->json(['message' => 'Supplier deleted successfully.']);
        }

        $this->success('Supplier deleted successfully.');
        return $this->redirect('/suppliers');
    }

    // -------------------------------------------------------------------------
    // Ledger — placeholder
    // -------------------------------------------------------------------------

    public function ledger(Request $request, int $id): Response
    {
        $supplier = $this->suppliers->findById($id);

        if ($supplier === null) {
            return $this->notFound('Supplier not found.');
        }

        return $this->render('suppliers/ledger', [
            'pageTitle'   => sanitize($supplier['name']) . ' — Ledger',
            'breadcrumbs' => ['Suppliers' => '/suppliers', sanitize($supplier['name']) => '/suppliers/' . $id, 'Ledger' => null],
            'supplier'    => $supplier,
        ]);
    }

    // -------------------------------------------------------------------------
    // Orders — placeholder
    // -------------------------------------------------------------------------

    public function orders(Request $request, int $id): Response
    {
        $supplier = $this->suppliers->findById($id);

        if ($supplier === null) {
            return $this->notFound('Supplier not found.');
        }

        return $this->render('suppliers/orders', [
            'pageTitle'   => sanitize($supplier['name']) . ' — Orders',
            'breadcrumbs' => ['Suppliers' => '/suppliers', sanitize($supplier['name']) => '/suppliers/' . $id, 'Orders' => null],
            'supplier'    => $supplier,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Validate supplier input and return an array of errors keyed by field.
     *
     * @param  array<string, mixed> $data
     * @return array<string, string[]>
     */
    private function validateSupplier(array $data): array
    {
        $errors = [];

        // name — required, max 200
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Supplier name is required.';
        } elseif (mb_strlen($name) > 200) {
            $errors['name'][] = 'Supplier name must not exceed 200 characters.';
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

        // payment_terms — optional, text (no max specified)

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
            'name'          => trim((string) ($data['name'] ?? '')),
            'email'         => trim((string) ($data['email'] ?? '')) ?: null,
            'phone'         => trim((string) ($data['phone'] ?? '')) ?: null,
            'address'       => trim((string) ($data['address'] ?? '')) ?: null,
            'city'          => trim((string) ($data['city'] ?? '')) ?: null,
            'country'       => trim((string) ($data['country'] ?? '')) ?: null,
            'tax_number'    => trim((string) ($data['tax_number'] ?? '')) ?: null,
            'payment_terms' => trim((string) ($data['payment_terms'] ?? '')) ?: null,
            'credit_limit'  => max(0, (float) ($data['credit_limit'] ?? 0)),
            'status'        => in_array($data['status'] ?? '', ['active', 'inactive'], true)
                                   ? $data['status']
                                   : 'active',
            'notes'         => trim((string) ($data['notes'] ?? '')) ?: null,
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
     * Redirect with an error flash and return to list.
     */
    private function notFound(string $message): Response
    {
        $this->error($message);
        return $this->redirect('/suppliers');
    }
}
