<?php

declare(strict_types=1);

namespace App\Http\Controllers\HR;

use App\Http\Controllers\BaseController;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends BaseController
{
    public function index(): View
    {
        $departments = Department::withCount('employees')->orderBy('name')->paginate(20);
        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        $parents = Department::where('status', 'active')->orderBy('name')->get();
        return view('departments.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150', 'unique:departments,name'],
            'code'        => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'status'      => ['nullable', 'string'],
        ]);

        Department::create($data);
        $this->success('Department created.');
        return redirect()->route('departments.index');
    }

    public function show(int $id): View
    {
        $department = Department::with(['employees', 'designations'])->findOrFail($id);
        return view('departments.show', compact('department'));
    }

    public function edit(int $id): View
    {
        $department = Department::findOrFail($id);
        $parents    = Department::where('status', 'active')->where('id', '!=', $id)->orderBy('name')->get();
        return view('departments.edit', compact('department', 'parents'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150', 'unique:departments,name,' . $id],
            'code'        => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'status'      => ['nullable', 'string'],
        ]);

        Department::findOrFail($id)->update($data);
        $this->success('Department updated.');
        return redirect()->route('departments.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $dept = Department::findOrFail($id);

        if ($dept->employees()->exists()) {
            $this->error('Cannot delete department with active employees.');
            return back();
        }

        $dept->delete();
        $this->success('Department deleted.');
        return redirect()->route('departments.index');
    }
}
