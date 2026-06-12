<?php

declare(strict_types=1);

namespace App\Controllers\Inventory;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\InventoryRepository;

/**
 * StockAdjustmentController
 *
 * Handles listing, creating, viewing, and approving stock adjustments.
 */
final class StockAdjustmentController extends BaseController
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
            'status'       => (string) $request->query('status', ''),
        ];

        $page = max(1, (int) $request->query('page', 1));

        try {
            $result     = $this->repo->paginateAdjustments($filters, $page);
            $warehouses = $this->allWarehouses();
        } catch (\Throwable $e) {
            $result     = ['items' => [], 'total' => 0, 'page' => 1, 'lastPage' => 1];
            $warehouses = [];
            $this->error('Could not load adjustments: ' . $e->getMessage());
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

        return $this->render('inventory/adjustments/index', [
            'pageTitle'  => 'Stock Adjustments',
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
        return $this->render('inventory/adjustments/create', [
            'pageTitle'  => 'New Adjustment',
            'warehouses' => $this->allWarehouses(),
            'products'   => $this->allProducts(),
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

        $errors = $this->validateAdjustment($data, $items);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/inventory/adjustments/create');
        }

        try {
            $user = $this->currentUser();
            $data['reference_no'] = $this->repo->generateAdjustmentRef();
            $data['created_by']   = $user?->id ?? 0;

            $id = $this->repo->createAdjustment($data, $items);
            $this->success('Adjustment ' . $data['reference_no'] . ' submitted for approval.');
            return $this->redirect("/inventory/adjustments/{$id}");
        } catch (\Throwable $e) {
            $this->error('Failed to save adjustment: ' . $e->getMessage());
            $this->withInput($request);
            return $this->redirect('/inventory/adjustments/create');
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        try {
            $adjustment = $this->repo->findAdjustment($id);
        } catch (\Throwable $e) {
            $this->error('Could not load adjustment: ' . $e->getMessage());
            return $this->redirect('/inventory/adjustments');
        }

        if ($adjustment === null) {
            $this->error('Adjustment not found.');
            return $this->redirect('/inventory/adjustments');
        }

        return $this->render('inventory/adjustments/show', [
            'pageTitle'  => 'Adjustment — ' . sanitize($adjustment['reference_no']),
            'adjustment' => $adjustment,
        ]);
    }

    // -------------------------------------------------------------------------
    // Approve
    // -------------------------------------------------------------------------

    public function approve(int $id): Response
    {
        try {
            $adjustment = $this->repo->findAdjustment($id);
            if ($adjustment === null) {
                $this->error('Adjustment not found.');
                return $this->redirect('/inventory/adjustments');
            }

            if ($adjustment['status'] !== 'pending') {
                $this->error('Only pending adjustments can be approved.');
                return $this->redirect("/inventory/adjustments/{$id}");
            }

            $user = $this->currentUser();
            $this->repo->approveAdjustment($id, $user?->id ?? 0);
            $this->success('Adjustment approved. Stock levels updated.');
        } catch (\Throwable $e) {
            $this->error('Failed to approve adjustment: ' . $e->getMessage());
        }

        return $this->redirect("/inventory/adjustments/{$id}");
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
            $type      = (string) ($row['type'] ?? '');
            if ($productId > 0 && $qty > 0 && in_array($type, ['add', 'remove'], true)) {
                $items[] = [
                    'product_id' => $productId,
                    'quantity'   => $qty,
                    'type'       => $type,
                    'reason'     => (string) ($row['reason'] ?? ''),
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
    private function validateAdjustment(array $data, array $items): array
    {
        $errors = [];

        if (empty($data['warehouse_id'])) {
            $errors['warehouse_id'][] = 'Warehouse is required.';
        }

        if (empty($data['date'])) {
            $errors['date'][] = 'Date is required.';
        }

        if (empty(trim((string) ($data['reason'] ?? '')))) {
            $errors['reason'][] = 'Reason is required.';
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
}
