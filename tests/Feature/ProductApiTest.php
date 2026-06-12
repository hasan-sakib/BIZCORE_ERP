<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Feature tests for the Product REST API endpoint behaviour.
 *
 * Each test exercises the business rules an API controller would enforce:
 * authentication, validation, CRUD operations, search, filtering, and
 * pagination — all validated against the in-memory SQLite database.
 */
final class ProductApiTest extends TestCase
{
    // =========================================================================
    // GET /api/v1/products  — list with pagination
    // =========================================================================

    public function testGetProductsListReturnsAllActiveProducts(): void
    {
        $this->actingAsAdmin();

        $this->createProduct(['name' => 'Widget A', 'category' => 'Electronics']);
        $this->createProduct(['name' => 'Widget B', 'category' => 'Electronics']);
        $this->createProduct(['name' => 'Gadget C', 'category' => 'Tools']);

        $response = $this->apiGetProducts();

        $this->assertSuccessResponse($response);
        $this->assertCount(3, $response['data']['items']);
    }

    public function testGetProductsListPaginated(): void
    {
        $this->actingAsAdmin();

        // Create 15 products.
        for ($i = 0; $i < 15; $i++) {
            $this->createProduct(['name' => "Product {$i}"]);
        }

        $page1 = $this->apiGetProducts(page: 1, perPage: 10);
        $page2 = $this->apiGetProducts(page: 2, perPage: 10);

        $this->assertSuccessResponse($page1);
        $this->assertSuccessResponse($page2);

        $this->assertCount(10, $page1['data']['items'],  'Page 1 must contain 10 items');
        $this->assertCount(5,  $page2['data']['items'],  'Page 2 must contain remaining 5 items');
        $this->assertSame(15,  $page1['data']['total'],  'Total must reflect all 15 products');
        $this->assertSame(2,   $page1['data']['last_page']);
    }

    public function testGetProductsListRequiresAuthentication(): void
    {
        // No actingAs() — unauthenticated.
        $response = $this->apiGetProducts();

        $this->assertErrorResponse($response, 401);
    }

    public function testGetProductsListExcludesSoftDeletedProducts(): void
    {
        $this->actingAsAdmin();

        $active  = $this->createProduct(['name' => 'Active Product']);
        $deleted = $this->createProduct(['name' => 'Deleted Product']);

        // Soft-delete the second product.
        $this->db->exec(
            "UPDATE products SET deleted_at = datetime('now') WHERE id = {$deleted['id']}"
        );

        $response = $this->apiGetProducts();

        $ids = array_column($response['data']['items'], 'id');
        $this->assertContains($active['id'],  $ids);
        $this->assertNotContains($deleted['id'], $ids);
    }

    // =========================================================================
    // GET /api/v1/products/{id}
    // =========================================================================

    public function testGetProductById(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct(['name' => 'Detail Product', 'sku' => 'DETAIL-001']);

        $response = $this->apiGetProductById($product['id']);

        $this->assertSuccessResponse($response);
        $this->assertSame($product['id'],   $response['data']['id']);
        $this->assertSame('Detail Product', $response['data']['name']);
        $this->assertSame('DETAIL-001',     $response['data']['sku']);
    }

    public function testGetProductByIdReturns404ForNonExistentProduct(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiGetProductById(999999);

        $this->assertErrorResponse($response, 404);
    }

    public function testGetProductByIdReturns404ForSoftDeletedProduct(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();

        $this->db->exec("UPDATE products SET deleted_at = datetime('now') WHERE id = {$product['id']}");

        $response = $this->apiGetProductById($product['id']);

        $this->assertErrorResponse($response, 404);
    }

    // =========================================================================
    // POST /api/v1/products  — create
    // =========================================================================

    public function testCreateProductRequiresAuth(): void
    {
        // No actingAs() set.
        $response = $this->apiCreateProduct([
            'name'       => 'New Product',
            'sku'        => 'NEW-001',
            'sale_price' => 100.00,
        ]);

        $this->assertErrorResponse($response, 401);
    }

    public function testCreateProductWithValidData(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiCreateProduct([
            'name'        => 'Brand New Widget',
            'sku'         => 'BNW-001',
            'barcode'     => '8800000010001',
            'category'    => 'Electronics',
            'unit'        => 'pcs',
            'cost_price'  => 200.00,
            'sale_price'  => 350.00,
            'description' => 'A brand new widget',
        ]);

        $this->assertSuccessResponse($response);
        $this->assertSame('Brand New Widget', $response['data']['name']);
        $this->assertSame('BNW-001',          $response['data']['sku']);

        $this->assertDatabaseHas('products', ['sku' => 'BNW-001', 'branch_id' => 1]);
    }

    public function testCreateProductValidationErrorsOnMissingName(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiCreateProduct([
            'sku'        => 'NONAME-001',
            'sale_price' => 100.00,
            // 'name' intentionally missing
        ]);

        $this->assertErrorResponse($response, 422);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('name', $response['errors']);
    }

