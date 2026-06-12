<?php

declare(strict_types=1);

namespace App\Controllers\Payroll;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PayrollRepository;
use App\Repositories\EmployeeRepository;

final class SalaryStructureController extends BaseController
{
    public function __construct(
        private readonly PayrollRepository  $payroll,
        private readonly EmployeeRepository $employees,
    ) {}

    public function index(Request $request): Response
    {
        $structures = $this->payroll->allSalaryStructures();

        return $this->render('payroll/salary-structures/index', [
            'pageTitle'     => 'Salary Structures',
            'breadcrumbs'   => ['Payroll' => null, 'Salary Structures' => null],
            'structures'    => $structures,
            'headerActions' => '<a href="/payroll/salary-structures/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Structure</a>',
        ]);
    }

    public function create(): Response
    {
        $employees = $this->employees->all();

        return $this->render('payroll/salary-structures/create', [
            'pageTitle'   => 'New Salary Structure',
            'breadcrumbs' => ['Payroll' => null, 'Salary Structures' => '/payroll/salary-structures', 'New' => null],
            'employees'   => $employees,
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token', '_method']);
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'Employee is required.';
        }
        if (empty($data['basic_salary']) || (float) $data['basic_salary'] <= 0) {
            $errors['basic_salary'] = 'Basic salary must be greater than zero.';
        }
        if (empty($data['effective_date'])) {
            $errors['effective_date'] = 'Effective date is required.';
        }

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/payroll/salary-structures/create');
        }

        $user = $this->currentUser();
        $data['created_by'] = $user?->id;

        $id = $this->payroll->createSalaryStructure($data);
        $this->success('Salary structure created successfully.');
        return $this->redirect('/payroll/salary-structures/' . $id);
    }

    public function show(int $id): Response
    {
        $structure = $this->payroll->findSalaryStructure($id);
        if ($structure === null) {
            $this->error('Salary structure not found.');
            return $this->redirect('/payroll/salary-structures');
        }

        return $this->render('payroll/salary-structures/show', [
            'pageTitle'   => 'Salary Structure #' . $id,
            'breadcrumbs' => ['Payroll' => null, 'Salary Structures' => '/payroll/salary-structures', '#' . $id => null],
            'structure'   => $structure,
        ]);
    }

    public function edit(int $id): Response
    {
        $structure = $this->payroll->findSalaryStructure($id);
        if ($structure === null) {
            $this->error('Salary structure not found.');
            return $this->redirect('/payroll/salary-structures');
        }

        return $this->render('payroll/salary-structures/edit', [
            'pageTitle'   => 'Edit Salary Structure',
            'breadcrumbs' => ['Payroll' => null, 'Salary Structures' => '/payroll/salary-structures', 'Edit' => null],
            'structure'   => $structure,
            'employees'   => $this->employees->all(),
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $this->error('Update not yet implemented.');
        return $this->redirect('/payroll/salary-structures/' . $id);
    }

    public function destroy(int $id): Response
    {
        $this->error('Delete not yet implemented.');
        return $this->redirect('/payroll/salary-structures');
    }
}
