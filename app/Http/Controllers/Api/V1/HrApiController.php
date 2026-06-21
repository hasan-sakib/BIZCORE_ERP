<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Models\Department;
use App\Models\Designation;
use App\Services\AttendanceService;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrApiController extends BaseApiController
{
    public function __construct(
        private readonly EmployeeService   $employeeService,
        private readonly AttendanceService $attendanceService,
    ) {}

    public function employees(Request $request): JsonResponse
    {
        return $this->paginate($this->employeeService->paginate($request->all()));
    }

    public function showEmployee(int $id): JsonResponse
    {
        return $this->success($this->employeeService->findWithDetails($id));
    }

    public function createEmployee(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name'     => ['required', 'string', 'max:80'],
            'last_name'      => ['required', 'string', 'max:80'],
            'email'          => ['required', 'email', 'unique:employees,email'],
            'department_id'  => ['required', 'integer', 'exists:departments,id'],
            'designation_id' => ['required', 'integer', 'exists:designations,id'],
            'branch_id'      => ['required', 'integer', 'exists:branches,id'],
            'joining_date'   => ['required', 'date'],
            'basic_salary'   => ['required', 'numeric', 'min:0'],
        ]);
        return $this->created($this->employeeService->create($data));
    }

    public function updateEmployee(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'first_name'    => ['sometimes', 'string', 'max:80'],
            'last_name'     => ['sometimes', 'string', 'max:80'],
            'basic_salary'  => ['sometimes', 'numeric', 'min:0'],
        ]);
        return $this->success($this->employeeService->update($id, $data));
    }

    public function attendance(Request $request): JsonResponse
    {
        return $this->paginate($this->attendanceService->paginate($request->all()));
    }

    public function checkIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'note'        => ['nullable', 'string'],
        ]);
        return $this->created($this->attendanceService->checkIn($data['employee_id'], $data['note'] ?? null));
    }

    public function checkOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);
        return $this->success($this->attendanceService->checkOut($data['employee_id']));
    }

    public function departments(): JsonResponse
    {
        return $this->success(Department::with('designations')->orderBy('name')->get());
    }

    public function designations(Request $request): JsonResponse
    {
        $designations = Designation::when($request->get('department_id'), fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('title')
            ->get();
        return $this->success($designations);
    }
}
