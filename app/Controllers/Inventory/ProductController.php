<?php

declare(strict_types=1);

namespace App\Controllers\Inventory;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\CatalogRepository;
use App\Repositories\ProductRepository;

/**
 * ProductController
 *
 * Handles CRUD for products plus stock-level view.
 */
final class ProductController extends BaseController
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CatalogRepository $catalog,
    ) {}

    // -------------------------------------------------------------------------
    // Index — paginated list with filters
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [
            'search'      => (string) $request->query('search', ''),
            'category_id' => (string) $request->query('category_id', ''),
            'status'      => (string) $request->query('status', ''),
        ];

        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->products->paginate($filters, $page);

        $categories = $this->catalog->allCategories();

        // Build pagination array matching the component's expectations
        $perPage    = 20;
        $total      = $result['total'];
        $lastPage   = $result['lastPage'];
        $pagination = [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'total_pages'  => $lastPage,
            'from'         => $total > 0 ? ($page - 1) * $perPage + 1 : 0,
            'to'           => min($page * $perPage, $total),
            'has_previous' => $page > 1,
            'has_next'     => $page < $lastPage,
        ];

        return $this->render('products/index', [
            'pageTitle'  => 'Products',
            'result'     => $result,
            'filters'    => $filters,
            'categories' => $categories,
            'pagination' => $pagination,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        $categories = $this->catalog->allCategories();
        $brands     = $this->catalog->allBrands();
        $units      = $this->catalog->allUnits();
        $autoSku    = $this->products->generateSku('PRD');

        return $this->render('products/create', [
            'pageTitle'  => 'Add Product',
            'categories' => $categories,
            'brands'     => $brands,
            'units'      => $units,
            'autoSku'    => $autoSku,
            'errors'     => session()->getFlash('errors', []),
            'old'        => session()->getFlash('_old_input', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token']);
        $errors = $this->validateProduct($data);

        // Auto-generate SKU if left blank
        if (trim((string) ($data['sku'] ?? '')) === '') {
            $data['sku'] = $this->products->generateSku('PRD');
        } elseif ($this->products->skuExists((string) $data['sku'])) {
            $errors['sku'][] = 'This SKU is already taken.';
        }

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/products/create');
        }

        $id = $this->products->create($data);
        $this->success('Product created successfully.');

        return $this->redirect("/products/{$id}");
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            $this->error('Product not found.');
            return $this->redirect('/products');
        }

        return $this->render('products/show', [
            'pageTitle' => sanitize($product['name']),
            'product'   => $product,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            $this->error('Product not found.');
            return $this->redirect('/products');
        }

        $categories = $this->catalog->allCategories();
        $brands     = $this->catalog->allBrands();
        $units      = $this->catalog->allUnits();

        return $this->render('products/edit', [
            'pageTitle'  => 'Edit Product',
            'product'    => $product,
            'categories' => $categories,
            'brands'     => $brands,
            'units'      => $units,
            'errors'     => session()->getFlash('errors', []),
            'old'        => session()->getFlash('_old_input', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            $this->error('Product not found.');
            return $this->redirect('/products');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateProduct($data);

        $sku = trim((string) ($data['sku'] ?? ''));
        if ($sku === '') {
            $data['sku'] = $this->products->generateSku('PRD');
        } elseif ($this->products->skuExists($sku, $id)) {
            $errors['sku'][] = 'This SKU is already taken.';
        }

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect("/products/{$id}/edit");
        }

        $this->products->update($id, $data);
        $this->success('Product updated successfully.');

        return $this->redirect("/products/{$id}");
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            $this->error('Product not found.');
            return $this->redirect('/products');
        }

        $this->products->softDelete($id);
        $this->success('Product deleted successfully.');

        return $this->redirect('/products');
    }

    // -------------------------------------------------------------------------
    // Stock levels — per-warehouse breakdown
    // -------------------------------------------------------------------------

    public function stockLevels(int $id): Response
    {
        $product = $this->products->findById($id);

        if ($product === null) {
            $this->error('Product not found.');
            return $this->redirect('/products');
        }

        // Fetch per-warehouse stock from inventory table (if it exists).
        // Falls back gracefully when the table is not yet migrated.
        try {
            $stocks = app(\PDO::class)->prepare(
                "SELECT w.name AS warehouse_name, w.id AS warehouse_id,
                        COALESCE(i.quantity_on_hand, 0)  AS quantity_on_hand,
                        COALESCE(i.quantity_reserved, 0) AS quantity_reserved,
                        COALESCE(i.quantity_on_hand - i.quantity_reserved, 0) AS quantity_available
                 FROM warehouses w
                 LEFT JOIN inventory i ON i.warehouse_id = w.id
                                      AND i.product_id = :product_id
                                      AND i.deleted_at IS NULL
                 WHERE w.deleted_at IS NULL
                 ORDER BY w.name ASC",
            );
            $stocks->execute([':product_id' => $id]);
            $stockRows = $stocks->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $stockRows = [];
        }

        return $this->render('products/show', [
            'pageTitle'  => sanitize($product['name']) . ' — Stock Levels',
            'product'    => $product,
            'stockRows'  => $stockRows,
            'showStock'  => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>              $data
     * @return array<string, list<string>>
     */
    private function validateProduct(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Product name is required.';
        } elseif (mb_strlen($name) > 200) {
            $errors['name'][] = 'Product name must not exceed 200 characters.';
        }

        $sku = trim((string) ($data['sku'] ?? ''));
        if ($sku !== '' && mb_strlen($sku) > 50) {
            $errors['sku'][] = 'SKU must not exceed 50 characters.';
        }

        if (empty($data['category_id'])) {
            $errors['category_id'][] = 'Category is required.';
        }

        $sellingPrice = $data['selling_price'] ?? '';
        if ($sellingPrice === '' || $sellingPrice === null) {
            $errors['selling_price'][] = 'Selling price is required.';
        } elseif (!is_numeric($sellingPrice) || (float) $sellingPrice < 0) {
            $errors['selling_price'][] = 'Selling price must be a non-negative number.';
        }

        $validStatuses = ['active', 'inactive', 'discontinued'];
        $status        = (string) ($data['status'] ?? '');
        if (!in_array($status, $validStatuses, true)) {
            $errors['status'][] = 'Status must be one of: ' . implode(', ', $validStatuses) . '.';
        }

        return $errors;
    }
}
