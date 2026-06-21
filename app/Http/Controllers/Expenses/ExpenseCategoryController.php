<?php

declare(strict_types=1);

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\BaseController;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends BaseController
{
    public function index(): View
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->paginate(20);
        return view('expenses.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('expenses.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:expense_categories,name'],
            'color'       => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['nullable', 'string'],
        ]);

        ExpenseCategory::create($data);
        $this->success('Category created.');
        return redirect()->route('expense-categories.index');
    }

    public function edit(int $id): View
    {
        $category = ExpenseCategory::findOrFail($id);
        return view('expenses.categories.edit', compact('category'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:expense_categories,name,' . $id],
            'color'       => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['nullable', 'string'],
        ]);

        ExpenseCategory::findOrFail($id)->update($data);
        $this->success('Category updated.');
        return redirect()->route('expense-categories.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $cat = ExpenseCategory::findOrFail($id);

        if ($cat->expenses()->exists()) {
            $this->error('Cannot delete category with associated expenses.');
            return back();
        }

        $cat->delete();
        $this->success('Category deleted.');
        return redirect()->route('expense-categories.index');
    }
}
