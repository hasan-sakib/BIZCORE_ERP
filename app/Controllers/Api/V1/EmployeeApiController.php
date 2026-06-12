<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use App\Core\Database;
use App\Entities\Employee;
use App\Http\Request;
use App\Services\EmployeeService;
use App\Services\PayrollService;

class EmployeeApiController extends BaseApiController
{
    public function __construct(
        private readonly EmployeeService $employeeService,
        private readonly PayrollService $payrollService,
        private readonly Database $db
    ) {}

    public function index(Request $request): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));
        $branchId = $this->getBranchId($request);

        $filters = array_filter([
            'search'        => $request->query('search'),
            'department_id' => $request->query('department_id'),
            'status'        => $request->query('status'),
        ]);

        $result = $this->employeeService->paginate($branchId, $page, $perPage, $filters);

        $this->paginated([
            'data'       => array_map(fn($e) => Employee::fromArray($e)->toArray(), $result['data']),
            'pagination' => paginate($result['total'], $page, $perPage),
        ]);
    }

    public function show(Request $request, int $id): void
    {
        $employee = $this->employeeService->findById($id);
        if (!$employee) {
            $this->error('Employee not found.', 404);
        }

        $data = $employee->toArray();

        $data['salary_structure'] = $this->db->fetchOne(
            "SELECT ss.*, GROUP_CONCAT(sc.name ORDER BY sc.component_type SEPARATOR ', ') AS components
             FROM salary_structures ss
             LEFT JOIN salary_components sc ON sc.salary_structure_id = ss.id AND sc.is_active = 1
             WHERE ss.employee_id = ? AND ss.is_active = 1",
            [$id]
        );

        $data['recent_attendance'] = $this->db->fetchAll(
            "SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 10",
            [$id]
        );

        $this->success($data);
    }

    public function store(Request $request): void
    {
        $data = $request->all();

        $required = ['first_name', 'last_name', 'date_of_joining', 'department_id', 'designation_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        try {
            $employee = $this->employeeService->create(
                array_merge($data, [
                    'branch_id'  => $this->getBranchId($request),
                    'created_by' => $this->currentUser($request)?->id ?? 0,
                ])
            );
            $this->success($employee->toArray(), 'Employee created.', 201);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    public function update(Request $request, int $id): void
    {
        $employee = $this->employeeService->findById($id);
        if (!$employee) {
            $this->error('Employee not found.', 404);
        }

        try {
            $updated = $this->employeeService->update($id, $request->all());
            $this->success($updated->toArray(), 'Employee updated.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Request $request, int $id): void
    {
        $employee = $this->employeeService->findById($id);
        if (!$employee) {
            $this->error('Employee not found.', 404);
        }

        $this->employeeService->delete($id);
        $this->success(null, 'Employee deleted.');
    }

    public function transfer(Request $request, int $id): void
    {
        $data = $request->all();

        $required = ['to_branch_id', 'transfer_date', 'reason'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        try {
            $this->employeeService->transfer(
                employeeId:  $id,
                toBranchId:  (int)$data['to_branch_id'],
                transferDate:$data['transfer_date'],
                reason:      $data['reason'],
                createdBy:   $this->currentUser($request)?->id ?? 0
            );
            $this->success(null, 'Employee transferred.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    public function payslip(Request $request, int $id): void
    {
        $month = (int)($request->query('month') ?? date('n'));
        $year  = (int)($request->query('year') ?? date('Y'));

        $payroll = $this->db->fetchOne(
            "SELECT * FROM payroll WHERE employee_id = ? AND month = ? AND year = ? AND status != 'cancelled'",
            [$id, $month, $year]
        );

        if (!$payroll) {
            $this->error("Payslip for {$month}/{$year} not found.", 404);
        }

        $payroll['components'] = $this->db->fetchAll(
            "SELECT sc.name, sc.component_type, sc.amount FROM salary_components sc
             JOIN salary_structures ss ON ss.id = sc.salary_structure_id
             WHERE ss.employee_id = ? AND ss.is_active = 1 AND sc.is_active = 1",
            [$id]
        );

        $this->success($payroll);
    }

    public function processPayroll(Request $request, int $id): void
    {
        $data = $request->all();

        $required = ['month', 'year'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        try {
            $payroll = $this->payrollService->processSingle(
                employeeId: $id,
                month:      (int)$data['month'],
                year:       (int)$data['year'],
                createdBy:  $this->currentUser($request)?->id ?? 0
            );
            $this->success($payroll, 'Payroll processed.', 201);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    public function attendance(Request $request, int $id): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));

        $from = $request->query('from', date('Y-m-01'));
        $to   = $request->query('to', date('Y-m-d'));

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?",
            [$id, $from, $to]
        );

        $records = $this->db->fetchAll(
            "SELECT * FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?
             ORDER BY date DESC LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            [$id, $from, $to]
        );

        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late,
                    SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) AS half_day,
                    COALESCE(SUM(overtime_hours), 0) AS total_overtime
             FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?",
            [$id, $from, $to]
        );

        $this->paginated([
            'data'       => ['records' => $records, 'summary' => $summary],
            'pagination' => paginate($total, $page, $perPage),
        ]);
    }
}
