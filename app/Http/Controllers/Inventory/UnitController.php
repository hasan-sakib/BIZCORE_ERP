<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\BaseController;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends BaseController
{
    public function index(): View
    {
        $units = Unit::withCount('products')->orderBy('name')->paginate(20);
        return view('units.index', compact('units'));
    }

    public function create(): View
    {
        return view('units.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:50', 'unique:units,name'],
            'abbreviation' => ['required', 'string', 'max:10'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        Unit::create($data);
        $this->success('Unit created.');
        return redirect()->route('units.index');
    }

    public function edit(int $id): View
    {
        $unit = Unit::findOrFail($id);
        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:50', 'unique:units,name,' . $id],
            'abbreviation' => ['required', 'string', 'max:10'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        Unit::findOrFail($id)->update($data);
        $this->success('Unit updated.');
        return redirect()->route('units.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $unit = Unit::findOrFail($id);

        if ($unit->products()->exists()) {
            $this->error('Cannot delete unit with associated products.');
            return back();
        }

        $unit->delete();
        $this->success('Unit deleted.');
        return redirect()->route('units.index');
    }
}
