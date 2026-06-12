<?php

declare(strict_types=1);

namespace App\Controllers\Inventory;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\CatalogRepository;

/**
 * BrandController
 *
 * Handles CRUD for product brands.
 */
final class BrandController extends BaseController
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
        $items  = $this->catalog->allBrands($search);

        return $this->render('products/brands/index', [
            'pageTitle' => 'Brands',
            'items'     => $items,
            'search'    => $search,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        return $this->render('products/brands/create', [
            'pageTitle' => 'Add Brand',
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
        $errors = $this->validateBrand($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/products/brands/create');
        }

        $data['slug'] = $this->slugify((string) $data['name']);

        $this->catalog->createBrand($data);
        $this->success('Brand created successfully.');

        return $this->redirect('/products/brands');
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $item = $this->catalog->findBrand($id);

        if ($item === null) {
            $this->error('Brand not found.');
            return $this->redirect('/products/brands');
        }

        return $this->render('products/brands/edit', [
            'pageTitle' => 'Edit Brand',
            'item'      => $item,
            'errors'    => session()->getFlash('errors', []),
            'old'       => session()->getFlash('_old_input', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $item = $this->catalog->findBrand($id);

        if ($item === null) {
            $this->error('Brand not found.');
            return $this->redirect('/products/brands');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateBrand($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect("/products/brands/{$id}/edit");
        }

        $data['slug'] = $this->slugify((string) $data['name']);

        $this->catalog->updateBrand($id, $data);
        $this->success('Brand updated successfully.');

        return $this->redirect('/products/brands');
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $item = $this->catalog->findBrand($id);

        if ($item === null) {
            $this->error('Brand not found.');
            return $this->redirect('/products/brands');
        }

        $this->catalog->softDeleteBrand($id);
        $this->success('Brand deleted successfully.');

        return $this->redirect('/products/brands');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>              $data
     * @return array<string, list<string>>
     */
    private function validateBrand(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Brand name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'][] = 'Brand name must not exceed 100 characters.';
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
