<?php

declare(strict_types=1);

namespace App\Controllers\Inventory;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\InventoryRepository;

/**
 * StockInController
 *
 * Handles listing, creating, and viewing stock-in orders.
 */
final class StockInController extends BaseController
{
    public function __construct(
        private readonly InventoryRepository $repo,
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [
            'warehouse_id' => (string) $request->query('warehouse_id', ''),
            'date_from'    => (string) $request->query('date_from', ''),
            'date_to'      => (string) $request->query('date_to', ''),
        ];

        $page = max(1, (int) $request->query('page', 1));

        try {
            $result     = $this->repo->paginateStockIn($filters, $page);
            $warehouses = $this->allWarehouses();
        } catch (\Throwable $e) {
            $result     = ['items' => [], 'total' => 0, 'page' => 1, 'lastPage' => 1];
            $warehouses = [];
            $this->error('Could not load stock-in orders: ' . $e->getMessage());
        }

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

        return $this->render('inventory/stock-in/index', [
            'pageTitle'  => 'Stock In',
            'result'     => $result,
            'filters'    => $filters,
            'warehouses' => $warehouses,
            'pagination' => $pagination,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        return $this->render('inventory/stock-in/create', [
            'pageTitle'  => 'New Stock In',
            'warehouses' => $this->allWarehouses(),
            'products'   => $this->allProducts(),
            'suppliers'  => $this->allSuppliers(),
            'errors'     => session()->getFlash('errors', []),
            'old'        => session()->getFlash('_old_input', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(Request $request): Response
    {
        $data  = $request->except(['_token']);
        $items = $this->parseItems((array) ($data['items'] ?? []));

        $errors = $this->validateStockIn($data, $items);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/inventory/stock-in/create');
        }

        try {
            $user = $this->currentUser();
            $data['reference_no'] = $this->repo->generateStockInRef();
            $data['created_by']   = $user?->id ?? 0;

            $id = $this->repo->createStockIn($data, $items);
            $this->success('Stock-in order ' . $data['reference_no'] . ' created successfully.');
            return $this->redirect("/inventory/stock-in/{$id}");
        } catch (\Throwable $e) {
            $this->error('Failed to save stock-in order: ' . $e->getMessage());
            $this->withInput($request);
            return $this->redirect('/inventory/stock-in/create');
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        try {
            $order = $this->repo->findStockIn($id);
        } catch (\Throwable $e) {
            $this->error('Could not load record: ' . $e->getMessage());
            return $this->redirect('/inventory/stock-in');
        }

        if ($order === null) {
            $this->error('Stock-in order not found.');
            return $this->redirect('/inventory/stock-in');
        }

        return $this->render('inventory/stock-in/show', [
            'pageTitle' => 'Stock In — ' . sanitize($order['reference_no']),
            'order'     => $order,
        ]);
    }

    // -------------------------------------------------------------------------
    // PDF (placeholder)
    // -------------------------------------------------------------------------

    public function pdf(int $id): Response
    {
        $this->success('PDF export coming soon.');
        return $this->redirect("/inventory/stock-in/{$id}");
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<int|string, mixed> $raw
     * @return list<array<string, mixed>>
     */
    private function parseItems(array $raw): array
    {
        $items = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $productId = (int) ($row['product_id'] ?? 0);
            $qty       = (float) ($row['quantity'] ?? 0);
            if ($productId > 0 && $qty > 0) {
                $items[] = [
                    'product_id' => $productId,
                    'quantity'   => $qty,
                    'unit_cost'  => (float) ($row['unit_cost'] ?? 0),
                ];
            }
        }
        return $items;
    }

    /**
     * @param  array<string, mixed>          $data
     * @param  list<array<string, mixed>>    $items
     * @return array<string, list<string>>
     */
    private function validateStockIn(array $data, array $items): array
    {
        $errors = [];

        if (empty($data['warehouse_id'])) {
            $errors['warehouse_id'][] = 'Warehouse is required.';
        }

        if (empty($data['date'])) {
            $errors['date'][] = 'Date is required.';
        }

        if ($items === []) {
            $errors['items'][] = 'At least one item is required.';
        }

        return $errors;
    }

    /** @return list<array<string, mixed>> */
    private function allWarehouses(): array
    {
        try {
            $stmt = app(\PDO::class)->query(
                "SELECT id, name FROM warehouses WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC",
            );
            return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    private function allProducts(): array
    {
        try {
            $stmt = app(\PDO::class)->query(
                "SELECT id, name, sku FROM products WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC",
            );
            return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    private function allSuppliers(): array
    {
        try {
            $stmt = app(\PDO::class)->query(
                "SELECT id, name FROM suppliers WHERE deleted_at IS NULL ORDER BY name ASC",
            );
            return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
