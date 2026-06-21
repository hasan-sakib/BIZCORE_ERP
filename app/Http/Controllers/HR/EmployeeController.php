<?php

declare(strict_types=1);

namespace App\Http\Controllers\HR;

use App\Http\Controllers\BaseController;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends BaseController
{
    public function __construct(private readonly EmployeeService $employeeService) {}

    public function index(Request $request): View
    {
        $employees   = $this->employeeService->paginate($request->all());
        $departments = Department::orderBy('name')->get();
        $branches    = Branch::where('status', 'active')->orderBy('name')->get();
        return view('employees.index', compact('employees', 'departments', 'branches'));
    }

    public function create(): View
    {
        $departments  = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('title')->get();
        $branches     = Branch::where('status', 'active')->orderBy('name')->get();
        return view('employees.create', compact('departments', 'designations', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name'       => ['required', 'string', 'max:80'],
            'last_name'        => ['required', 'string', 'max:80'],
            'email'            => ['required', 'email', 'unique:employees,email'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'department_id'    => ['required', 'integer', 'exists:departments,id'],
            'designation_id'   => ['required', 'integer', 'exists:designations,id'],
            'branch_id'        => ['required', 'integer', 'exists:branches,id'],
            'joining_date'     => ['required', 'date'],
            'employment_type'  => ['required', 'string'],
            'basic_salary'     => ['required', 'numeric', 'min:0'],
            'gender'           => ['nullable', 'string'],
            'date_of_birth'    => ['nullable', 'date'],
            'national_id'      => ['nullable', 'string', 'max:30'],
            'address'          => ['nullable', 'string', 'max:500'],
        ]);

        $employee = $this->employeeService->create($data);
        $this->success('Employee created successfully.');
        return redirect()->route('employees.show', $employee->id);
    }

    public function show(int $id): View
    {
        $employee = $this->employeeService->findWithDetails($id);
        return view('employees.show', compact('employee'));
    }

    public function edit(int $id): View
    {
        $employee     = $this->employeeService->findWithDetails($id);
        $departments  = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('title')->get();
        $branches     = Branch::where('status', 'active')->orderBy('name')->get();
        return view('employees.edit', compact('employee', 'departments', 'designations', 'branches'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'first_name'      => ['required', 'string', 'max:80'],
            'last_name'       => ['required', 'string', 'max:80'],
            'email'           => ['required', 'email', 'unique:employees,email,' . $id],
            'phone'           => ['nullable', 'string', 'max:30'],
            'department_id'   => ['required', 'integer', 'exists:departments,id'],
            'designation_id'  => ['required', 'integer', 'exists:designations,id'],
            'branch_id'       => ['required', 'integer', 'exists:branches,id'],
            'joining_date'    => ['required', 'date'],
            'employment_type' => ['required', 'string'],
            'basic_salary'    => ['required', 'numeric', 'min:0'],
            'gender'          => ['nullable', 'string'],
            'date_of_birth'   => ['nullable', 'date'],
            'national_id'     => ['nullable', 'string', 'max:30'],
            'address'         => ['nullable', 'string', 'max:500'],
        ]);

        $this->employeeService->update($id, $data);
        $this->success('Employee updated successfully.');
        return redirect()->route('employees.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->employeeService->delete($id);
        $this->success('Employee deleted.');
        return redirect()->route('employees.index');
    }

    public function timeline(int $id): View
    {
        $employee = $this->employeeService->findWithDetails($id);
        return view('employees.timeline', compact('employee'));
    }
}
