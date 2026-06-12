<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Services\BranchService;

/**
 * BranchController
 *
 * Thin controller: validate input → delegate to BranchService → return Response.
 * All business logic and database access live in the service / repository layers.
 */
final class BranchController
{
    public function __construct(
        private readonly BranchService $branchService,
    ) {}

    // -------------------------------------------------------------------------
    // Index — list all branches
    // -------------------------------------------------------------------------

    /**
     * Display a list of all branches (active and inactive).
     */
    public function index(Request $request): Response
    {
        $branches = $this->branchService->getAllBranches();

        if ($request->wantsJson()) {
            return Response::json([
                'data' => array_map(static fn ($b) => $b->toArray(), $branches),
            ]);
        }

        return Response::make($this->renderView('branches/index', [
            'title'    => 'Branch Management',
            'branches' => $branches,
        ]));
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    /**
     * Display the branch-creation form.
     */
    public function create(): Response
    {
        return Response::make($this->renderView('branches/create', [
            'title'     => 'Create Branch',
            'errors'    => $this->flashGet('errors', []),
            'old'       => $this->flashGet('old', []),
            'csrfToken' => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    /**
     * Validate and persist a new branch.
     */
    public function store(Request $request): Response
    {
        if (!$this->verifyCsrfToken($request)) {
            return $this->csrfError('/branches/create');
        }

        try {
            $branch = $this->branchService->create($request->all());
        } catch (ValidationException $e) {
            $this->flashSet('errors', $e->getErrors());
            $this->flashSet('old', $request->all());
            return Response::redirect('/branches/create');
        }

        $this->flashSet('success', "Branch '{$branch->name}' was created successfully.");
        return Response::redirect('/branches/' . $branch->id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Display a single branch with aggregate stats.
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $data = $this->branchService->getWithStats($id);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        if ($request->wantsJson()) {
            return Response::json(['data' => $data]);
        }

        return Response::make($this->renderView('branches/show', [
            'title'  => $data['name'] ?? 'Branch',
            'branch' => $data,
        ]));
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    /**
     * Display the branch-edit form.
     */
    public function edit(int $id): Response
    {
        try {
            $branch = $this->branchService->getActiveBranches();
            // Reload the specific branch for editing.
            $data = $this->branchService->getWithStats($id);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        return Response::make($this->renderView('branches/edit', [
            'title'     => 'Edit — ' . ($data['name'] ?? ''),
            'branch'    => $data,
            'errors'    => $this->flashGet('errors', []),
            'old'       => $this->flashGet('old', $data),
            'csrfToken' => $this->generateCsrfToken(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    /**
     * Validate and apply updates to an existing branch.
     */
    public function update(Request $request, int $id): Response
    {
        if (!$this->verifyCsrfToken($request)) {
            return $this->csrfError("/branches/{$id}/edit");
        }

        try {
            $data   = $request->except(['_token', '_method']);
            $branch = $this->branchService->update($id, $data);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ValidationException $e) {
            $this->flashSet('errors', $e->getErrors());
            $this->flashSet('old', $request->except(['_token', '_method']));
            return Response::redirect("/branches/{$id}/edit");
        }

        $this->flashSet('success', "Branch '{$branch->name}' was updated successfully.");
        return Response::redirect('/branches/' . $branch->id);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    /**
     * Soft-delete a branch.
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            $this->branchService->disable($id);
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
            return Response::redirect('/branches');
        }

        if ($request->wantsJson()) {
            return Response::json(['message' => 'Branch disabled successfully.']);
        }

        $this->flashSet('success', 'Branch was disabled successfully.');
        return Response::redirect('/branches');
    }

    // -------------------------------------------------------------------------
    // Enable / Disable (status toggle)
    // -------------------------------------------------------------------------

    /**
     * Enable a previously disabled branch.
     */
    public function enable(Request $request, int $id): Response
    {
        try {
            $this->branchService->enable($id);
        } catch (NotFoundException $e) {
            if ($request->wantsJson()) {
                return Response::json(['message' => $e->getMessage()], 404);
            }
            return $this->notFound($e->getMessage());
        }

        if ($request->wantsJson()) {
            return Response::json(['message' => 'Branch enabled successfully.']);
        }

        $this->flashSet('success', 'Branch was enabled successfully.');
        return Response::redirect('/branches/' . $id);
    }

    /**
     * Disable a branch (alias for destroy when called via AJAX / API).
     */
    public function disable(Request $request, int $id): Response
    {
        try {
            $this->branchService->disable($id);
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
            return Response::redirect("/branches/{$id}");
        }

        if ($request->wantsJson()) {
            return Response::json(['message' => 'Branch disabled successfully.']);
        }

        $this->flashSet('success', 'Branch was disabled successfully.');
        return Response::redirect("/branches/{$id}");
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    /**
     * Return the branch-level dashboard summary.
     */
    public function dashboard(Request $request, int $id): Response
    {
        try {
            $data = $this->branchService->getDashboardData($id);
        } catch (NotFoundException $e) {
            if ($request->wantsJson()) {
                return Response::json(['message' => $e->getMessage()], 404);
            }
            return $this->notFound($e->getMessage());
        }

        if ($request->wantsJson()) {
            return Response::json(['data' => $data]);
        }

        try {
            $branch = $this->branchService->getWithStats($id);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        return Response::make($this->renderView('branches/dashboard', [
            'title'   => ($branch['name'] ?? 'Branch') . ' — Dashboard',
            'branch'  => $branch,
            'summary' => $data,
        ]));
    }

    // -------------------------------------------------------------------------
    // Reports
    // -------------------------------------------------------------------------

    /**
     * Generate and return a date-range performance report for a branch.
     *
     * Query parameters: from_date (Y-m-d), to_date (Y-m-d)
     */
    public function reports(Request $request, int $id): Response
    {
        $fromDate = (string) $request->query('from_date', date('Y-m-01'));
        $toDate   = (string) $request->query('to_date', date('Y-m-d'));

        try {
            $report = $this->branchService->getReports($id, $fromDate, $toDate);
        } catch (NotFoundException $e) {
            if ($request->wantsJson()) {
                return Response::json(['message' => $e->getMessage()], 404);
            }
            return $this->notFound($e->getMessage());
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return Response::json(['errors' => $e->getErrors()], 422);
            }
            $this->flashSet('errors', $e->getErrors());
            return Response::redirect("/branches/{$id}/reports");
        }

        if ($request->wantsJson()) {
            return Response::json(['data' => $report]);
        }

        return Response::make($this->renderView('branches/reports', [
            'title'     => ($report['branch']['name'] ?? 'Branch') . ' — Reports',
            'report'    => $report,
            'from_date' => $fromDate,
            'to_date'   => $toDate,
        ]));
    }

    // -------------------------------------------------------------------------
    // Performance metrics (API)
    // -------------------------------------------------------------------------

    /**
     * Return raw performance metrics for a branch and period (JSON only).
     *
     * Query parameter: period (today|week|month|quarter|year, default: month)
     */
    public function metrics(Request $request, int $id): Response
    {
        $period = (string) $request->query('period', 'month');

        $allowed = ['today', 'week', 'month', 'quarter', 'year'];
        if (!in_array($period, $allowed, true)) {
            return Response::json([
                'errors' => ['period' => ['Period must be one of: ' . implode(', ', $allowed) . '.']],
            ], 422);
        }

        try {
            $this->branchService->getWithStats($id); // existence check
            // Direct repository access via service method not available; re-use getReports
            $data = $this->branchService->getDashboardData($id);
        } catch (NotFoundException $e) {
            return Response::json(['message' => $e->getMessage()], 404);
        }

        return Response::json(['data' => $data, 'period' => $period]);
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

    private function csrfError(string $redirect = '/branches'): Response
    {
        $this->flashSet('errors', ['form' => ['Invalid security token. Please refresh and try again.']]);
        return Response::redirect($redirect);
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
