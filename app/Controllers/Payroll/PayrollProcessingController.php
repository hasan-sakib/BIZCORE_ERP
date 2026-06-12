<?php

declare(strict_types=1);

namespace App\Controllers\Payroll;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PayrollRepository;

final class PayrollProcessingController extends BaseController
{
    public function __construct(private readonly PayrollRepository $payroll) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['month', 'year', 'status']);
        $page    = max(1, (int) $request->query('page', 1));
        $result  = $this->payroll->paginatePayroll($filters, $page, 20);

        return $this->render('payroll/process/index', [
            'pageTitle'   => 'Payroll Processing',
            'breadcrumbs' => ['Payroll' => null, 'Processing' => null],
            'result'      => $result,
            'filters'     => $filters,
        ]);
    }

    public function run(Request $request): Response
    {
        $this->error('Payroll processing is not yet implemented.');
        return $this->redirect('/payroll/process');
    }

    public function approve(Request $request, int $id): Response
    {
        $this->error('Payroll approval is not yet implemented.');
        return $this->redirect('/payroll/process');
    }

    public function reject(Request $request, int $id): Response
    {
        $this->error('Payroll rejection is not yet implemented.');
        return $this->redirect('/payroll/process');
    }

    public function disburse(Request $request, int $id): Response
    {
        $this->error('Payroll disbursement is not yet implemented.');
        return $this->redirect('/payroll/process');
    }
}
