<?php

declare(strict_types=1);

namespace App\Controllers\Expenses;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\ExpenseRepository;

/**
 * ExpenseController
 *
 * Full CRUD for expenses plus approve/reject workflow.
 */
final class ExpenseController extends BaseController
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
    ) {}

    // -------------------------------------------------------------------------
    // List
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [];

        $categoryId = (int) $request->query('category_id', 0);
        if ($categoryId > 0) {
            $filters['category_id'] = $categoryId;
        }

        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $filters['status'] = $status;
        }

        $dateFrom = (string) $request->query('date_from', '');
        if ($dateFrom !== '') {
            $filters['date_from'] = $dateFrom;
        }

        $dateTo = (string) $request->query('date_to', '');
        if ($dateTo !== '') {
            $filters['date_to'] = $dateTo;
        }

        $search = (string) $request->query('search', '');
        if ($search !== '') {
            $filters['search'] = $search;
        }

        $page       = max(1, (int) $request->query('page', 1));
        $result     = $this->expenses->paginate($filters, $page);
        $categories = $this->expenses->allCategories();

        $pagination = $this->buildPagination($result['total'], $page, 20);

        return $this->render('expenses/index', [
            'pageTitle'   => 'Expenses',
            'breadcrumbs' => ['Expenses' => null],
            'expenses'    => $result['items'],
            'categories'  => $categories,
            'pagination'  => $pagination,
            'filters'     => [
                'category_id' => $categoryId,
                'status'      => $status,
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
                'search'      => $search,
            ],
            'headerActions' => '<a href="/expenses/create" class="btn btn-primary btn-sm">'
                             . '<i class="fas fa-plus me-1"></i>New Expense</a>',
        ]);
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        $categories = $this->expenses->allCategories();

        return $this->render('expenses/create', [
            'pageTitle'   => 'Create Expense',
            'breadcrumbs' => ['Expenses' => '/expenses', 'Create' => null],
            'categories'  => $categories,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('_old_input', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token', '_method']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/expenses/create');
        }

        $user = $this->currentUser();

        $data['reference_no'] = $this->expenses->generateRef();
        $data['created_by']   = $user?->id ?? 0;
        $data['status']       = 'pending';

        $id = $this->expenses->create($data);
        $this->success('Expense created successfully.');
        return $this->redirect('/expenses/' . $id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        $expense = $this->expenses->findById($id);
        if ($expense === null) {
            $this->error('Expense not found.');
            return $this->redirect('/expenses');
        }

        return $this->render('expenses/show', [
            'pageTitle'   => 'Expense: ' . sanitize($expense['reference_no'] ?? ''),
            'breadcrumbs' => ['Expenses' => '/expenses', ($expense['reference_no'] ?? 'Detail') => null],
            'expense'     => $expense,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $expense = $this->expenses->findById($id);
        if ($expense === null) {
            $this->error('Expense not found.');
            return $this->redirect('/expenses');
        }

        $categories = $this->expenses->allCategories();

        return $this->render('expenses/edit', [
            'pageTitle'   => 'Edit Expense: ' . ($expense['reference_no'] ?? ''),
            'breadcrumbs' => ['Expenses' => '/expenses', ($expense['reference_no'] ?? '') => '/expenses/' . $id, 'Edit' => null],
            'expense'     => $expense,
            'categories'  => $categories,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('_old_input', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $expense = $this->expenses->findById($id);
        if ($expense === null) {
            $this->error('Expense not found.');
            return $this->redirect('/expenses');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/expenses/' . $id . '/edit');
        }

        $this->expenses->update($id, $data);
        $this->success('Expense updated successfully.');
        return $this->redirect('/expenses/' . $id);
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $expense = $this->expenses->findById($id);
        if ($expense === null) {
            $this->error('Expense not found.');
            return $this->redirect('/expenses');
        }

        $this->expenses->softDelete($id);
        $this->success('Expense deleted.');
        return $this->redirect('/expenses');
    }

    // -------------------------------------------------------------------------
    // Approve / Reject
    // -------------------------------------------------------------------------

    public function approve(Request $request, int $id): Response
    {
        $expense = $this->expenses->findById($id);
        if ($expense === null) {
            $this->error('Expense not found.');
            return $this->redirect('/expenses');
        }

        $user = $this->currentUser();
        $this->expenses->approve($id, $user?->id ?? 0);
        $this->success('Expense approved successfully.');
        return $this->redirect('/expenses/' . $id);
    }

    public function reject(Request $request, int $id): Response
    {
        $expense = $this->expenses->findById($id);
        if ($expense === null) {
            $this->error('Expense not found.');
            return $this->redirect('/expenses');
        }

        $this->expenses->reject($id);
        $this->success('Expense rejected.');
        return $this->redirect('/expenses/' . $id);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Validate expense input.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (empty($data['category_id'])) {
            $errors['category_id'] = 'Category is required.';
        }

        $amount = trim((string) ($data['amount'] ?? ''));
        if ($amount === '') {
            $errors['amount'] = 'Amount is required.';
        } elseif (!is_numeric($amount)) {
            $errors['amount'] = 'Amount must be a number.';
        } elseif ((float) $amount <= 0) {
            $errors['amount'] = 'Amount must be greater than zero.';
        }

        if (empty($data['date'])) {
            $errors['date'] = 'Date is required.';
        }

        return $errors;
    }

    /**
     * Build a pagination array compatible with the shared pagination component.
     *
     * @return array<string, mixed>
     */
    private function buildPagination(int $total, int $page, int $perPage): array
    {
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $from     = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
        $to       = min($page * $perPage, $total);

        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => $lastPage,
            'from'         => $from,
            'to'           => $to,
        ];
    }
}
