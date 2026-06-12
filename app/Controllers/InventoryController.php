<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Http\Request;
use App\Http\Response;
use App\Services\InventoryService;

class InventoryController extends BaseController
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly Database $db
    ) {}

    public function index(Request $request): Response
    {
        $user      = $this->currentUser();
        $branchId  = $user->branchId ?? 0;
        $page      = max(1, (int)$request->query('page', 1));
        $search    = $request->query('search');
        $catId     = $request->query('category_id');
        $lowStock  = $request->query('low_stock');
        $perPage   = 20;

        $where    = ['p.is_active = 1', 'p.deleted_at IS NULL', 'w.branch_id = ?'];
        $bindings = [$branchId];

        if ($search) {
            $where[]    = "(p.name LIKE ? OR p.sku LIKE ?)";
            $bindings[] = '%' . $search . '%';
            $bindings[] = '%' . $search . '%';
        }
        if ($catId) {
            $where[]    = 'p.category_id = ?';
            $bindings[] = (int)$catId;
        }

        $having = $lowStock ? 'HAVING current_stock <= p.reorder_point' : '';
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->fetchAll(
            "SELECT p.id, p.name, p.sku, p.reorder_point,
                    c.name AS category_name,
                    COALESCE(SUM(i.quantity), 0) AS current_stock,
                    COALESCE(SUM(i.quantity * i.avg_cost), 0) AS stock_value
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN inventory i ON i.product_id = p.id
             LEFT JOIN warehouses w ON w.id = i.warehouse_id
             {$whereClause}
             GROUP BY p.id, p.name, p.sku, p.reorder_point, c.name
             {$having}
             ORDER BY p.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $total = count($rows);

        $categories = $this->db->fetchAll(
            "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name"
        );

        return $this->render('inventory/index', [
            'pageTitle'   => 'Inventory',
            'breadcrumbs' => ['Inventory' => null],
            'items'       => array_map(fn($r) => array_merge($r, [
                'is_low_stock' => (float)$r['current_stock'] <= (float)$r['reorder_point'],
            ]), $rows),
            'pagination'  => paginate($total, $page, $perPage),
            'categories'  => $categories,
            'currentUser' => $user,
            'filters'     => compact('search', 'catId', 'lowStock'),
            'headerActions' => '
                <a href="/inventory/stock-in" class="btn btn-success btn-sm me-2"><i class="fas fa-arrow-down me-1"></i>Stock In</a>
                <a href="/inventory/stock-out" class="btn btn-warning btn-sm me-2"><i class="fas fa-arrow-up me-1"></i>Stock Out</a>
                <a href="/inventory/transfers" class="btn btn-info btn-sm"><i class="fas fa-exchange-alt me-1"></i>Transfer</a>
            ',
        ]);
    }

    public function stockIn(Request $request): Response
    {
        $user      = $this->currentUser();
        $branchId  = $user->branchId ?? 0;
        $warehouses = $this->db->fetchAll(
            "SELECT id, name FROM warehouses WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );

        return $this->render('inventory/stock_in', [
            'pageTitle'   => 'Stock In',
            'breadcrumbs' => ['Inventory' => '/inventory', 'Stock In' => null],
            'warehouses'  => $warehouses,
            'currentUser' => $user,
        ]);
    }

    public function processStockIn(Request $request): Response
    {
        $data = $request->all();
        $user = $this->currentUser();

        try {
            $this->inventoryService->stockIn(
                productId:   (int)$data['product_id'],
                variantId:   (int)($data['variant_id'] ?? 0) ?: null,
                warehouseId: (int)$data['warehouse_id'],
                quantity:    (float)$data['quantity'],
                unitCost:    (float)$data['unit_cost'],
                reference:   $data['reference'] ?? null,
                notes:       $data['notes'] ?? null,
                createdBy:   $user->id ?? 0
            );
            $this->success('Stock in recorded successfully.');
            return $this->redirect('/inventory');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->withInput($request);
            return $this->back();
        }
    }

    public function stockOut(Request $request): Response
    {
        $user       = $this->currentUser();
        $branchId   = $user->branchId ?? 0;
        $warehouses = $this->db->fetchAll(
            "SELECT id, name FROM warehouses WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );

        return $this->render('inventory/stock_out', [
            'pageTitle'   => 'Stock Out',
            'breadcrumbs' => ['Inventory' => '/inventory', 'Stock Out' => null],
            'warehouses'  => $warehouses,
            'currentUser' => $user,
        ]);
    }

    public function processStockOut(Request $request): Response
    {
        $data = $request->all();
        $user = $this->currentUser();

        try {
            $this->inventoryService->stockOut(
                productId:   (int)$data['product_id'],
                variantId:   (int)($data['variant_id'] ?? 0) ?: null,
                warehouseId: (int)$data['warehouse_id'],
                quantity:    (float)$data['quantity'],
                reference:   $data['reference'] ?? null,
                notes:       $data['notes'] ?? null,
                createdBy:   $user->id ?? 0
            );
            $this->success('Stock out recorded successfully.');
            return $this->redirect('/inventory');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->withInput($request);
            return $this->back();
        }
    }

    public function movements(Request $request): Response
    {
        $page    = max(1, (int)$request->query('page', 1));
        $perPage = 25;
        $from    = $request->query('from', date('Y-m-01'));
        $to      = $request->query('to', date('Y-m-d'));

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM stock_movements WHERE created_at BETWEEN ? AND ?",
            [$from . ' 00:00:00', $to . ' 23:59:59']
        );

        $movements = $this->db->fetchAll(
            "SELECT sm.*, p.name AS product_name, p.sku, w.name AS warehouse_name
             FROM stock_movements sm
             JOIN products p ON p.id = sm.product_id
             JOIN warehouses w ON w.id = sm.warehouse_id
             WHERE sm.created_at BETWEEN ? AND ?
             ORDER BY sm.created_at DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            [$from . ' 00:00:00', $to . ' 23:59:59']
        );

        return $this->render('inventory/movements', [
            'pageTitle'   => 'Stock Movements',
            'breadcrumbs' => ['Inventory' => '/inventory', 'Movements' => null],
            'movements'   => $movements,
            'pagination'  => paginate($total, $page, $perPage),
            'currentUser' => $this->currentUser(),
            'filters'     => compact('from', 'to'),
        ]);
    }
}
