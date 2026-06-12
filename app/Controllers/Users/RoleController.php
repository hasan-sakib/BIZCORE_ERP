<?php

declare(strict_types=1);

namespace App\Controllers\Users;

use App\Core\BaseController;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\RoleRepository;
use App\Services\RoleService;

final class RoleController extends BaseController
{
    public function __construct(
        private readonly RoleService    $roleService,
        private readonly RoleRepository $roleRepository,
    ) {}

    public function index(): Response
    {
        $roles = $this->roleService->getAllRoles();

        return $this->render('roles/index', [
            'pageTitle'   => 'Roles',
            'breadcrumbs' => ['Roles' => null],
            'roles'       => $roles,
        ]);
    }

    public function create(): Response
    {
        return $this->render('roles/create', [
            'pageTitle'   => 'Create Role',
            'breadcrumbs' => ['Roles' => '/roles', 'Create' => null],
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $this->roleService->create($request->except(['_token']));
            $this->success('Role created successfully.');
            return $this->redirect('/roles');
        } catch (ValidationException $e) {
            $this->withErrors($e->getErrors());
            $this->withInput($request);
            return $this->redirect('/roles/create');
        }
    }

    public function show(int $id): Response
    {
        try {
            $data = $this->roleService->getWithPermissions($id);
        } catch (NotFoundException $e) {
            $this->error('Role not found.');
            return $this->redirect('/roles');
        }

        return $this->render('roles/show', [
            'pageTitle'      => sanitize($data['role']->name),
            'breadcrumbs'    => ['Roles' => '/roles', $data['role']->name => null],
            'role'           => $data['role'],
            'allPermissions' => $data['allPermissions'],
        ]);
    }

    public function edit(int $id): Response
    {
        $role = $this->roleRepository->findById($id);
        if ($role === null) {
            $this->error('Role not found.');
            return $this->redirect('/roles');
        }

        try {
            $data = $this->roleService->getWithPermissions($id);
        } catch (NotFoundException $e) {
            $this->error('Role not found.');
            return $this->redirect('/roles');
        }

        return $this->render('roles/edit', [
            'pageTitle'      => 'Edit: ' . $role->name,
            'breadcrumbs'    => ['Roles' => '/roles', $role->name => '/roles/' . $id, 'Edit' => null],
            'role'           => $data['role'],
            'allPermissions' => $data['allPermissions'],
            'errors'         => session()->getFlash('errors', []),
            'old'            => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        try {
            $this->roleService->update($id, $request->except(['_token', '_method']));
            $this->success('Role updated successfully.');
            return $this->redirect('/roles/' . $id);
        } catch (ValidationException $e) {
            $this->withErrors($e->getErrors());
            $this->withInput($request);
            return $this->redirect('/roles/' . $id . '/edit');
        } catch (ForbiddenException $e) {
            $this->error($e->getMessage());
            return $this->redirect('/roles/' . $id);
        } catch (NotFoundException $e) {
            $this->error('Role not found.');
            return $this->redirect('/roles');
        }
    }

    public function destroy(int $id): Response
    {
        try {
            $this->roleService->delete($id);
            $this->success('Role deleted successfully.');
        } catch (ForbiddenException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        return $this->redirect('/roles');
    }

    public function assignPermissions(Request $request, int $id): Response
    {
        $permissions = $request->input('permissions', []);
        if (!is_array($permissions)) {
            $permissions = [];
        }

        try {
            $this->roleService->syncPermissions($id, $permissions);
            $this->success('Permissions updated successfully.');
        } catch (ForbiddenException $e) {
            $this->error($e->getMessage());
        } catch (NotFoundException $e) {
            $this->error('Role not found.');
            return $this->redirect('/roles');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/roles/' . $id);
    }
}
