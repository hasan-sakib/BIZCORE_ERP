<?php

declare(strict_types=1);

namespace App\Controllers\HR;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;

/**
 * AttendanceController
 *
 * Handles attendance listing, self-service check-in/check-out,
 * and admin create/edit/delete.
 */
final class AttendanceController extends BaseController
{
    public function __construct(
        private readonly AttendanceRepository $attendance,
        private readonly EmployeeRepository   $employees,
    ) {}

    // -------------------------------------------------------------------------
    // Admin: paginated attendance list
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [];

        $empId = (int) $request->query('employee_id', 0);
        if ($empId > 0) {
            $filters['employee_id'] = $empId;
        }

        $dateFrom = (string) $request->query('date_from', '');
        if ($dateFrom !== '') {
            $filters['date_from'] = $dateFrom;
        }

        $dateTo = (string) $request->query('date_to', '');
        if ($dateTo !== '') {
            $filters['date_to'] = $dateTo;
        }

        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $filters['status'] = $status;
        }

        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->attendance->paginateRecords($filters, $page);

        $employeeList = $this->employees->paginateRecords([], 1, 500);
        $pagination   = $this->buildPagination($result['total'], $page, 20);

        return $this->render('attendance/index', [
            'pageTitle'    => 'Attendance',
            'breadcrumbs'  => ['Attendance' => null],
            'records'      => $result['items'],
            'employeeList' => $employeeList['items'],
            'pagination'   => $pagination,
            'filters'      => [
                'employee_id' => $empId,
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
                'status'      => $status,
            ],
            'headerActions' => '<a href="/attendance/my" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-user-clock me-1"></i>My Attendance</a>'
                             . '<a href="/attendance/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Record</a>',
        ]);
    }

    // -------------------------------------------------------------------------
    // Self-service: current user's attendance history
    // -------------------------------------------------------------------------

    public function myAttendance(Request $request): Response
    {
        $user     = $this->currentUser();
        $employee = $user !== null ? $this->employees->findByUserId($user->id) : null;

        $records  = [];
        $today    = null;

        if ($employee !== null) {
            $empId    = (int) $employee['id'];
            $page     = max(1, (int) $request->query('page', 1));
            $result   = $this->attendance->paginateRecords(['employee_id' => $empId], $page, 30);
            $records  = $result['items'];
            $today    = $this->attendance->findTodayForEmployee($empId);
        }

        return $this->render('attendance/my', [
            'pageTitle'  => 'My Attendance',
            'breadcrumbs'=> ['Attendance' => '/attendance', 'My Attendance' => null],
            'employee'   => $employee,
            'records'    => $records,
            'today'      => $today,
        ]);
    }

    // -------------------------------------------------------------------------
    // Self-service: check-in
    // -------------------------------------------------------------------------

    public function checkIn(Request $request): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->error('You must be logged in.');
            return $this->redirect('/attendance');
        }

        $employee = $this->employees->findByUserId($user->id);
        if ($employee === null) {
            $this->error('No employee record linked to your account.');
            return $this->redirect('/attendance');
        }

        $empId   = (int) $employee['id'];
        $existing = $this->attendance->findTodayForEmployee($empId);

        if ($existing !== null) {
            $this->error('You have already checked in today.');
            return $this->redirect('/attendance/my');
        }

        $this->attendance->create([
            'employee_id' => $empId,
            'date'        => date('Y-m-d'),
            'check_in'    => date('H:i:s'),
            'status'      => 'present',
        ]);

        $this->success('Checked in at ' . date('H:i') . '.');
        return $this->redirect('/attendance/my');
    }

    // -------------------------------------------------------------------------
    // Self-service: check-out
    // -------------------------------------------------------------------------

    public function checkOut(Request $request): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->error('You must be logged in.');
            return $this->redirect('/attendance');
        }

        $employee = $this->employees->findByUserId($user->id);
        if ($employee === null) {
            $this->error('No employee record linked to your account.');
            return $this->redirect('/attendance');
        }

        $empId   = (int) $employee['id'];
        $existing = $this->attendance->findTodayForEmployee($empId);

        if ($existing === null) {
            $this->error('You have not checked in today.');
            return $this->redirect('/attendance/my');
        }

        if (!empty($existing['check_out'])) {
            $this->error('You have already checked out today.');
            return $this->redirect('/attendance/my');
        }

        $this->attendance->update((int) $existing['id'], array_merge($existing, [
            'check_out' => date('H:i:s'),
        ]));

        $this->success('Checked out at ' . date('H:i') . '.');
        return $this->redirect('/attendance/my');
    }

    // -------------------------------------------------------------------------
    // Admin: create attendance record
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        $employeeList = $this->employees->paginateRecords([], 1, 500);

        return $this->render('attendance/create', [
            'pageTitle'    => 'Add Attendance Record',
            'breadcrumbs'  => ['Attendance' => '/attendance', 'Add Record' => null],
            'employeeList' => $employeeList['items'],
            'errors'       => session()->getFlash('errors', []),
            'old'          => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateAttendance($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/attendance/create');
        }

        $this->attendance->create($data);
        $this->success('Attendance record created.');
        return $this->redirect('/attendance');
    }

    // -------------------------------------------------------------------------
    // Admin: edit attendance record
    // -------------------------------------------------------------------------

    public function edit(int $id): Response
    {
        $record = $this->attendance->findById($id);
        if ($record === null) {
            $this->error('Attendance record not found.');
            return $this->redirect('/attendance');
        }

        $employeeList = $this->employees->paginateRecords([], 1, 500);

        return $this->render('attendance/edit', [
            'pageTitle'    => 'Edit Attendance Record',
            'breadcrumbs'  => ['Attendance' => '/attendance', 'Edit Record' => null],
            'record'       => $record,
            'employeeList' => $employeeList['items'],
            'errors'       => session()->getFlash('errors', []),
            'old'          => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $record = $this->attendance->findById($id);
        if ($record === null) {
            $this->error('Attendance record not found.');
            return $this->redirect('/attendance');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = $this->validateAttendance($data);

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/attendance/' . $id . '/edit');
        }

        $this->attendance->update($id, $data);
        $this->success('Attendance record updated.');
        return $this->redirect('/attendance');
    }

    // -------------------------------------------------------------------------
    // Admin: delete
    // -------------------------------------------------------------------------

    public function destroy(int $id): Response
    {
        $record = $this->attendance->findById($id);
        if ($record === null) {
            $this->error('Attendance record not found.');
            return $this->redirect('/attendance');
        }

        $this->attendance->delete($id);
        $this->success('Attendance record deleted.');
        return $this->redirect('/attendance');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateAttendance(array $data): array
    {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'Employee is required.';
        }

        if (empty($data['date'])) {
            $errors['date'] = 'Date is required.';
        }

        $validStatuses = ['present', 'absent', 'half_day', 'late', 'holiday', 'leave'];
        if (!empty($data['status']) && !in_array($data['status'], $validStatuses, true)) {
            $errors['status'] = 'Invalid status value.';
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
