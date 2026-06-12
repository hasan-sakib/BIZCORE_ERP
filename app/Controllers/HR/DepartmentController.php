<?php

declare(strict_types=1);

namespace App\Controllers\HR;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\HRRepository;

/**
 * DepartmentController
 *
 * Full CRUD for HR departments.
 */
final class DepartmentController extends BaseController
{
    public function __construct(
        private readonly HRRepository $hr,
    ) {}

    // -------------------------------------------------------------------------
    // List
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $search      = (string) $request->query('search', '');
        $status      = (string) $request->query('status', '');
        $departments = $this->hr->allDepartments($search);

        // Apply status filter in PHP (avoids extra SQL complexity)
        if ($status !== '') {
            $departments = array_values(
                array_filter($departments, static fn ($d) => ($d['status'] ?? '') === $status)
            );
        }

        return $this->render('departments/index', [
            'pageTitle'   => 'Departments',
            'breadcrumbs' => ['HR' => null, 'Departments' => null],
            'departments' => $departments,
            'filters'     => compact('search', 'status'),
            'headerActions' => '<a href="/hr/departments/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Department</a>',
        ]);
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        return $this->render('departments/create', [
            'pageTitle'   => 'New Department',
            'breadcrumbs' => ['HR' => null, 'Departments' => '/hr/departments', 'New' => null],
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateDepartment($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/hr/departments/create');
        }

        $id = $this->hr->createDepartment($data);
        $this->success('Department created successfully.');
        return $this->redirect('/hr/departments/' . $id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        $department = $this->hr->findDepartment($id);
        if ($department === null) {
            $this->error('Department not found.');
            return $this->redirect('/hr/departments');
        }

        return $this->render('departments/show', [
            'pageTitle'   => sanitize($department['name']),
            'breadcrumbs' => ['HR' => null, 'Departments' => '/hr/departments', $department['name'] => null],
            'department'  => $department,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $department = $this->hr->findDepartment($id);
        if ($department === null) {
            $this->error('Department not found.');
            return $this->redirect('/hr/departments');
        }

        return $this->render('departments/edit', [
            'pageTitle'   => 'Edit Department',
            'breadcrumbs' => ['HR' => null, 'Departments' => '/hr/departments', $department['name'] => '/hr/departments/' . $id, 'Edit' => null],
            'department'  => $department,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $department = $this->hr->findDepartment($id);
        if ($department === null) {
            $this->error('Department not found.');
            return $this->redirect('/hr/departments');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateDepartment($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/hr/departments/' . $id . '/edit');
        }

        $this->hr->updateDepartment($id, $data);
        $this->success('Department updated successfully.');
        return $this->redirect('/hr/departments/' . $id);
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $department = $this->hr->findDepartment($id);
        if ($department === null) {
            $this->error('Department not found.');
            return $this->redirect('/hr/departments');
        }

        $this->hr->softDeleteDepartment($id);
        $this->success('Department deleted.');
        return $this->redirect('/hr/departments');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateDepartment(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Department name is required.';
        } elseif (mb_strlen((string) $data['name']) > 150) {
            $errors['name'] = 'Department name must not exceed 150 characters.';
        }

        if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'], true)) {
            $errors['status'] = 'Status must be active or inactive.';
        }

        return $errors;
    }
}
