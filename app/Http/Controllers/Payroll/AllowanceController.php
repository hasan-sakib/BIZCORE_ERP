<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\BaseController;
use App\Models\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AllowanceController extends BaseController
{
    public function index(): View
    {
        $allowances = SalaryComponent::where('type', 'allowance')->orderBy('name')->paginate(20);
        return view('payroll.allowances.index', compact('allowances'));
    }

    public function create(): View
    {
        return view('payroll.allowances.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'amount'       => ['required', 'numeric', 'min:0'],
            'is_taxable'   => ['boolean'],
            'is_mandatory' => ['boolean'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);

        SalaryComponent::create(array_merge($data, ['type' => 'allowance']));
        $this->success('Allowance created.');
        return redirect()->route('allowances.index');
    }

    public function edit(int $id): View
    {
        $allowance = SalaryComponent::where('type', 'allowance')->findOrFail($id);
        return view('payroll.allowances.edit', compact('allowance'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'amount'       => ['required', 'numeric', 'min:0'],
            'is_taxable'   => ['boolean'],
            'is_mandatory' => ['boolean'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);

        SalaryComponent::where('type', 'allowance')->findOrFail($id)->update($data);
        $this->success('Allowance updated.');
        return redirect()->route('allowances.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        SalaryComponent::where('type', 'allowance')->findOrFail($id)->delete();
        $this->success('Allowance deleted.');
        return redirect()->route('allowances.index');
    }
}
