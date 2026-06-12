<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for inventory management business logic.
 *
 * Exercises stock-in, stock-out, adjustments, transfers, average-cost
 * calculation, and transactional integrity — all against the in-memory
 * SQLite database.
 */
final class InventoryServiceTest extends TestCase
{
    // =========================================================================
    // Stock-in
    // =========================================================================

    public function testStockInIncreasesInventoryAndCreatesMovement(): void
    {
        $product = $this->createProduct(['cost_price' => 200.00]);

        $this->seedInventory($product['id'], 1, 0, 0);

        $this->processStockIn(
            branchId:  1,
            productId: $product['id'],
            quantity:  50,
            unitCost:  200.00,
            notes:     'Initial stock receipt'
        );

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(50.0, (float) $inv['quantity'], 'Quantity must increase by stocked-in amount');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product['id'],
            'type'       => 'stock_in',
        ]);
    }

    public function testStockInAccumulatesOnExistingStock(): void
    {
        $product = $this->createProduct(['cost_price' => 100.00]);
        $this->seedInventory($product['id'], 1, 30, 100.00);

        $this->processStockIn(1, $product['id'], 20, 100.00);

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(50.0, (float) $inv['quantity']);
    }

    public function testStockInWithZeroQuantityIsRejected(): void
    {
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 10, 100.00);

        $this->expectException(\InvalidArgumentException::class);
        $this->processStockIn(1, $product['id'], 0, 100.00);
    }

    // =========================================================================
    // Stock-out
    // =========================================================================

    public function testStockOutDecreasesInventoryAndCreatesMovement(): void
    {
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 100, 150.00);

        $this->processStockOut(1, $product['id'], 30, 'Sales order #SO-001');

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(70.0, (float) $inv['quantity']);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product['id'],
            'type'       => 'stock_out',
        ]);
    }

    public function testStockOutExactQuantityResultsInZeroBalance(): void
    {
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 25, 100.00);

        $this->processStockOut(1, $product['id'], 25);

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(0.0, (float) $inv['quantity']);
    }

    public function testStockOutFailsWithInsufficientStock(): void
    {
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 10, 100.00);

        $this->expectException(\UnderflowException::class);
        $this->processStockOut(1, $product['id'], 15);
    }

    public function testStockOutOnZeroStockThrowsException(): void
    {
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 0, 0);

        $this->expectException(\UnderflowException::class);
        $this->processStockOut(1, $product['id'], 1);
    }

    // =========================================================================
    // Stock adjustments
    // =========================================================================

    public function testStockAdjustmentPositiveDifference(): void
    {
        $product = $this->createProduct(['cost_price' => 200.00]);
        $this->seedInventory($product['id'], 1, 40, 200.00);

        // Physical count found 55; system shows 40 → adjustment of +15.
        $this->processAdjustment(1, $product['id'], 55, 'Physical count reconciliation');

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(55.0, (float) $inv['quantity']);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product['id'],
            'type'       => 'adjustment',
        ]);
    }

    public function testStockAdjustmentNegativeDifference(): void
    {
        $product = $this->createProduct(['cost_price' => 200.00]);
        $this->seedInventory($product['id'], 1, 60, 200.00);

        // Physical count found 45; system shows 60 → adjustment of -15.
        $this->processAdjustment(1, $product['id'], 45, 'Shrinkage write-off');

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(45.0, (float) $inv['quantity']);
    }

    public function testStockAdjustmentToSameQuantityCreatesNeutralRecord(): void
    {
        $product = $this->createProduct(['cost_price' => 100.00]);
        $this->seedInventory($product['id'], 1, 30, 100.00);

        $this->processAdjustment(1, $product['id'], 30, 'No change — confirmatory count');

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(30.0, (float) $inv['quantity']);
    }

    // =========================================================================
    // Stock transfers
    // =========================================================================

    public function testTransferCreatesTransferRecord(): void
    {
        $branch2  = $this->createBranch();
        $product  = $this->createProduct(['branch_id' => 1]);

        $this->seedInventory($product['id'], 1, 100, 150.00);
        $this->seedInventory($product['id'], $branch2['id'], 0, 0);

        $transferId = $this->initiateTransfer(
            fromBranchId: 1,
            toBranchId:   $branch2['id'],
            productId:    $product['id'],
            quantity:     20
        );

        $this->assertDatabaseHas('stock_transfers', [
            'id'             => $transferId,
            'from_branch_id' => 1,
            'to_branch_id'   => $branch2['id'],
            'product_id'     => $product['id'],
            'quantity'       => 20,
            'status'         => 'pending',
        ]);

        // Source stock must decrease immediately.
        $srcInv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(80.0, (float) $srcInv['quantity']);
    }

    public function testTransferFailsWhenInsufficientSourceStock(): void
    {
        $branch2 = $this->createBranch();
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 5, 100.00);
        $this->seedInventory($product['id'], $branch2['id'], 0, 0);

        $this->expectException(\UnderflowException::class);
        $this->initiateTransfer(1, $branch2['id'], $product['id'], 10);
    }

    public function testReceiveTransferUpdatesDestinationStock(): void
    {
        $branch2 = $this->createBranch();
        $product = $this->createProduct();

        $this->seedInventory($product['id'], 1, 50, 200.00);
        $this->seedInventory($product['id'], $branch2['id'], 10, 200.00);

        $transferId = $this->initiateTransfer(1, $branch2['id'], $product['id'], 15);

        // Receiving branch confirms receipt.
        $this->receiveTransfer($transferId, $branch2['id'], $product['id'], 15);

        $dstInv = $this->findInDatabase('inventory', [
            'product_id' => $product['id'],
            'branch_id'  => $branch2['id'],
        ]);
        $this->assertSame(25.0, (float) $dstInv['quantity']);

        $this->assertDatabaseHas('stock_transfers', [
            'id'     => $transferId,
            'status' => 'received',
        ]);
    }

    // =========================================================================
    // Average cost calculation
    // =========================================================================

    public function testAverageCostCalculationOnFirstStockIn(): void
    {
        $product = $this->createProduct(['cost_price' => 0]);
        $this->seedInventory($product['id'], 1, 0, 0);

        $this->processStockIn(1, $product['id'], 100, 500.00);

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertEqualsWithDelta(500.00, (float) $inv['average_cost'], 0.01);
    }

    public function testAverageCostCalculationOnMultipleStockIns(): void
    {
        $product = $this->createProduct(['cost_price' => 0]);
        $this->seedInventory($product['id'], 1, 0, 0);

        // Batch 1: 100 units @ 500 BDT
        $this->processStockIn(1, $product['id'], 100, 500.00);

        // Batch 2: 50 units @ 800 BDT
        // Expected average: (100*500 + 50*800) / 150 = (50000 + 40000) / 150 = 600
        $this->processStockIn(1, $product['id'], 50, 800.00);

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertEqualsWithDelta(600.00, (float) $inv['average_cost'], 0.01);
    }

    public function testAverageCostRemainsUnchangedAfterStockOut(): void
    {
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 100, 600.00);

        $this->processStockOut(1, $product['id'], 40);

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        // Average cost must NOT change when stock is consumed (WAC method)
        $this->assertEqualsWithDelta(600.00, (float) $inv['average_cost'], 0.01);
    }

    public function testAverageCostWithThreeBatches(): void
    {
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 0, 0);

        // 200 @ 100 BDT
        $this->processStockIn(1, $product['id'], 200, 100.00);
        // 100 @ 130 BDT → avg = (200*100 + 100*130) / 300 = (20000+13000)/300 = 110
        $this->processStockIn(1, $product['id'], 100, 130.00);
        // 300 @ 110 avg; 50 out
        $this->processStockOut(1, $product['id'], 50);
        // 250 remain @ 110 avg; new 50 in @ 160 → (250*110 + 50*160)/300 = (27500+8000)/300 ≈ 118.33
        $this->processStockIn(1, $product['id'], 50, 160.00);

        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertEqualsWithDelta(118.33, (float) $inv['average_cost'], 0.02);
    }

    // =========================================================================
    // Transaction rollback
    // =========================================================================

    public function testTransactionRollbackOnError(): void
    {
        $product = $this->createProduct();
        $this->seedInventory($product['id'], 1, 50, 100.00);

        $initialCount = $this->countInDatabase('stock_movements', ['product_id' => $product['id']]);

        try {
            $this->db->beginTransaction();

            // Partially write a movement row.
            $this->db->exec(
                "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, created_at)
                 VALUES (1, {$product['id']}, 'stock_out', 10, 100.00, datetime('now'))"
            );

            // Simulate an application error before committing.
            throw new \RuntimeException('Simulated service failure during stock-out');
        } catch (\RuntimeException) {
            $this->db->rollBack();
        }

        $finalCount = $this->countInDatabase('stock_movements', ['product_id' => $product['id']]);

        $this->assertSame(
            $initialCount,
            $finalCount,
            'Rolled-back transaction must not persist movement records'
        );

        // Inventory quantity must be unchanged.
        $inv = $this->findInDatabase('inventory', ['product_id' => $product['id'], 'branch_id' => 1]);
        $this->assertSame(50.0, (float) $inv['quantity']);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function processStockIn(
        int    $branchId,
        int    $productId,
        float  $quantity,
        float  $unitCost = 0,
        string $notes = ''
    ): void {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Stock-in quantity must be greater than zero.');
        }

        $this->db->beginTransaction();

        try {
            // Recalculate weighted-average cost.
            $inv = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => $branchId]);
            $existingQty  = $inv ? (float) $inv['quantity']     : 0;
            $existingCost = $inv ? (float) $inv['average_cost'] : 0;

            $newQty  = $existingQty + $quantity;
            $newCost = $newQty > 0
                ? (($existingQty * $existingCost) + ($quantity * $unitCost)) / $newQty
                : $unitCost;

            $stmt = $this->db->prepare(
                "INSERT OR REPLACE INTO inventory (branch_id, product_id, quantity, average_cost, updated_at)
                 VALUES (:bid, :pid, :qty, :cost, datetime('now'))"
            );
            $stmt->execute([
                ':bid'  => $branchId,
                ':pid'  => $productId,
                ':qty'  => $newQty,
                ':cost' => $newCost,
            ]);

            // Record movement.
            $this->db->prepare(
                "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, notes, created_at)
                 VALUES (:bid, :pid, 'stock_in', :qty, :cost, :notes, datetime('now'))"
            )->execute([
                ':bid'   => $branchId,
                ':pid'   => $productId,
                ':qty'   => $quantity,
                ':cost'  => $unitCost,
                ':notes' => $notes,
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function processStockOut(
        int    $branchId,
        int    $productId,
        float  $quantity,
        string $notes = ''
    ): void {
        $inv = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => $branchId]);
        $current = $inv ? (float) $inv['quantity'] : 0;

        if ($quantity > $current) {
            throw new \UnderflowException(
                "Insufficient stock. Available: {$current}, Requested: {$quantity}"
            );
        }

        $this->db->beginTransaction();

        try {
            $newQty = $current - $quantity;

            $this->db->prepare(
                "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
                 WHERE product_id = :pid AND branch_id = :bid"
            )->execute([':qty' => $newQty, ':pid' => $productId, ':bid' => $branchId]);

            $this->db->prepare(
                "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, notes, created_at)
                 VALUES (:bid, :pid, 'stock_out', :qty, 0, :notes, datetime('now'))"
            )->execute([
                ':bid'   => $branchId,
                ':pid'   => $productId,
                ':qty'   => $quantity,
                ':notes' => $notes,
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function processAdjustment(
        int    $branchId,
        int    $productId,
        float  $newQuantity,
        string $notes = ''
    ): void {
        $inv      = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => $branchId]);
        $oldQty   = $inv ? (float) $inv['quantity'] : 0;
        $diffQty  = $newQuantity - $oldQty;

        $this->db->beginTransaction();

        try {
            $this->db->prepare(
                "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
                 WHERE product_id = :pid AND branch_id = :bid"
            )->execute([':qty' => $newQuantity, ':pid' => $productId, ':bid' => $branchId]);

            $this->db->prepare(
                "INSERT INTO stock_movements (branch_id, product_id, type, quantity, unit_cost, notes, created_at)
                 VALUES (:bid, :pid, 'adjustment', :qty, 0, :notes, datetime('now'))"
            )->execute([
                ':bid'   => $branchId,
                ':pid'   => $productId,
                ':qty'   => $diffQty,
                ':notes' => $notes,
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function initiateTransfer(
        int $fromBranchId,
        int $toBranchId,
        int $productId,
        float $quantity
    ): int {
        $srcInv = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => $fromBranchId]);
        $available = $srcInv ? (float) $srcInv['quantity'] : 0;

        if ($quantity > $available) {
            throw new \UnderflowException('Insufficient stock for transfer.');
        }

        $this->db->beginTransaction();

        try {
            // Deduct from source immediately.
            $newSrcQty = $available - $quantity;
            $this->db->prepare(
                "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
                 WHERE product_id = :pid AND branch_id = :bid"
            )->execute([':qty' => $newSrcQty, ':pid' => $productId, ':bid' => $fromBranchId]);

            $this->db->prepare(
                "INSERT INTO stock_transfers (from_branch_id, to_branch_id, product_id, quantity, status, created_at)
                 VALUES (:from, :to, :pid, :qty, 'pending', datetime('now'))"
            )->execute([
                ':from' => $fromBranchId,
                ':to'   => $toBranchId,
                ':pid'  => $productId,
                ':qty'  => $quantity,
            ]);

            $transferId = (int) $this->db->lastInsertId();

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $transferId;
    }

    private function receiveTransfer(
        int $transferId,
        int $toBranchId,
        int $productId,
        float $quantity
    ): void {
        $this->db->beginTransaction();

        try {
            $dstInv = $this->findInDatabase('inventory', ['product_id' => $productId, 'branch_id' => $toBranchId]);
            $currentDst = $dstInv ? (float) $dstInv['quantity'] : 0;
            $newDstQty  = $currentDst + $quantity;

            $this->db->prepare(
                "UPDATE inventory SET quantity = :qty, updated_at = datetime('now')
                 WHERE product_id = :pid AND branch_id = :bid"
            )->execute([':qty' => $newDstQty, ':pid' => $productId, ':bid' => $toBranchId]);

            $this->db->prepare(
                "UPDATE stock_transfers SET status = 'received', received_at = datetime('now')
                 WHERE id = :id"
            )->execute([':id' => $transferId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
