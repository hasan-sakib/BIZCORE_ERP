<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\BaseController;
use App\Models\Department;
use App\Models\SalaryStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryStructureController extends BaseController
{
    public function index(): View
    {
        $structures = SalaryStructure::with('department')->orderBy('name')->paginate(20);
        return view('payroll.salary-structures.index', compact('structures'));
    }

    public function create(): View
    {
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        return view('payroll.salary-structures.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'basic_salary'    => ['required', 'numeric', 'min:0'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'description'     => ['nullable', 'string', 'max:500'],
        ]);

        SalaryStructure::create($data);
        $this->success('Salary structure created.');
        return redirect()->route('salary-structures.index');
    }

    public function show(int $id): View
    {
        $structure = SalaryStructure::with('components')->findOrFail($id);
        return view('payroll.salary-structures.show', compact('structure'));
    }

    public function edit(int $id): View
    {
        $structure   = SalaryStructure::findOrFail($id);
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        return view('payroll.salary-structures.edit', compact('structure', 'departments'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'basic_salary'  => ['required', 'numeric', 'min:0'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'description'   => ['nullable', 'string', 'max:500'],
        ]);

        SalaryStructure::findOrFail($id)->update($data);
        $this->success('Salary structure updated.');
        return redirect()->route('salary-structures.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        SalaryStructure::findOrFail($id)->delete();
        $this->success('Salary structure deleted.');
        return redirect()->route('salary-structures.index');
    }
}
