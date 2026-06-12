<?php

declare(strict_types=1);

namespace App\Controllers\HR;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\EmployeeRepository;
use App\Repositories\HRRepository;

/**
 * EmployeeController
 *
 * Full CRUD for HR employees, plus a timeline placeholder.
 */
final class EmployeeController extends BaseController
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly HRRepository       $hr,
    ) {}

    // -------------------------------------------------------------------------
    // List
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [];

        $search = (string) $request->query('search', '');
        if ($search !== '') {
            $filters['search'] = $search;
        }

        $deptId = (int) $request->query('department_id', 0);
        if ($deptId > 0) {
            $filters['department_id'] = $deptId;
        }

        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $filters['status'] = $status;
        }

        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->employees->paginateRecords($filters, $page);

        $departments = $this->hr->allDepartments();

        // Pagination array compatible with the existing pagination component.
        $pagination = $this->buildPagination($result['total'], $page, 20);

        return $this->render('employees/index', [
            'pageTitle'   => 'Employees',
            'breadcrumbs' => ['HR' => null, 'Employees' => null],
            'employees'   => $result['items'],
            'departments' => $departments,
            'pagination'  => $pagination,
            'filters'     => ['search' => $search, 'deptId' => $deptId, 'status' => $status],
            'headerActions' => '<a href="/hr/employees/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Employee</a>',
        ]);
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        $departments  = $this->hr->allDepartments();
        $designations = $this->hr->allDesignations();

        return $this->render('employees/create', [
            'pageTitle'    => 'Add Employee',
            'breadcrumbs'  => ['HR' => null, 'Employees' => '/hr/employees', 'Add' => null],
            'departments'  => $departments,
            'designations' => $designations,
            'errors'       => session()->getFlash('errors', []),
            'old'          => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateEmployee($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/hr/employees/create');
        }

        // Auto-generate employee number if not provided.
        if (empty($data['employee_number'])) {
            $data['employee_number'] = $this->employees->generateEmployeeNumber();
        }

        // Assign the creating user's branch.
        $data['branch_id'] = $this->currentUser()?->branchId ?? 1;

        $id = $this->employees->create($data);
        $this->success('Employee created successfully.');
        return $this->redirect('/hr/employees/' . $id);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(int $id): Response
    {
        $employee = $this->employees->findById($id);
        if ($employee === null) {
            $this->error('Employee not found.');
            return $this->redirect('/hr/employees');
        }

        $fullName = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));

        return $this->render('employees/show', [
            'pageTitle'   => $fullName,
            'breadcrumbs' => ['HR' => null, 'Employees' => '/hr/employees', $fullName => null],
            'employee'    => $employee,
        ]);
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $employee = $this->employees->findById($id);
        if ($employee === null) {
            $this->error('Employee not found.');
            return $this->redirect('/hr/employees');
        }

        $departments  = $this->hr->allDepartments();
        $designations = $this->hr->allDesignations();
        $fullName     = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));

        return $this->render('employees/edit', [
            'pageTitle'    => 'Edit Employee',
            'breadcrumbs'  => ['HR' => null, 'Employees' => '/hr/employees', $fullName => '/hr/employees/' . $id, 'Edit' => null],
            'employee'     => $employee,
            'departments'  => $departments,
            'designations' => $designations,
            'errors'       => session()->getFlash('errors', []),
            'old'          => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $employee = $this->employees->findById($id);
        if ($employee === null) {
            $this->error('Employee not found.');
            return $this->redirect('/hr/employees');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateEmployee($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/hr/employees/' . $id . '/edit');
        }

        $this->employees->update($id, $data);
        $this->success('Employee updated successfully.');
        return $this->redirect('/hr/employees/' . $id);
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $employee = $this->employees->findById($id);
        if ($employee === null) {
            $this->error('Employee not found.');
            return $this->redirect('/hr/employees');
        }

        $this->employees->softDelete($id);
        $this->success('Employee deleted.');
        return $this->redirect('/hr/employees');
    }

    // -------------------------------------------------------------------------
    // Timeline (placeholder)
    // -------------------------------------------------------------------------

    public function timeline(int $id): Response
    {
        $employee = $this->employees->findById($id);
        if ($employee === null) {
            $this->error('Employee not found.');
            return $this->redirect('/hr/employees');
        }

        $fullName = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));

        return $this->render('employees/timeline', [
            'pageTitle'   => $fullName . ' — Timeline',
            'breadcrumbs' => ['HR' => null, 'Employees' => '/hr/employees', $fullName => '/hr/employees/' . $id, 'Timeline' => null],
            'employee'    => $employee,
            'events'      => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateEmployee(array $data): array
    {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required.';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required.';
        }

        if (empty($data['department_id'])) {
            $errors['department_id'] = 'Department is required.';
        }

        if (empty($data['designation_id'])) {
            $errors['designation_id'] = 'Designation is required.';
        }

        if (empty($data['join_date'])) {
            $errors['join_date'] = 'Date of joining is required.';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email address is required.';
        }

        return $errors;
    }

    /**
     * Build a pagination array compatible with the shared pagination component.
     *
     * @return array<string, mixed>
     */
    private function buildPagination(int $total, int $page, int $perPage): array
    {
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $from     = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
        $to       = min($page * $perPage, $total);

        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => $lastPage,
            'from'         => $from,
            'to'           => $to,
        ];
    }
}