    public function testCreateProductValidationErrorsOnMissingSku(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiCreateProduct([
            'name'       => 'Product Without SKU',
            'sale_price' => 100.00,
        ]);

        $this->assertErrorResponse($response, 422);
        $this->assertArrayHasKey('sku', $response['errors'] ?? []);
    }

    public function testCreateProductValidationRejectsDuplicateSku(): void
    {
        $this->actingAsAdmin();
        $this->createProduct(['sku' => 'DUP-SKU-001']);

        $response = $this->apiCreateProduct([
            'name' => 'Duplicate SKU Product',
            'sku'  => 'DUP-SKU-001',
        ]);

        $this->assertErrorResponse($response, 422);
    }

    public function testCreateProductValidationRejectsNegativeSalePrice(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiCreateProduct([
            'name'       => 'Negative Price Product',
            'sku'        => 'NEG-PRICE-001',
            'sale_price' => -50.00,
        ]);

        $this->assertErrorResponse($response, 422);
    }

    // =========================================================================
    // PUT /api/v1/products/{id}  — update
    // =========================================================================

    public function testUpdateProduct(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct(['name' => 'Old Name', 'sale_price' => 100.00]);

        $response = $this->apiUpdateProduct($product['id'], [
            'name'       => 'Updated Name',
            'sale_price' => 175.00,
        ]);

        $this->assertSuccessResponse($response);
        $this->assertSame('Updated Name', $response['data']['name']);
        $this->assertEqualsWithDelta(175.00, (float) $response['data']['sale_price'], 0.01);

        $this->assertDatabaseHas('products', ['id' => $product['id'], 'name' => 'Updated Name']);
    }

    public function testUpdateProductReturns404ForNonExistentId(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiUpdateProduct(999999, ['name' => 'Ghost Product']);

        $this->assertErrorResponse($response, 404);
    }

    public function testUpdateProductRequiresAuth(): void
    {
        $product = $this->createProduct();

        $response = $this->apiUpdateProduct($product['id'], ['name' => 'Attempt']);

        $this->assertErrorResponse($response, 401);
    }

    // =========================================================================
    // DELETE /api/v1/products/{id}  — soft-delete
    // =========================================================================

    public function testDeleteProduct(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct(['name' => 'To Be Deleted']);

        $response = $this->apiDeleteProduct($product['id']);

        $this->assertSuccessResponse($response);
        $this->assertSoftDeleted('products', ['id' => $product['id']]);
    }

    public function testDeleteProductReturns404ForAlreadyDeleted(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();

        $this->db->exec("UPDATE products SET deleted_at = datetime('now') WHERE id = {$product['id']}");

        $response = $this->apiDeleteProduct($product['id']);

        $this->assertErrorResponse($response, 404);
    }

    // =========================================================================
    // Barcode search
    // =========================================================================

    public function testProductBarcodeSearch(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct(['barcode' => '8801234567890', 'name' => 'Barcode Product']);

        $response = $this->apiSearchByBarcode('8801234567890');

        $this->assertSuccessResponse($response);
        $this->assertSame($product['id'], $response['data']['id']);
    }

    public function testProductBarcodeSearchReturns404ForUnknownBarcode(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiSearchByBarcode('0000000000000');

        $this->assertErrorResponse($response, 404);
    }

    // =========================================================================
    // Name search
    // =========================================================================

    public function testSearchProductsByName(): void
    {
        $this->actingAsAdmin();
        $this->createProduct(['name' => 'Anchor Bolt 10mm']);
        $this->createProduct(['name' => 'Anchor Bolt 12mm']);
        $this->createProduct(['name' => 'Nylon Rope 5m']);

        $response = $this->apiSearchProducts('Anchor Bolt');

        $this->assertSuccessResponse($response);
        $this->assertCount(2, $response['data']['items']);
    }

    public function testSearchProductsReturnsEmptyResultForNoMatch(): void
    {
        $this->actingAsAdmin();
        $this->createProduct(['name' => 'Random Widget']);

        $response = $this->apiSearchProducts('XyzNoMatchABC');

        $this->assertSuccessResponse($response);
        $this->assertCount(0, $response['data']['items']);
    }

    // =========================================================================
    // Filter by category
    // =========================================================================

    public function testFilterProductsByCategory(): void
    {
        $this->actingAsAdmin();
        $this->createProduct(['category' => 'Electronics', 'name' => 'TV']);
        $this->createProduct(['category' => 'Electronics', 'name' => 'Radio']);
        $this->createProduct(['category' => 'Furniture',   'name' => 'Chair']);

        $response = $this->apiGetProducts(category: 'Electronics');

        $this->assertSuccessResponse($response);
        $this->assertCount(2, $response['data']['items']);

        foreach ($response['data']['items'] as $item) {
            $this->assertSame('Electronics', $item['category']);
        }
    }

