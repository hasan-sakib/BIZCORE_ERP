<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Enums\UserStatus;
use App\Http\Controllers\BaseController;
use App\Models\Branch;
use App\Models\Role;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends BaseController
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): View
    {
        $users = $this->userService->paginate($request->all());
        $roles    = Role::orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        return view('users.index', compact('users', 'roles', 'branches'));
    }

    public function create(): View
    {
        $roles    = Role::where('slug', '!=', 'super_admin')->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        return view('users.create', compact('roles', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'confirmed', 'min:8'],
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'phone'     => ['nullable', 'string', 'max:30'],
            'status'    => ['nullable', 'string'],
        ]);

        $this->userService->create($data);
        $this->success('User created successfully.');
        return redirect()->route('users.index');
    }

    public function show(int $id): View
    {
        $user = \App\Models\User::with(['role', 'branch', 'employee'])->findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function edit(int $id): View
    {
        $user     = \App\Models\User::findOrFail($id);
        $roles    = Role::where('slug', '!=', 'super_admin')->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        return view('users.edit', compact('user', 'roles', 'branches'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'unique:users,email,' . $id],
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'phone'     => ['nullable', 'string', 'max:30'],
        ]);

        $this->userService->update($id, $data);
        $this->success('User updated successfully.');
        return redirect()->route('users.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->userService->delete($id);
        $this->success('User deleted successfully.');
        return redirect()->route('users.index');
    }

    public function activate(int $id): RedirectResponse
    {
        $this->userService->updateStatus($id, UserStatus::Active);
        $this->success('User activated.');
        return back();
    }

    public function deactivate(int $id): RedirectResponse
    {
        $this->userService->updateStatus($id, UserStatus::Inactive);
        $this->success('User deactivated.');
        return back();
    }

    public function lock(int $id): RedirectResponse
    {
        $this->userService->updateStatus($id, UserStatus::Locked);
        $this->success('User locked.');
        return back();
    }
}
