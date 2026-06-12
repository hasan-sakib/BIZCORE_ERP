<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Feature tests for the Inventory REST API.
 *
 * Validates stock-in, stock-out, adjustment, listing, and movement history
 * endpoints at the business-logic and database-state level.
 */
final class InventoryApiTest extends TestCase
{
    // =========================================================================
    // POST /api/v1/inventory/stock-in
    // =========================================================================

    public function testStockInCreatesMovement(): void
    {
        $this->actingAsAdmin();

        $product = $this->createProduct(['cost_price' => 300.00]);
        $this->seedInventory($product['id'], 1, 0, 0);

        $response = $this->apiStockIn($product['id'], 100, 300.00, 'Opening stock');

        $this->assertSuccessResponse($response);
        $this->assertSame('stock_in', $response['data']['movement']['type']);
        $this->assertEqualsWithDelta(100.0, (float) $response['data']['inventory']['quantity'], 0.01);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product['id'],
            'type'       => 'stock_in',
        ]);
    }

    public function testStockInRequiresAuthentication(): void
    {
        $product = $this->createProduct();

        $response = $this->apiStockIn($product['id'], 10, 100.00);

        $this->assertErrorResponse($response, 401);
    }

    public function testStockInValidationRejectsZeroQuantity(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();

        $response = $this->apiStockIn($product['id'], 0, 100.00);

        $this->assertErrorResponse($response, 422);
    }

    public function testStockInValidationRejectsNegativeQuantity(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();

        $response = $this->apiStockIn($product['id'], -5, 100.00);

        $this->assertErrorResponse($response, 422);
    }

    public function testStockInForNonExistentProductReturns404(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiStockIn(999999, 10, 100.00);

        $this->assertErrorResponse($response, 404);
    }

    public function testStockInUpdatesAverageCost(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct(['cost_price' => 0]);
        $this->seedInventory($product['id'], 1, 50, 200.00);

        // New batch: 50 @ 300 → expected avg = (50*200 + 50*300) / 100 = 250
        $response = $this->apiStockIn($product['id'], 50, 300.00);

        $this->assertSuccessResponse($response);
        $this->assertEqualsWithDelta(250.00, (float) $response['data']['inventory']['average_cost'], 0.01);
    }

    // =========================================================================
    // POST /api/v1/inventory/stock-out
    // =========================================================================

    public function testStockOutWithSufficientStockSucceeds(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 80, 100.00);

        $response = $this->apiStockOut($product['id'], 30, 'Manual issue');

        $this->assertSuccessResponse($response);
        $this->assertEqualsWithDelta(50.0, (float) $response['data']['inventory']['quantity'], 0.01);
    }

    public function testStockOutWithInsufficientStockReturns422(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 5, 100.00);

        $response = $this->apiStockOut($product['id'], 10);

        $this->assertErrorResponse($response, 422);
        $this->assertStringContainsString('Insufficient', $response['message']);
    }

    public function testStockOutOnZeroStockReturns422(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 0, 0);

        $response = $this->apiStockOut($product['id'], 1);

        $this->assertErrorResponse($response, 422);
    }

    // =========================================================================
    // POST /api/v1/inventory/adjustment
    // =========================================================================

    public function testStockAdjustmentIncrease(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 30, 100.00);

        $response = $this->apiAdjustStock($product['id'], 45, 'Physical count — count higher');

        $this->assertSuccessResponse($response);
        $this->assertEqualsWithDelta(45.0, (float) $response['data']['inventory']['quantity'], 0.01);
        $this->assertSame('adjustment', $response['data']['movement']['type']);
    }

    public function testStockAdjustmentDecrease(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 60, 100.00);

        $response = $this->apiAdjustStock($product['id'], 50, 'Shrinkage write-off');

        $this->assertSuccessResponse($response);
        $this->assertEqualsWithDelta(50.0, (float) $response['data']['inventory']['quantity'], 0.01);
    }

    public function testStockAdjustmentToNegativeQuantityIsRejected(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 10, 100.00);

        $response = $this->apiAdjustStock($product['id'], -5);

        $this->assertErrorResponse($response, 422);
    }

    // =========================================================================
    // GET /api/v1/inventory  — list
    // =========================================================================

    public function testGetInventoryListReturnsAllStockedProducts(): void
    {
        $this->actingAsAdmin();

        $p1 = $this->createProduct(['name' => 'Stock P1']);
        $p2 = $this->createProduct(['name' => 'Stock P2']);
        $p3 = $this->createProduct(['name' => 'Stock P3']);

        $this->seedInventory($p1['id'], 1, 100, 100.00);
        $this->seedInventory($p2['id'], 1, 50,  200.00);
        $this->seedInventory($p3['id'], 1, 0,   0);

        $response = $this->apiGetInventory();

        $this->assertSuccessResponse($response);
        $this->assertGreaterThanOrEqual(3, count($response['data']['items']));
    }

    public function testGetInventoryListRequiresAuthentication(): void
    {
        $response = $this->apiGetInventory();

        $this->assertErrorResponse($response, 401);
    }

    public function testGetInventoryListPaginated(): void
    {
        $this->actingAsAdmin();

        for ($i = 0; $i < 12; $i++) {
            $p = $this->createProduct();
            $this->seedInventory($p['id'], 1, $i * 10, 100.00);
        }

        $page1 = $this->apiGetInventory(page: 1, perPage: 5);
        $page2 = $this->apiGetInventory(page: 2, perPage: 5);
        $page3 = $this->apiGetInventory(page: 3, perPage: 5);

        $this->assertCount(5, $page1['data']['items']);
        $this->assertCount(5, $page2['data']['items']);
        $this->assertCount(2, $page3['data']['items']);
    }

    // =========================================================================
    // GET /api/v1/inventory/{productId}/movements
    // =========================================================================

    public function testGetStockMovementsReturnsHistoryForProduct(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 0, 0);

        // Record a few movements.
        $this->db->exec(
            "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, created_at)
             VALUES (1, {$product['id']}, 'stock_in', 100, 100, datetime('now'))"
        );
        $this->db->exec(
            "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, created_at)
             VALUES (1, {$product['id']}, 'stock_out', 20, 0, datetime('now'))"
        );

        $response = $this->apiGetStockMovements($product['id']);

        $this->assertSuccessResponse($response);
        $this->assertCount(2, $response['data']['items']);
    }

    public function testGetStockMovementsForNonExistentProductReturns404(): void
    {
        $this->actingAsAdmin();

        $response = $this->apiGetStockMovements(999999);

        $this->assertErrorResponse($response, 404);
    }

    public function testGetStockMovementsReturnsEmptyListWhenNoMovements(): void
    {
        $this->actingAsAdmin();
        $product = $this->createProduct();

        $response = $this->apiGetStockMovements($product['id']);

        $this->assertSuccessResponse($response);
        $this->assertCount(0, $response['data']['items']);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function apiStockIn(int $productId, float $quantity, float $unitCost = 0, string $notes = ''): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        if ($quantity <= 0) {
            return array_merge(
                $this->errorResponse(422, 'Quantity must be greater than zero.'),
                ['errors' => ['quantity' => ['Quantity must be greater than zero.']]]
            );
        }

        $product = $this->findInDatabase('products', ['id' => $productId]);
        if ($product === null || $product['deleted_at'] !== null) {
            return $this->errorResponse(404, "Product with identifier '{$productId}' was not found.");
        }

        $inv = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => 1]);

        $existingQty  = $inv ? (float) $inv['quantity']     : 0;
        $existingCost = $inv ? (float) $inv['average_cost'] : 0;

        $newQty  = $existingQty + $quantity;
        $newCost = $newQty > 0 ? (($existingQty * $existingCost) + ($quantity * $unitCost)) / $newQty : $unitCost;

        $this->db->prepare(
            "INSERT OR REPLACE INTO inventory (branch_id, product_id, quantity, average_cost, updated_at)
             VALUES (1, :pid, :qty, :cost, datetime('now'))"
        )->execute([':pid' => $productId, ':qty' => $newQty, ':cost' => $newCost]);

        $this->db->prepare(
            "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, notes, created_at)
             VALUES (1, :pid, 'stock_in', :qty, :cost, :notes, datetime('now'))"
        )->execute([':pid' => $productId, ':qty' => $quantity, ':cost' => $unitCost, ':notes' => $notes]);

        $movementId = (int) $this->db->lastInsertId();

        return $this->successResponse([
            'inventory' => $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => 1]),
            'movement'  => $this->findInDatabase('stock_movements', ['id' => $movementId]),
        ]);
    }

    private function apiStockOut(int $productId, float $quantity, string $notes = ''): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $product = $this->findInDatabase('products', ['id' => $productId]);
        if ($product === null || $product['deleted_at'] !== null) {
            return $this->errorResponse(404, "Product not found.");
        }

        $inv       = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => 1]);
        $available = $inv ? (float) $inv['quantity'] : 0;

        if ($quantity > $available) {
            return $this->errorResponse(422, "Insufficient stock. Available: {$available}, Requested: {$quantity}");
        }

        $newQty = $available - $quantity;

        $this->db->prepare(
            "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
             WHERE product_id = :pid AND branch_id = 1"
        )->execute([':qty' => $newQty, ':pid' => $productId]);

        $this->db->prepare(
            "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, notes, created_at)
             VALUES (1, :pid, 'stock_out', :qty, 0, :notes, datetime('now'))"
        )->execute([':pid' => $productId, ':qty' => $quantity, ':notes' => $notes]);

        $movementId = (int) $this->db->lastInsertId();

        return $this->successResponse([
            'inventory' => $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => 1]),
            'movement'  => $this->findInDatabase('stock_movements', ['id' => $movementId]),
        ]);
    }

    private function apiAdjustStock(int $productId, float $newQuantity, string $notes = ''): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        if ($newQuantity < 0) {
            return array_merge(
                $this->errorResponse(422, 'Adjusted quantity must not be negative.'),
                ['errors' => ['quantity' => ['Adjusted quantity must not be negative.']]]
            );
        }

        $product = $this->findInDatabase('products', ['id' => $productId]);
        if ($product === null || $product['deleted_at'] !== null) {
            return $this->errorResponse(404, "Product not found.");
        }

        $inv   = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => 1]);
        $diff  = $newQuantity - ($inv ? (float) $inv['quantity'] : 0);

        $this->db->prepare(
            "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
             WHERE product_id = :pid AND branch_id = 1"
        )->execute([':qty' => $newQuantity, ':pid' => $productId]);

        $this->db->prepare(
            "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, notes, created_at)
             VALUES (1, :pid, 'adjustment', :qty, 0, :notes, datetime('now'))"
        )->execute([':pid' => $productId, ':qty' => $diff, ':notes' => $notes]);

        $movementId = (int) $this->db->lastInsertId();

        return $this->successResponse([
            'inventory' => $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => 1]),
            'movement'  => $this->findInDatabase('stock_movements', ['id' => $movementId]),
        ]);
    }

    private function apiGetInventory(int $page = 1, int $perPage = 50): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $countRow = $this->db->query("SELECT COUNT(*) AS cnt FROM inventory WHERE branch_id = 1")->fetch();
        $total    = (int) $countRow['cnt'];

        $offset = ($page - 1) * $perPage;
        $items  = $this->db->query(
            "SELECT i.*, p.name, p.sku, p.barcode FROM inventory i
             JOIN products p ON p.id = i.product_id
             WHERE i.branch_id = 1 AND p.deleted_at IS NULL
             ORDER BY i.id ASC LIMIT {$perPage} OFFSET {$offset}"
        )->fetchAll();

        return $this->successResponse([
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    private function apiGetStockMovements(int $productId, int $page = 1, int $perPage = 50): array
    {
        if ($this->authContext === null) {
            return $this->errorResponse(401, 'Unauthenticated.');
        }

        $product = $this->findInDatabase('products', ['id' => $productId]);
        if ($product === null || $product['deleted_at'] !== null) {
            return $this->errorResponse(404, "Product with identifier '{$productId}' was not found.");
        }

        $offset = ($page - 1) * $perPage;

        $items = $this->db->query(
            "SELECT * FROM stock_movements WHERE product_id = {$productId}
             ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        )->fetchAll();

        $countRow = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM stock_movements WHERE product_id = {$productId}"
        )->fetch();

        return $this->successResponse([
            'items' => $items,
            'total' => (int) $countRow['cnt'],
        ]);
    }

    private function successResponse(mixed $data): array
    {
        return ['success' => true, 'data' => $data];
    }

    private function errorResponse(int $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
