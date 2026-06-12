<?php

declare(strict_types=1);

namespace App\Controllers\Inventory;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\CatalogRepository;

/**
 * UnitController
 *
 * Handles CRUD for measurement units (kg, pcs, litre, etc.).
 */
final class UnitController extends BaseController
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
        $items  = $this->catalog->allUnits($search);

        return $this->render('products/units/index', [
            'pageTitle' => 'Units of Measure',
            'items'     => $items,
            'search'    => $search,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(Request $request): Response
    {
        return $this->render('products/units/create', [
            'pageTitle' => 'Add Unit',
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
        $errors = $this->validateUnit($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/products/units/create');
        }

        $this->catalog->createUnit($data);
        $this->success('Unit created successfully.');

        return $this->redirect('/products/units');
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $item = $this->catalog->findUnit($id);

        if ($item === null) {
            $this->error('Unit not found.');
            return $this->redirect('/products/units');
        }

        return $this->render('products/units/edit', [
            'pageTitle' => 'Edit Unit',
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
        $item = $this->catalog->findUnit($id);

        if ($item === null) {
            $this->error('Unit not found.');
            return $this->redirect('/products/units');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateUnit($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect("/products/units/{$id}/edit");
        }

        $this->catalog->updateUnit($id, $data);
        $this->success('Unit updated successfully.');

        return $this->redirect('/products/units');
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $item = $this->catalog->findUnit($id);

        if ($item === null) {
            $this->error('Unit not found.');
            return $this->redirect('/products/units');
        }

        $this->catalog->softDeleteUnit($id);
        $this->success('Unit deleted successfully.');

        return $this->redirect('/products/units');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>              $data
     * @return array<string, list<string>>
     */
    private function validateUnit(array $data): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Unit name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'][] = 'Unit name must not exceed 100 characters.';
        }

        $symbol = trim((string) ($data['symbol'] ?? ''));
        if ($symbol === '') {
            $errors['symbol'][] = 'Unit symbol is required.';
        } elseif (mb_strlen($symbol) > 20) {
            $errors['symbol'][] = 'Unit symbol must not exceed 20 characters.';
        }

        $status = (string) ($data['status'] ?? '');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        return $errors;
    }
}