    // =========================================================================
    // Private helpers — simulate API controller behaviour
    // =========================================================================

    private function apiGetProducts(
        int    $page     = 1,
        int    $perPage  = 50,
        ?string $category = null
    ): array {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $where  = 'deleted_at IS NULL';
        $params = [];

        if ($category !== null) {
            $where   .= " AND category = :category";
            $params[':category'] = $category;
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM products WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['cnt'];

        $offset  = ($page - 1) * $perPage;
        $query   = $this->db->prepare(
            "SELECT * FROM products WHERE {$where} ORDER BY id ASC LIMIT :limit OFFSET :offset"
        );
        $query->execute(array_merge($params, [':limit' => $perPage, ':offset' => $offset]));
        $items = $query->fetchAll();

        $lastPage = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        return $this->successResponse([
            'items'     => $items,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
        ]);
    }

    private function apiGetProductById(int $id): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $product = $this->findInDatabase('products', ['id' => $id]);

        if ($product === null || $product['deleted_at'] !== null) {
            return $this->errorResponse(404, "Product with identifier '{$id}' was not found.");
        }

        return $this->successResponse($product);
    }

    private function apiCreateProduct(array $data): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $errors = [];

        if (empty($data['name'])) {
            $errors['name'][] = 'The name field is required.';
        }

        if (empty($data['sku'])) {
            $errors['sku'][] = 'The SKU field is required.';
        }

        if (isset($data['sale_price']) && (float) $data['sale_price'] < 0) {
            $errors['sale_price'][] = 'Sale price must not be negative.';
        }

        if (!empty($data['sku'])) {
            $existing = $this->findInDatabase('products', ['sku' => $data['sku'], 'branch_id' => 1]);
            if ($existing !== null) {
                $errors['sku'][] = 'A product with this SKU already exists.';
            }
        }

        if (!empty($errors)) {
            return array_merge($this->errorResponse(422, 'The given data was invalid.'), ['errors' => $errors]);
        }

        $product = $this->createProduct([
            'name'        => $data['name'],
            'sku'         => $data['sku'],
            'barcode'     => $data['barcode']     ?? null,
            'category'    => $data['category']    ?? 'General',
            'unit'        => $data['unit']         ?? 'pcs',
            'cost_price'  => $data['cost_price']   ?? 0,
            'sale_price'  => $data['sale_price']   ?? 0,
            'description' => $data['description']  ?? null,
        ]);

        return $this->successResponse($product);
    }

    private function apiUpdateProduct(int $id, array $data): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $product = $this->findInDatabase('products', ['id' => $id]);

        if ($product === null || $product['deleted_at'] !== null) {
            return $this->errorResponse(404, "Product with identifier '{$id}' was not found.");
        }

        $updates = [];
        $params  = [':id' => $id];

        if (isset($data['name'])) {
            $updates[]        = 'name = :name';
            $params[':name']  = $data['name'];
        }

        if (isset($data['sale_price'])) {
            $updates[]              = 'sale_price = :sale_price';
            $params[':sale_price']  = $data['sale_price'];
        }

        if (isset($data['cost_price'])) {
            $updates[]              = 'cost_price = :cost_price';
            $params[':cost_price']  = $data['cost_price'];
        }

        if (!empty($updates)) {
            $updates[] = "updated_at = datetime('now')";
            $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id";
            $this->db->prepare($sql)->execute($params);
        }

        return $this->successResponse($this->findInDatabase('products', ['id' => $id]));
    }

    private function apiDeleteProduct(int $id): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $product = $this->findInDatabase('products', ['id' => $id]);

        if ($product === null || $product['deleted_at'] !== null) {
            return $this->errorResponse(404, "Product with identifier '{$id}' was not found.");
        }

        $this->db->exec("UPDATE products SET deleted_at = datetime('now') WHERE id = {$id}");

        return $this->successResponse(['id' => $id, 'deleted' => true]);
    }

    private function apiSearchByBarcode(string $barcode): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $product = $this->findInDatabase('products', ['barcode' => $barcode]);

        if ($product === null || $product['deleted_at'] !== null) {
            return $this->errorResponse(404, "No product found with barcode '{$barcode}'.");
        }

        return $this->successResponse($product);
    }

    private function apiSearchProducts(string $query): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM products WHERE name LIKE :q AND deleted_at IS NULL ORDER BY name ASC"
        );
        $stmt->execute([':q' => "%{$query}%"]);
        $items = $stmt->fetchAll();

        return $this->successResponse(['items' => $items, 'total' => count($items)]);
    }

    // =========================================================================
    // Response builder helpers
    // =========================================================================

    private function successResponse(mixed $data): array
    {
        return ['success' => true, 'data' => $data];
    }

    private function errorResponse(int $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
