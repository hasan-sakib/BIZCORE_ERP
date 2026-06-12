<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Entities\User;
use App\Entities\UserStatus;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;
use App\Services\RoleService;
use App\Services\UserService;

/**
 * UserController
 *
 * Thin controller: validate → delegate to UserService → return Response.
 * All business logic and database access lives in the service / repository layers.
 */
final class UserController
{
    public function __construct(
        private readonly UserService    $userService,
        private readonly RoleService    $roleService,
        private readonly UserRepository $userRepository,
    ) {}

    // -------------------------------------------------------------------------
    // Index — paginated list
    // -------------------------------------------------------------------------

    /**
     * Display a paginated, searchable list of users.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'role_id', 'branch_id']);
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 20);

        $result = $this->userService->getAllWithFilters($filters, $page, $perPage);
        $roles  = $this->roleService->getAllRoles();

        if ($request->wantsJson()) {
            return Response::json($result->toArray());
        }

        return Response::make($this->renderView('users/index', [
            'title'   => 'Users',
            'result'  => $result,
            'roles'   => $roles,
            'filters' => $filters,
        ]));
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    /**
     * Display the user creation form.
     */
    public function create(): Response
    {
        $roles = $this->roleService->getAllRoles();

        return Response::make($this->renderView('users/create', [
            'title'     => 'Create User',
            'roles'     => $roles,
            'errors'    => $this->flashGet('errors', []),
            'old'       => $this->flashGet('old', []),
            'csrfToken' => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    /**
     * Validate and persist a new user account.
     */
    public function store(Request $request): Response
    {
        if (!$this->verifyCsrfToken($request)) {
            return $this->csrfError();
        }

        try {
            $user = $this->userService->create($request->all());
        } catch (ValidationException $e) {
            $this->flashSet('errors', $e->getErrors());
            $this->flashSet('old', $request->except(['password', 'password_confirmation']));
            return Response::redirect('/users/create');
        }

        $this->flashSet('success', "User '{$user->name}' was created successfully.");
        return Response::redirect('/users/' . $user->id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Display a single user's profile.
     */
    public function show(int $id): Response
    {
        try {
            $user = $this->findUserOrFail($id);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        return Response::make($this->renderView('users/show', [
            'title' => $user->name,
            'user'  => $user,
        ]));
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    /**
     * Display the user-edit form.
     */
    public function edit(int $id): Response
    {
        try {
            $user = $this->findUserOrFail($id);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        $roles = $this->roleService->getAllRoles();

        return Response::make($this->renderView('users/edit', [
            'title'     => 'Edit — ' . $user->name,
            'user'      => $user,
            'roles'     => $roles,
            'errors'    => $this->flashGet('errors', []),
            'old'       => $this->flashGet('old', $user->toArray()),
            'csrfToken' => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    /**
     * Validate and apply updates to an existing user.
     */
    public function update(Request $request, int $id): Response
    {
        if (!$this->verifyCsrfToken($request)) {
            return $this->csrfError();
        }

        try {
            $data = $request->except(['_token', '_method', 'password', 'password_confirmation']);
            $user = $this->userService->update($id, $data);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ValidationException $e) {
            $this->flashSet('errors', $e->getErrors());
            $this->flashSet('old', $request->except(['password', 'password_confirmation']));
            return Response::redirect("/users/{$id}/edit");
        }

        $this->flashSet('success', "User '{$user->name}' was updated successfully.");
        return Response::redirect('/users/' . $user->id);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    /**
     * Soft-delete a user account.
     */
    public function destroy(int $id): Response
    {
        try {
            $this->userService->delete($id);
        } catch (NotFoundException $e) {
            return Response::json(['message' => $e->getMessage()], 404);
        } catch (ForbiddenException $e) {
            $this->flashSet('errors', ['form' => [$e->getMessage()]]);
            return Response::redirect('/users');
        }

        $this->flashSet('success', 'User was deleted successfully.');
        return Response::redirect('/users');
    }

    // -------------------------------------------------------------------------
    // Update status
    // -------------------------------------------------------------------------

    /**
     * Change the status (active / inactive / locked) of a user.
     */
    public function updateStatus(Request $request, int $id): Response
    {
        $rawStatus = (string) $request->input('status', '');
        $status    = UserStatus::tryFrom($rawStatus);

        if ($status === null) {
            $error = ['status' => ['Invalid status value.']];
            if ($request->wantsJson()) {
                return Response::json(['errors' => $error], 422);
            }
            $this->flashSet('errors', $error);
            return Response::redirect("/users/{$id}");
        }

        try {
            $this->userService->updateStatus($id, $status);
        } catch (NotFoundException $e) {
            if ($request->wantsJson()) {
                return Response::json(['message' => $e->getMessage()], 404);
            }
            return $this->notFound($e->getMessage());
        }

        if ($request->wantsJson()) {
            return Response::json(['message' => 'Status updated.']);
        }

        $this->flashSet('success', 'User status was updated.');
        return Response::redirect("/users/{$id}");
    }

    // -------------------------------------------------------------------------
    // Login history
    // -------------------------------------------------------------------------

    /**
     * Display paginated login history for a user.
     */
    public function loginHistory(int $id): Response
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $user   = $this->findUserOrFail($id);
            $result = $this->userService->getLoginHistory($id, $page);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        return Response::make($this->renderView('users/login-history', [
            'title'  => 'Login History — ' . $user->name,
            'user'   => $user,
            'result' => $result,
        ]));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @throws NotFoundException
     */
    private function findUserOrFail(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            throw new NotFoundException('User', $id);
        }

        return $user;
    }

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

    private function csrfError(): Response
    {
        $this->flashSet('errors', ['form' => ['Invalid security token. Please refresh and try again.']]);
        return Response::redirect('/users');
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
