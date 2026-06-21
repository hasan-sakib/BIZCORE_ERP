<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\BaseController;
use App\Models\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeductionController extends BaseController
{
    public function index(): View
    {
        $deductions = SalaryComponent::where('type', 'deduction')->orderBy('name')->paginate(20);
        return view('payroll.deductions.index', compact('deductions'));
    }

    public function create(): View
    {
        return view('payroll.deductions.create');
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

        SalaryComponent::create(array_merge($data, ['type' => 'deduction']));
        $this->success('Deduction created.');
        return redirect()->route('deductions.index');
    }

    public function edit(int $id): View
    {
        $deduction = SalaryComponent::where('type', 'deduction')->findOrFail($id);
        return view('payroll.deductions.edit', compact('deduction'));
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

        SalaryComponent::where('type', 'deduction')->findOrFail($id)->update($data);
        $this->success('Deduction updated.');
        return redirect()->route('deductions.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        SalaryComponent::where('type', 'deduction')->findOrFail($id)->delete();
        $this->success('Deduction deleted.');
        return redirect()->route('deductions.index');
    }
}
