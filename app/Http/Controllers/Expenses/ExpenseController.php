<?php

declare(strict_types=1);

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\BaseController;
use App\Models\Branch;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenseController extends BaseController
{
    public function __construct(private readonly ExpenseService $expenseService) {}

    public function index(Request $request): View
    {
        $expenses   = $this->expenseService->paginate($request->all());
        $categories = ExpenseCategory::where('status', 'active')->orderBy('name')->get();
        $branches   = Branch::where('status', 'active')->orderBy('name')->get();
        return view('expenses.index', compact('expenses', 'categories', 'branches'));
    }

    public function create(): View
    {
        $categories = ExpenseCategory::where('status', 'active')->orderBy('name')->get();
        $branches   = Branch::where('status', 'active')->orderBy('name')->get();
        return view('expenses.create', compact('categories', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'               => ['required', 'string', 'max:200'],
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'branch_id'           => ['required', 'integer', 'exists:branches,id'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'vat_rate'            => ['nullable', 'numeric', 'min:0', 'max:100'],
            'expense_date'        => ['required', 'date'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'receipt'             => ['nullable', 'file', 'max:5120'],
        ]);

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('expenses', 'private');
        }

        $expense = $this->expenseService->create(array_merge($data, ['created_by' => Auth::id()]));
        $this->success('Expense submitted.');
        return redirect()->route('expenses.show', $expense->id);
    }

    public function show(int $id): View
    {
        $expense = \App\Models\Expense::with(['category', 'branch', 'createdBy', 'approvedBy'])->findOrFail($id);
        return view('expenses.show', compact('expense'));
    }

    public function edit(int $id): View
    {
        $expense    = \App\Models\Expense::findOrFail($id);
        $categories = ExpenseCategory::where('status', 'active')->orderBy('name')->get();
        $branches   = Branch::where('status', 'active')->orderBy('name')->get();
        return view('expenses.edit', compact('expense', 'categories', 'branches'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'title'               => ['required', 'string', 'max:200'],
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'branch_id'           => ['required', 'integer', 'exists:branches,id'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'vat_rate'            => ['nullable', 'numeric', 'min:0', 'max:100'],
            'expense_date'        => ['required', 'date'],
            'description'         => ['nullable', 'string', 'max:1000'],
        ]);

        $this->expenseService->update($id, $data);
        $this->success('Expense updated.');
        return redirect()->route('expenses.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->expenseService->delete($id);
        $this->success('Expense deleted.');
        return redirect()->route('expenses.index');
    }

    public function approve(int $id): RedirectResponse
    {
        $this->expenseService->approve($id, Auth::id());
        $this->success('Expense approved.');
        return back();
    }

    public function reject(int $id): RedirectResponse
    {
        $this->expenseService->reject($id, Auth::id());
        $this->success('Expense rejected.');
        return back();
    }

    public function pay(int $id): RedirectResponse
    {
        $this->expenseService->markPaid($id, Auth::id());
        $this->success('Expense marked as paid.');
        return back();
    }

    public function report(Request $request): View
    {
        $expenses   = $this->expenseService->paginate(array_merge($request->all(), ['per_page' => 50]));
        $categories = ExpenseCategory::where('status', 'active')->orderBy('name')->get();
        return view('expenses.report', compact('expenses', 'categories'));
    }
}
