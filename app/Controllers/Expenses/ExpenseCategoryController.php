<?php

declare(strict_types=1);

namespace App\Controllers\Expenses;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\ExpenseRepository;

/**
 * ExpenseCategoryController
 *
 * Full CRUD for expense_categories.
 */
final class ExpenseCategoryController extends BaseController
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
    ) {}

    // -------------------------------------------------------------------------
    // List
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $search     = (string) $request->query('search', '');
        $categories = $this->expenses->allCategories($search);

        return $this->render('expenses/categories/index', [
            'pageTitle'   => 'Expense Categories',
            'breadcrumbs' => ['Expenses' => '/expenses', 'Categories' => null],
            'categories'  => $categories,
            'search'      => $search,
            'headerActions' => '<a href="/expenses/categories/create" class="btn btn-primary btn-sm">'
                             . '<i class="fas fa-plus me-1"></i>New Category</a>',
        ]);
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        return $this->render('expenses/categories/create', [
            'pageTitle'   => 'Create Expense Category',
            'breadcrumbs' => ['Expenses' => '/expenses', 'Categories' => '/expenses/categories', 'Create' => null],
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
            return $this->redirect('/expenses/categories/create');
        }

        $this->expenses->createCategory($data);
        $this->success('Category created successfully.');
        return $this->redirect('/expenses/categories');
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $category = $this->expenses->findCategory($id);
        if ($category === null) {
            $this->error('Category not found.');
            return $this->redirect('/expenses/categories');
        }

        return $this->render('expenses/categories/edit', [
            'pageTitle'   => 'Edit Category: ' . ($category['name'] ?? ''),
            'breadcrumbs' => ['Expenses' => '/expenses', 'Categories' => '/expenses/categories', 'Edit' => null],
            'category'    => $category,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('_old_input', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $category = $this->expenses->findCategory($id);
        if ($category === null) {
            $this->error('Category not found.');
            return $this->redirect('/expenses/categories');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/expenses/categories/' . $id . '/edit');
        }

        $this->expenses->updateCategory($id, $data);
        $this->success('Category updated successfully.');
        return $this->redirect('/expenses/categories');
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $category = $this->expenses->findCategory($id);
        if ($category === null) {
            $this->error('Category not found.');
            return $this->redirect('/expenses/categories');
        }

        try {
            $this->expenses->deleteCategory($id);
            $this->success('Category deleted successfully.');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/expenses/categories');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Validate expense category input.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            $errors['name'] = 'Category name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Category name must not exceed 150 characters.';
        }

        return $errors;
    }
}
