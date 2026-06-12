<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Entities\Employee;
use App\Http\Request;
use App\Services\EmployeeService;

class EmployeeController extends BaseController
{
    public function __construct(
        private readonly EmployeeService $employeeService,
        private readonly Database $db
    ) {}

    public function index(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;
        $search   = $request->query('search');
        $deptId   = $request->query('department_id');
        $status   = $request->query('status', 'active');
        $page     = max(1, (int)$request->query('page', 1));

        $filters = array_filter(compact('search', 'deptId', 'status'));
        $result  = $this->employeeService->paginate($branchId, $page, 20, $filters);

        $departments = $this->db->fetchAll(
            "SELECT id, name FROM departments WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );

        $this->view('employees/index', [
            'pageTitle'   => 'Employees',
            'breadcrumbs' => ['Employees' => null],
            'employees'   => array_map(fn($e) => Employee::fromArray($e), $result['data']),
            'pagination'  => paginate($result['total'], $page, 20),
            'departments' => $departments,
            'currentUser' => $user,
            'filters'     => $filters,
            'headerActions' => '<a href="/employees/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Employee</a>',
        ]);
    }

    public function create(Request $request): void
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;

        $departments  = $this->db->fetchAll(
            "SELECT id, name FROM departments WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );
        $designations = $this->db->fetchAll(
            "SELECT id, name FROM designations WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );

        $this->view('employees/create', [
            'pageTitle'    => 'Add Employee',
            'breadcrumbs'  => ['Employees' => '/employees', 'Add' => null],
            'departments'  => $departments,
            'designations' => $designations,
            'currentUser'  => $user,
        ]);
    }

    public function store(Request $request): void
    {
        $data = $request->all();
        $user = $this->currentUser();

        $required = ['first_name', 'last_name', 'date_of_joining', 'department_id', 'designation_id'];
        $errors   = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucwords(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!empty($errors)) {
            $this->withErrors($errors)->withInput($data)->back();
        }

        try {
            $employee = $this->employeeService->create(array_merge($data, [
                'branch_id'  => $user?->branchId ?? 0,
                'created_by' => $user?->id ?? 0,
            ]));
            $this->success('Employee created successfully.')->redirect('/employees/' . $employee->id);
        } catch (\Throwable $e) {
            $this->error($e->getMessage())->withInput($data)->back();
        }
    }

    public function show(Request $request, int $id): void
    {
        $employee = $this->employeeService->findById($id);
        if (!$employee) {
            $this->error('Employee not found.')->redirect('/employees');
            return;
        }

        $salaryStructure = $this->db->fetchOne(
            "SELECT * FROM salary_structures WHERE employee_id = ? AND is_active = 1",
            [$id]
        );
        if ($salaryStructure) {
            $salaryStructure['components'] = $this->db->fetchAll(
                "SELECT * FROM salary_components WHERE salary_structure_id = ? AND is_active = 1 ORDER BY component_type",
                [$salaryStructure['id']]
            );
        }

        $recentAttendance = $this->db->fetchAll(
            "SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 30",
            [$id]
        );

        $this->view('employees/show', [
            'pageTitle'       => $employee->getFullName(),
            'breadcrumbs'     => ['Employees' => '/employees', $employee->getFullName() => null],
            'employee'        => $employee,
            'salaryStructure' => $salaryStructure,
            'recentAttendance'=> $recentAttendance,
            'currentUser'     => $this->currentUser(),
        ]);
    }

    public function edit(Request $request, int $id): void
    {
        $employee = $this->employeeService->findById($id);
        if (!$employee) {
            $this->error('Employee not found.')->redirect('/employees');
            return;
        }

        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;

        $departments  = $this->db->fetchAll(
            "SELECT id, name FROM departments WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );
        $designations = $this->db->fetchAll(
            "SELECT id, name FROM designations WHERE branch_id = ? AND is_active = 1 ORDER BY name",
            [$branchId]
        );

        $this->view('employees/edit', [
            'pageTitle'    => 'Edit Employee',
            'breadcrumbs'  => ['Employees' => '/employees', $employee->getFullName() => "/employees/{$id}", 'Edit' => null],
            'employee'     => $employee,
            'departments'  => $departments,
            'designations' => $designations,
            'currentUser'  => $user,
        ]);
    }

    public function update(Request $request, int $id): void
    {
        $employee = $this->employeeService->findById($id);
        if (!$employee) {
            $this->error('Employee not found.')->redirect('/employees');
            return;
        }

        try {
            $this->employeeService->update($id, $request->all());
            $this->success('Employee updated successfully.')->redirect("/employees/{$id}");
        } catch (\Throwable $e) {
            $this->error($e->getMessage())->back();
        }
    }

    public function destroy(Request $request, int $id): void
    {
        $employee = $this->employeeService->findById($id);
        if (!$employee) {
            $this->error('Employee not found.')->redirect('/employees');
            return;
        }

        $this->employeeService->delete($id);
        $this->success('Employee deleted.')->redirect('/employees');
    }
}
