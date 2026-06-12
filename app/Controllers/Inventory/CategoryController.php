<?php

declare(strict_types=1);

namespace App\Controllers\Inventory;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\CatalogRepository;

/**
 * CategoryController
 *
 * Handles CRUD for product categories.
 * Thin controller: validate → repository → redirect or render.
 */
final class CategoryController extends BaseController
{
    public function __construct(
        private readonly CatalogRepository $catalog,
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $items  = $this->catalog->allCategories($search);

        return $this->render('categories/index', [
            'pageTitle' => 'Product Categories',
            'items'     => $items,
            'search'    => $search,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        $parents = $this->catalog->allCategories();

        return $this->render('categories/create', [
            'pageTitle' => 'Add Category',
            'parents'   => $parents,
            'errors'    => session()->getFlash('errors', []),
            'old'       => session()->getFlash('_old_input', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token']);
        $errors = $this->validateCategory($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/products/categories/create');
        }

        $data['slug'] = $this->slugify((string) $data['name']);

        $this->catalog->createCategory($data);
        $this->success('Category created successfully.');

        return $this->redirect('/products/categories');
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $item = $this->catalog->findCategory($id);

        if ($item === null) {
            $this->error('Category not found.');
            return $this->redirect('/products/categories');
        }

        $parents = $this->catalog->allCategories();

        return $this->render('categories/edit', [
            'pageTitle' => 'Edit Category',
            'item'      => $item,
            'parents'   => $parents,
            'errors'    => session()->getFlash('errors', []),
            'old'       => session()->getFlash('_old_input', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $item = $this->catalog->findCategory($id);

        if ($item === null) {
            $this->error('Category not found.');
            return $this->redirect('/products/categories');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateCategory($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect("/products/categories/{$id}/edit");
        }

        $data['slug'] = $this->slugify((string) $data['name']);

        $this->catalog->updateCategory($id, $data);
        $this->success('Category updated successfully.');

        return $this->redirect('/products/categories');
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $item = $this->catalog->findCategory($id);

        if ($item === null) {
            $this->error('Category not found.');
            return $this->redirect('/products/categories');
        }

        $this->catalog->softDeleteCategory($id);
        $this->success('Category deleted successfully.');

        return $this->redirect('/products/categories');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>              $data
     * @return array<string, list<string>>
     */
    private function validateCategory(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Category name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'][] = 'Category name must not exceed 100 characters.';
        }

        $status = (string) ($data['status'] ?? '');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        return $errors;
    }

    private function slugify(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($name)) ?? '');
    }
}
