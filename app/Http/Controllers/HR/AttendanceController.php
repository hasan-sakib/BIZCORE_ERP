<?php

declare(strict_types=1);

namespace App\Http\Controllers\HR;

use App\Http\Controllers\BaseController;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends BaseController
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function index(Request $request): View
    {
        $records     = $this->attendanceService->paginate($request->all());
        $departments = Department::orderBy('name')->get();
        $branches    = Branch::where('status', 'active')->orderBy('name')->get();
        return view('attendance.index', compact('records', 'departments', 'branches'));
    }

    public function create(): View
    {
        $employees = Employee::active()->with('department')->orderBy('employee_number')->get();
        return view('attendance.create', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id'  => ['required', 'integer', 'exists:employees,id'],
            'date'         => ['required', 'date'],
            'check_in'     => ['nullable', 'date_format:H:i'],
            'check_out'    => ['nullable', 'date_format:H:i'],
            'status'       => ['required', 'string'],
            'note'         => ['nullable', 'string', 'max:500'],
        ]);

        $this->attendanceService->create($data);
        $this->success('Attendance recorded.');
        return redirect()->route('attendance.index');
    }

    public function edit(int $id): View
    {
        $record    = $this->attendanceService->find($id);
        $employees = Employee::active()->orderBy('employee_number')->get();
        return view('attendance.edit', compact('record', 'employees'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'check_in'  => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status'    => ['required', 'string'],
            'note'      => ['nullable', 'string', 'max:500'],
        ]);

        $this->attendanceService->update($id, $data);
        $this->success('Attendance updated.');
        return redirect()->route('attendance.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->attendanceService->delete($id);
        $this->success('Attendance record deleted.');
        return back();
    }

    public function myRecords(Request $request): View
    {
        $user      = Auth::user();
        $employee  = $user->employee;
        $records   = $this->attendanceService->paginate(array_merge($request->all(), ['employee_id' => $employee?->id]));
        return view('attendance.my-records', compact('records', 'employee'));
    }
}
