<?php

declare(strict_types=1);

namespace App\Controllers\Payroll;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PayrollRepository;

final class PayslipController extends BaseController
{
    public function __construct(private readonly PayrollRepository $payroll) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['month', 'year']);
        $page    = max(1, (int) $request->query('page', 1));
        $result  = $this->payroll->paginatePayroll($filters, $page, 20);

        return $this->render('payroll/payslips/index', [
            'pageTitle'   => 'Payslips',
            'breadcrumbs' => ['Payroll' => null, 'Payslips' => null],
            'result'      => $result,
            'filters'     => $filters,
        ]);
    }

    public function show(int $id): Response
    {
        return $this->render('payroll/payslips/show', [
            'pageTitle'   => 'Payslip #' . $id,
            'breadcrumbs' => ['Payroll' => null, 'Payslips' => '/payroll/payslips', '#' . $id => null],
        ]);
    }

    public function pdf(int $id): Response
    {
        $this->error('PDF export is not yet implemented.');
        return $this->redirect('/payroll/payslips/' . $id);
    }

    public function email(Request $request, int $id): Response
    {
        $this->error('Email sending is not yet implemented.');
        return $this->redirect('/payroll/payslips/' . $id);
    }
}
