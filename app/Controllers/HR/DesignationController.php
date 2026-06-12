<?php

declare(strict_types=1);

namespace App\Controllers\HR;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\HRRepository;

/**
 * DesignationController
 *
 * Full CRUD for HR designations.
 */
final class DesignationController extends BaseController
{
    public function __construct(
        private readonly HRRepository $hr,
    ) {}

    // -------------------------------------------------------------------------
    // List
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $search       = (string) $request->query('search', '');
        $deptId       = (int)    $request->query('department_id', 0);
        $designations = $this->hr->allDesignations($search, $deptId);
        $departments  = $this->hr->allDepartments();

        return $this->render('designations/index', [
            'pageTitle'    => 'Designations',
            'breadcrumbs'  => ['HR' => null, 'Designations' => null],
            'designations' => $designations,
            'departments'  => $departments,
            'filters'      => ['search' => $search, 'department_id' => $deptId],
            'headerActions' => '<a href="/hr/designations/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Designation</a>',
        ]);
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        $departments = $this->hr->allDepartments();

        return $this->render('designations/create', [
            'pageTitle'   => 'New Designation',
            'breadcrumbs' => ['HR' => null, 'Designations' => '/hr/designations', 'New' => null],
            'departments' => $departments,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateDesignation($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/hr/designations/create');
        }

        $id = $this->hr->createDesignation($data);
        $this->success('Designation created successfully.');
        return $this->redirect('/hr/designations/' . $id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        $designation = $this->hr->findDesignation($id);
        if ($designation === null) {
            $this->error('Designation not found.');
            return $this->redirect('/hr/designations');
        }

        return $this->render('designations/show', [
            'pageTitle'   => sanitize($designation['name']),
            'breadcrumbs' => ['HR' => null, 'Designations' => '/hr/designations', $designation['name'] => null],
            'designation' => $designation,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $designation = $this->hr->findDesignation($id);
        if ($designation === null) {
            $this->error('Designation not found.');
            return $this->redirect('/hr/designations');
        }

        $departments = $this->hr->allDepartments();

        return $this->render('designations/edit', [
            'pageTitle'   => 'Edit Designation',
            'breadcrumbs' => ['HR' => null, 'Designations' => '/hr/designations', $designation['name'] => '/hr/designations/' . $id, 'Edit' => null],
            'designation' => $designation,
            'departments' => $departments,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $designation = $this->hr->findDesignation($id);
        if ($designation === null) {
            $this->error('Designation not found.');
            return $this->redirect('/hr/designations');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateDesignation($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/hr/designations/' . $id . '/edit');
        }

        $this->hr->updateDesignation($id, $data);
        $this->success('Designation updated successfully.');
        return $this->redirect('/hr/designations/' . $id);
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $designation = $this->hr->findDesignation($id);
        if ($designation === null) {
            $this->error('Designation not found.');
            return $this->redirect('/hr/designations');
        }

        $this->hr->softDeleteDesignation($id);
        $this->success('Designation deleted.');
        return $this->redirect('/hr/designations');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateDesignation(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Designation name is required.';
        } elseif (mb_strlen((string) $data['name']) > 150) {
            $errors['name'] = 'Designation name must not exceed 150 characters.';
        }

        if (empty($data['department_id'])) {
            $errors['department_id'] = 'Department is required.';
        }

        if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'], true)) {
            $errors['status'] = 'Status must be active or inactive.';
        }

        return $errors;
    }
}
