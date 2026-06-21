<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\BaseController;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends BaseController
{
    public function index(): View
    {
        $brands = Brand::withCount('products')->orderBy('name')->paginate(20);
        return view('brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('brands.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150', 'unique:brands,name'],
            'logo'        => ['nullable', 'image', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
            'website'     => ['nullable', 'url', 'max:255'],
            'status'      => ['nullable', 'string'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        Brand::create($data);
        $this->success('Brand created.');
        return redirect()->route('brands.index');
    }

    public function show(int $id): View
    {
        $brand = Brand::with('products')->findOrFail($id);
        return view('brands.show', compact('brand'));
    }

    public function edit(int $id): View
    {
        $brand = Brand::findOrFail($id);
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150', 'unique:brands,name,' . $id],
            'logo'        => ['nullable', 'image', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
            'website'     => ['nullable', 'url', 'max:255'],
            'status'      => ['nullable', 'string'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        Brand::findOrFail($id)->update($data);
        $this->success('Brand updated.');
        return redirect()->route('brands.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        Brand::findOrFail($id)->delete();
        $this->success('Brand deleted.');
        return redirect()->route('brands.index');
    }
}
