<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Services\RoleService;

/**
 * RoleController
 *
 * Thin controller for RBAC role management.
 * Validate → RoleService → Response. No business logic here.
 */
final class RoleController
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    /**
     * List all roles.
     */
    public function index(): Response
    {
        $roles = $this->roleService->getAllRoles();

        return Response::make($this->renderView('roles/index', [
            'title' => 'Roles & Permissions',
            'roles' => $roles,
        ]));
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    /**
     * Display the role creation form.
     */
    public function create(): Response
    {
        return Response::make($this->renderView('roles/create', [
            'title'     => 'Create Role',
            'errors'    => $this->flashGet('errors', []),
            'old'       => $this->flashGet('old', []),
            'csrfToken' => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    /**
     * Validate and persist a new role.
     */
    public function store(Request $request): Response
    {
        if (!$this->verifyCsrfToken($request)) {
            return $this->csrfError('/roles/create');
        }

        try {
            $role = $this->roleService->create($request->only(['name', 'description']));
        } catch (ValidationException $e) {
            $this->flashSet('errors', $e->getErrors());
            $this->flashSet('old', $request->only(['name', 'description']));
            return Response::redirect('/roles/create');
        }

        $this->flashSet('success', "Role '{$role->name}' was created successfully.");
        return Response::redirect('/roles/' . $role->id . '/edit');
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    /**
     * Display the role-edit form with grouped permissions.
     */
    public function edit(int $id): Response
    {
        try {
            $data = $this->roleService->getWithPermissions($id);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        return Response::make($this->renderView('roles/edit', [
            'title'          => 'Edit Role — ' . $data['role']->name,
            'role'           => $data['role'],
            'allPermissions' => $data['allPermissions'],
            'errors'         => $this->flashGet('errors', []),
            'old'            => $this->flashGet('old', $data['role']->toArray()),
            'csrfToken'      => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    /**
     * Validate and apply updates to an existing role's metadata.
     */
    public function update(Request $request, int $id): Response
    {
        if (!$this->verifyCsrfToken($request)) {
            return $this->csrfError("/roles/{$id}/edit");
        }

        try {
            $role = $this->roleService->update($id, $request->only(['name', 'description']));
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->flashSet('errors', ['form' => [$e->getMessage()]]);
            return Response::redirect("/roles/{$id}/edit");
        } catch (ValidationException $e) {
            $this->flashSet('errors', $e->getErrors());
            $this->flashSet('old', $request->only(['name', 'description']));
            return Response::redirect("/roles/{$id}/edit");
        }

        $this->flashSet('success', "Role '{$role->name}' was updated successfully.");
        return Response::redirect("/roles/{$id}/edit");
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    /**
     * Delete a non-system role.
     */
    public function destroy(int $id): Response
    {
        try {
            $this->roleService->delete($id);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->flashSet('errors', ['form' => [$e->getMessage()]]);
            return Response::redirect('/roles');
        }

        $this->flashSet('success', 'Role was deleted successfully.');
        return Response::redirect('/roles');
    }

    // -------------------------------------------------------------------------
    // Sync permissions
    // -------------------------------------------------------------------------

    /**
     * Replace the full permission set for a role.
     *
     * Accepts a JSON body `{"permissions": [1, 3, 7]}` or a form POST
     * with `permissions[]` checkboxes.
     */
    public function syncPermissions(Request $request, int $id): Response
    {
        if (!$this->verifyCsrfToken($request)) {
            return $this->csrfError("/roles/{$id}/edit");
        }

        // Accept either JSON body or form-encoded array.
        $raw = $request->input('permissions', []);

        if (!is_array($raw)) {
            $raw = [];
        }

        $permissionIds = array_map('intval', $raw);

        try {
            $this->roleService->syncPermissions($id, $permissionIds);
        } catch (NotFoundException $e) {
            if ($request->wantsJson()) {
                return Response::json(['message' => $e->getMessage()], 404);
            }
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            if ($request->wantsJson()) {
                return Response::json(['message' => $e->getMessage()], 403);
            }
            $this->flashSet('errors', ['form' => [$e->getMessage()]]);
            return Response::redirect("/roles/{$id}/edit");
        }

        if ($request->wantsJson()) {
            return Response::json(['message' => 'Permissions updated successfully.']);
        }

        $this->flashSet('success', 'Permissions were updated successfully.');
        return Response::redirect("/roles/{$id}/edit");
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function generateCsrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    private function verifyCsrfToken(Request $request): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $expected = $_SESSION['csrf_token'] ?? '';
        $provided = $request->csrfToken();

        return $expected !== '' && hash_equals($expected, $provided);
    }

    private function csrfError(string $redirectTo = '/roles'): Response
    {
        $this->flashSet('errors', ['form' => ['Invalid security token. Please refresh and try again.']]);
        return Response::redirect($redirectTo);
    }

    private function flashSet(string $key, mixed $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['_flash'][$key] = $value;
    }

    private function flashGet(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    private function notFound(string $message): Response
    {
        return Response::make(
            $this->renderView('errors/404', ['message' => $message]),
            404,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderView(string $view, array $data = []): string
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            return '';
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
