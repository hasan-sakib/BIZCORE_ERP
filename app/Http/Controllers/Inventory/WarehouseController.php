<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\BaseController;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends BaseController
{
    public function index(): View
    {
        $warehouses = Warehouse::with('branch')->orderBy('name')->paginate(20);
        return view('warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        return view('warehouses.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:150'],
            'location'   => ['nullable', 'string', 'max:255'],
            'branch_id'  => ['required', 'integer', 'exists:branches,id'],
            'capacity'   => ['nullable', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'status'     => ['nullable', 'string'],
        ]);

        Warehouse::create($data);
        $this->success('Warehouse created.');
        return redirect()->route('warehouses.index');
    }

    public function show(int $id): View
    {
        $warehouse = Warehouse::with('branch')->findOrFail($id);
        return view('warehouses.show', compact('warehouse'));
    }

    public function edit(int $id): View
    {
        $warehouse = Warehouse::findOrFail($id);
        $branches  = Branch::where('status', 'active')->orderBy('name')->get();
        return view('warehouses.edit', compact('warehouse', 'branches'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:150'],
            'location'   => ['nullable', 'string', 'max:255'],
            'branch_id'  => ['required', 'integer', 'exists:branches,id'],
            'capacity'   => ['nullable', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'status'     => ['nullable', 'string'],
        ]);

        Warehouse::findOrFail($id)->update($data);
        $this->success('Warehouse updated.');
        return redirect()->route('warehouses.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        Warehouse::findOrFail($id)->delete();
        $this->success('Warehouse deleted.');
        return redirect()->route('warehouses.index');
    }
}
