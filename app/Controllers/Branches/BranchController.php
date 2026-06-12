<?php

declare(strict_types=1);

namespace App\Controllers\Branches;

use App\Core\BaseController;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\BranchRepository;
use App\Services\BranchService;

final class BranchController extends BaseController
{
    public function __construct(
        private readonly BranchService    $branchService,
        private readonly BranchRepository $branchRepository,
    ) {}

    public function index(): Response
    {
        $branches = $this->branchService->getAllBranches();

        return $this->render('branches/index', [
            'pageTitle'   => 'Branches',
            'breadcrumbs' => ['Branches' => null],
            'branches'    => $branches,
        ]);
    }

    public function create(): Response
    {
        return $this->render('branches/create', [
            'pageTitle'   => 'Create Branch',
            'breadcrumbs' => ['Branches' => '/branches', 'Create' => null],
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $this->branchService->create($request->except(['_token']));
            $this->success('Branch created successfully.');
            return $this->redirect('/branches');
        } catch (ValidationException $e) {
            $this->withErrors($e->getErrors());
            $this->withInput($request);
            return $this->redirect('/branches/create');
        }
    }

    public function show(int $id): Response
    {
        try {
            $stats = $this->branchService->getWithStats($id);
        } catch (NotFoundException $e) {
            $this->error('Branch not found.');
            return $this->redirect('/branches');
        }

        $branch = $this->branchRepository->findById($id);

        return $this->render('branches/show', [
            'pageTitle'   => sanitize($branch->name),
            'breadcrumbs' => ['Branches' => '/branches', $branch->name => null],
            'branch'      => $branch,
            'stats'       => $stats,
        ]);
    }

    public function edit(int $id): Response
    {
        $branch = $this->branchRepository->findById($id);
        if ($branch === null) {
            $this->error('Branch not found.');
            return $this->redirect('/branches');
        }

        return $this->render('branches/edit', [
            'pageTitle'   => 'Edit: ' . $branch->name,
            'breadcrumbs' => ['Branches' => '/branches', $branch->name => '/branches/' . $id, 'Edit' => null],
            'branch'      => $branch,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        try {
            $this->branchService->update($id, $request->except(['_token', '_method']));
            $this->success('Branch updated successfully.');
            return $this->redirect('/branches/' . $id);
        } catch (ValidationException $e) {
            $this->withErrors($e->getErrors());
            $this->withInput($request);
            return $this->redirect('/branches/' . $id . '/edit');
        } catch (ForbiddenException $e) {
            $this->error($e->getMessage());
            return $this->redirect('/branches/' . $id);
        } catch (NotFoundException $e) {
            $this->error('Branch not found.');
            return $this->redirect('/branches');
        }
    }

    public function destroy(int $id): Response
    {
        $branch = $this->branchRepository->findById($id);
        if ($branch === null) {
            $this->error('Branch not found.');
            return $this->redirect('/branches');
        }

        if ($branch->isHeadOffice()) {
            $this->error('The head office branch cannot be deleted.');
            return $this->redirect('/branches');
        }

        try {
            $this->branchRepository->delete($id);
            $this->success('Branch deleted successfully.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        return $this->redirect('/branches');
    }

    public function switchBranch(Request $request, int $id): Response
    {
        $branch = $this->branchRepository->findById($id);
        if ($branch === null || !$branch->isActive()) {
            $this->error('Branch not found or is not active.');
            return $this->back();
        }

        session()->set('active_branch_id', $branch->id);
        session()->set('active_branch_name', $branch->name);

        $this->success('Switched to branch: ' . $branch->name . '.');

        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        return $this->redirect($referer);
    }
}
