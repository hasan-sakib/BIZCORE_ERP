<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\BaseController;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends BaseController
{
    public function __construct(private readonly RoleService $roleService) {}

    public function index(): View
    {
        $roles = $this->roleService->all();
        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role = $this->roleService->create($data);
        $this->success('Role created successfully.');
        return redirect()->route('roles.show', $role->id);
    }

    public function show(int $id): View
    {
        $role = \App\Models\Role::findOrFail($id);
        return view('roles.show', compact('role'));
    }

    public function edit(int $id): View
    {
        $role = \App\Models\Role::findOrFail($id);
        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->roleService->update($id, $data);
        $this->success('Role updated successfully.');
        return redirect()->route('roles.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->roleService->delete($id);
        $this->success('Role deleted.');
        return redirect()->route('roles.index');
    }

    public function syncPermissions(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $this->roleService->syncPermissions($id, $data['permissions'] ?? []);
        $this->success('Permissions updated.');
        return back();
    }
}
