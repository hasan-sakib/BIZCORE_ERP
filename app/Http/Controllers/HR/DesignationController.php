<?php

declare(strict_types=1);

namespace App\Http\Controllers\HR;

use App\Http\Controllers\BaseController;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignationController extends BaseController
{
    public function index(): View
    {
        $designations = Designation::with('department')->orderBy('title')->paginate(20);
        return view('designations.index', compact('designations'));
    }

    public function create(): View
    {
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        return view('designations.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'grade'         => ['nullable', 'string', 'max:20'],
            'description'   => ['nullable', 'string', 'max:500'],
            'status'        => ['nullable', 'string'],
        ]);

        Designation::create($data);
        $this->success('Designation created.');
        return redirect()->route('designations.index');
    }

    public function show(int $id): View
    {
        $designation = Designation::with('department')->findOrFail($id);
        return view('designations.show', compact('designation'));
    }

    public function edit(int $id): View
    {
        $designation = Designation::findOrFail($id);
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        return view('designations.edit', compact('designation', 'departments'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:150'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'grade'         => ['nullable', 'string', 'max:20'],
            'description'   => ['nullable', 'string', 'max:500'],
            'status'        => ['nullable', 'string'],
        ]);

        Designation::findOrFail($id)->update($data);
        $this->success('Designation updated.');
        return redirect()->route('designations.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $desig = Designation::findOrFail($id);

        if ($desig->employees()->exists()) {
            $this->error('Cannot delete designation with assigned employees.');
            return back();
        }

        $desig->delete();
        $this->success('Designation deleted.');
        return redirect()->route('designations.index');
    }
}
