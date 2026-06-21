<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\BaseController;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends BaseController
{
    public function index(): View
    {
        $categories = Category::withCount('products')->orderBy('name')->paginate(20);
        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parents = Category::where('status', 'active')->whereNull('parent_id')->orderBy('name')->get();
        return view('categories.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'status'      => ['nullable', 'string'],
        ]);

        Category::create($data);
        $this->success('Category created.');
        return redirect()->route('categories.index');
    }

    public function show(int $id): View
    {
        $category = Category::with('products')->findOrFail($id);
        return view('categories.show', compact('category'));
    }

    public function edit(int $id): View
    {
        $category = Category::findOrFail($id);
        $parents  = Category::where('status', 'active')->whereNull('parent_id')->where('id', '!=', $id)->orderBy('name')->get();
        return view('categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150', 'unique:categories,name,' . $id],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'status'      => ['nullable', 'string'],
        ]);

        Category::findOrFail($id)->update($data);
        $this->success('Category updated.');
        return redirect()->route('categories.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            $this->error('Cannot delete category with associated products.');
            return back();
        }

        $category->delete();
        $this->success('Category deleted.');
        return redirect()->route('categories.index');
    }
}
