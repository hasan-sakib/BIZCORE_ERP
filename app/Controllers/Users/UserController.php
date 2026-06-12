<?php

declare(strict_types=1);

namespace App\Controllers\Users;

use App\Core\BaseController;
use App\Entities\UserStatus;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;
use App\Services\RoleService;
use App\Services\UserService;

final class UserController extends BaseController
{
    public function __construct(
        private readonly UserService    $userService,
        private readonly RoleService    $roleService,
        private readonly UserRepository $userRepository,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'role_id']);
        $page    = max(1, (int) $request->query('page', 1));
        $result  = $this->userService->getAllWithFilters($filters, $page, 20);
        $roles   = $this->roleService->getAllRoles();

        return $this->render('users/index', [
            'pageTitle'   => 'Users',
            'breadcrumbs' => ['Users' => null],
            'result'      => $result,
            'roles'       => $roles,
            'filters'     => $filters,
        ]);
    }

    public function create(): Response
    {
        $roles    = $this->roleService->getAllRoles();
        $branches = $this->getBranches();

        return $this->render('users/create', [
            'pageTitle'   => 'Create User',
            'breadcrumbs' => ['Users' => '/users', 'Create' => null],
            'roles'       => $roles,
            'branches'    => $branches,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $this->userService->create($request->all());
            $this->success('User created successfully.');
            return $this->redirect('/users');
        } catch (ValidationException $e) {
            $this->withErrors($e->getErrors());
            $this->withInput($request);
            return $this->redirect('/users/create');
        }
    }

    public function show(int $id): Response
    {
        $user = $this->userRepository->findById($id);
        if ($user === null) {
            $this->error('User not found.');
            return $this->redirect('/users');
        }

        return $this->render('users/show', [
            'pageTitle'   => sanitize($user->name),
            'breadcrumbs' => ['Users' => '/users', $user->name => null],
            'user'        => $user,
        ]);
    }

    public function edit(int $id): Response
    {
        $user = $this->userRepository->findById($id);
        if ($user === null) {
            $this->error('User not found.');
            return $this->redirect('/users');
        }

        $roles    = $this->roleService->getAllRoles();
        $branches = $this->getBranches();

        return $this->render('users/edit', [
            'pageTitle'   => 'Edit: ' . $user->name,
            'breadcrumbs' => ['Users' => '/users', $user->name => '/users/' . $id, 'Edit' => null],
            'user'        => $user,
            'roles'       => $roles,
            'branches'    => $branches,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        try {
            $this->userService->update($id, $request->except(['_token', '_method']));
            $this->success('User updated successfully.');
            return $this->redirect('/users/' . $id);
        } catch (ValidationException $e) {
            $this->withErrors($e->getErrors());
            $this->withInput($request);
            return $this->redirect('/users/' . $id . '/edit');
        } catch (NotFoundException $e) {
            $this->error($e->getMessage());
            return $this->redirect('/users');
        }
    }

    public function destroy(int $id): Response
    {
        try {
            $this->userService->delete($id);
            $this->success('User deleted successfully.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        return $this->redirect('/users');
    }

    public function toggleStatus(Request $request, int $id): Response
    {
        $currentUser = $this->currentUser();
        if ($currentUser !== null && $currentUser->id === $id) {
            $this->error('You cannot change the status of your own account.');
            return $this->redirect('/users/' . $id);
        }

        $status = UserStatus::tryFrom((string) $request->input('status', ''));
        if ($status === null) {
            $this->error('Invalid status value provided.');
            return $this->redirect('/users/' . $id);
        }

        try {
            $this->userService->updateStatus($id, $status);
            $this->success('User status updated to ' . $status->label() . '.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        return $this->redirect('/users/' . $id);
    }

    public function resetPassword(Request $request, int $id): Response
    {
        $user = $this->userRepository->findById($id);
        if ($user === null) {
            $this->error('User not found.');
            return $this->redirect('/users');
        }

        // Placeholder: password reset email feature coming soon.
        $this->success('Password reset link has been sent to ' . $user->email . ' (feature coming soon).');
        return $this->redirect('/users/' . $id);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getBranches(): array
    {
        $pdo = app(\PDO::class);
        return $pdo
            ->query('SELECT id, name FROM branches WHERE deleted_at IS NULL ORDER BY name ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
