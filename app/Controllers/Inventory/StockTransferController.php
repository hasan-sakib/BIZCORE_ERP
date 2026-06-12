<?php

declare(strict_types=1);

namespace App\Controllers\Inventory;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\InventoryRepository;

/**
 * StockTransferController
 *
 * Handles listing, creating, viewing, and status transitions for stock transfers.
 */
final class StockTransferController extends BaseController
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
            $result     = $this->repo->paginateTransfers($filters, $page);
            $warehouses = $this->allWarehouses();
        } catch (\Throwable $e) {
            $result     = ['items' => [], 'total' => 0, 'page' => 1, 'lastPage' => 1];
            $warehouses = [];
            $this->error('Could not load transfers: ' . $e->getMessage());
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

        return $this->render('inventory/transfers/index', [
            'pageTitle'  => 'Stock Transfers',
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
        return $this->render('inventory/transfers/create', [
            'pageTitle'  => 'New Transfer',
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

        $errors = $this->validateTransfer($data, $items);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/inventory/transfers/create');
        }

        try {
            $user = $this->currentUser();
            $data['reference_no'] = $this->repo->generateTransferRef();
            $data['created_by']   = $user?->id ?? 0;

            $id = $this->repo->createTransfer($data, $items);
            $this->success('Transfer ' . $data['reference_no'] . ' created successfully.');
            return $this->redirect("/inventory/transfers/{$id}");
        } catch (\Throwable $e) {
            $this->error('Failed to create transfer: ' . $e->getMessage());
            $this->withInput($request);
            return $this->redirect('/inventory/transfers/create');
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        try {
            $transfer = $this->repo->findTransfer($id);
        } catch (\Throwable $e) {
            $this->error('Could not load transfer: ' . $e->getMessage());
            return $this->redirect('/inventory/transfers');
        }

        if ($transfer === null) {
            $this->error('Transfer not found.');
            return $this->redirect('/inventory/transfers');
        }

        return $this->render('inventory/transfers/show', [
            'pageTitle' => 'Transfer — ' . sanitize($transfer['reference_no']),
            'transfer'  => $transfer,
        ]);
    }

    // -------------------------------------------------------------------------
    // Confirm
    // -------------------------------------------------------------------------

    public function confirm(int $id): Response
    {
        try {
            $transfer = $this->repo->findTransfer($id);
            if ($transfer === null) {
                $this->error('Transfer not found.');
                return $this->redirect('/inventory/transfers');
            }

            if ($transfer['status'] !== 'draft') {
                $this->error('Only draft transfers can be confirmed.');
                return $this->redirect("/inventory/transfers/{$id}");
            }

            $this->repo->updateTransferStatus($id, 'confirmed');
            $this->success('Transfer confirmed successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to confirm transfer: ' . $e->getMessage());
        }

        return $this->redirect("/inventory/transfers/{$id}");
    }

    // -------------------------------------------------------------------------
    // Receive
    // -------------------------------------------------------------------------

    public function receive(int $id): Response
    {
        try {
            $transfer = $this->repo->findTransfer($id);
            if ($transfer === null) {
                $this->error('Transfer not found.');
                return $this->redirect('/inventory/transfers');
            }

            if (!in_array($transfer['status'], ['confirmed', 'in_transit'], true)) {
                $this->error('Transfer cannot be received in its current status.');
                return $this->redirect("/inventory/transfers/{$id}");
            }

            $this->repo->updateTransferStatus($id, 'received');
            $this->success('Transfer marked as received. Stock levels updated.');
        } catch (\Throwable $e) {
            $this->error('Failed to receive transfer: ' . $e->getMessage());
        }

        return $this->redirect("/inventory/transfers/{$id}");
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    public function cancel(int $id): Response
    {
        try {
            $transfer = $this->repo->findTransfer($id);
            if ($transfer === null) {
                $this->error('Transfer not found.');
                return $this->redirect('/inventory/transfers');
            }

            if (in_array($transfer['status'], ['received', 'cancelled'], true)) {
                $this->error('Transfer cannot be cancelled in its current status.');
                return $this->redirect("/inventory/transfers/{$id}");
            }

            $this->repo->updateTransferStatus($id, 'cancelled');
            $this->success('Transfer cancelled.');
        } catch (\Throwable $e) {
            $this->error('Failed to cancel transfer: ' . $e->getMessage());
        }

        return $this->redirect("/inventory/transfers/{$id}");
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
    private function validateTransfer(array $data, array $items): array
    {
        $errors = [];

        if (empty($data['from_warehouse_id'])) {
            $errors['from_warehouse_id'][] = 'Source warehouse is required.';
        }

        if (empty($data['to_warehouse_id'])) {
            $errors['to_warehouse_id'][] = 'Destination warehouse is required.';
        }

        if (!empty($data['from_warehouse_id']) && !empty($data['to_warehouse_id'])
            && $data['from_warehouse_id'] === $data['to_warehouse_id']) {
            $errors['to_warehouse_id'][] = 'Source and destination warehouses must be different.';
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
}
