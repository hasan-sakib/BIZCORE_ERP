<?php

declare(strict_types=1);

namespace App\Controllers\Inventory;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\InventoryRepository;

/**
 * WarehouseController
 *
 * CRUD for warehouses plus a dedicated stock-levels view.
 */
final class WarehouseController extends BaseController
{
    public function __construct(
        private readonly InventoryRepository $repo,
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');

        try {
            $warehouses = $this->repo->allWarehouses($search);
        } catch (\Throwable) {
            $warehouses = [];
            $this->error('Could not load warehouses — the table may not exist yet.');
        }

        return $this->render('inventory/warehouses/index', [
            'pageTitle'  => 'Warehouses',
            'warehouses' => $warehouses,
            'search'     => $search,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        return $this->render('inventory/warehouses/create', [
            'pageTitle' => 'Add Warehouse',
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
        $errors = $this->validateWarehouse($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/inventory/warehouses/create');
        }

        try {
            $id = $this->repo->createWarehouse($data);
            $this->success('Warehouse created successfully.');
            return $this->redirect("/inventory/warehouses/{$id}");
        } catch (\Throwable $e) {
            $this->error('Failed to save warehouse: ' . $e->getMessage());
            $this->withInput($request);
            return $this->redirect('/inventory/warehouses/create');
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        $warehouse = $this->repo->findWarehouse($id);

        if ($warehouse === null) {
            $this->error('Warehouse not found.');
            return $this->redirect('/inventory/warehouses');
        }

        try {
            $stockLevels = $this->repo->getWarehouseStock($id);
        } catch (\Throwable) {
            $stockLevels = [];
        }

        return $this->render('inventory/warehouses/show', [
            'pageTitle'   => sanitize($warehouse['name']),
            'warehouse'   => $warehouse,
            'stockLevels' => $stockLevels,
            'tab'         => 'details',
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $warehouse = $this->repo->findWarehouse($id);

        if ($warehouse === null) {
            $this->error('Warehouse not found.');
            return $this->redirect('/inventory/warehouses');
        }

        return $this->render('inventory/warehouses/edit', [
            'pageTitle' => 'Edit Warehouse',
            'warehouse' => $warehouse,
            'errors'    => session()->getFlash('errors', []),
            'old'       => session()->getFlash('_old_input', []),
        ]);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id): Response
    {
        $warehouse = $this->repo->findWarehouse($id);

        if ($warehouse === null) {
            $this->error('Warehouse not found.');
            return $this->redirect('/inventory/warehouses');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateWarehouse($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect("/inventory/warehouses/{$id}/edit");
        }

        try {
            $this->repo->updateWarehouse($id, $data);
            $this->success('Warehouse updated successfully.');
            return $this->redirect("/inventory/warehouses/{$id}");
        } catch (\Throwable $e) {
            $this->error('Failed to update warehouse: ' . $e->getMessage());
            $this->withInput($request);
            return $this->redirect("/inventory/warehouses/{$id}/edit");
        }
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $warehouse = $this->repo->findWarehouse($id);

        if ($warehouse === null) {
            $this->error('Warehouse not found.');
            return $this->redirect('/inventory/warehouses');
        }

        try {
            $this->repo->softDeleteWarehouse($id);
            $this->success('Warehouse deleted successfully.');
        } catch (\Throwable $e) {
            $this->error('Failed to delete warehouse: ' . $e->getMessage());
        }

        return $this->redirect('/inventory/warehouses');
    }

    // -------------------------------------------------------------------------
    // Stock tab (alias for show with stock tab active)
    // -------------------------------------------------------------------------

    public function stock(int $id): Response
    {
        $warehouse = $this->repo->findWarehouse($id);

        if ($warehouse === null) {
            $this->error('Warehouse not found.');
            return $this->redirect('/inventory/warehouses');
        }

        try {
            $stockLevels = $this->repo->getWarehouseStock($id);
        } catch (\Throwable) {
            $stockLevels = [];
        }

        return $this->render('inventory/warehouses/show', [
            'pageTitle'   => sanitize($warehouse['name']) . ' — Stock',
            'warehouse'   => $warehouse,
            'stockLevels' => $stockLevels,
            'tab'         => 'stock',
        ]);
    }

    // -------------------------------------------------------------------------
    // Validation helper
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>        $data
     * @return array<string, list<string>>
     */
    private function validateWarehouse(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Warehouse name is required.';
        } elseif (mb_strlen($name) > 200) {
            $errors['name'][] = 'Warehouse name must not exceed 200 characters.';
        }

        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $errors['code'][] = 'Warehouse code is required.';
        } elseif (mb_strlen($code) > 20) {
            $errors['code'][] = 'Warehouse code must not exceed 20 characters.';
        }

        $validStatuses = ['active', 'inactive'];
        $status        = (string) ($data['status'] ?? '');
        if (!in_array($status, $validStatuses, true)) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        if (!empty($data['capacity']) && !is_numeric($data['capacity'])) {
            $errors['capacity'][] = 'Capacity must be a number.';
        }

        return $errors;
    }
}
