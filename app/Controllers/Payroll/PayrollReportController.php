<?php

declare(strict_types=1);

namespace App\Controllers\Payroll;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PayrollRepository;

final class PayrollReportController extends BaseController
{
    public function __construct(private readonly PayrollRepository $payroll) {}

    public function summary(Request $request): Response
    {
        return $this->render('payroll/reports/summary', [
            'pageTitle'   => 'Payroll Summary Report',
            'breadcrumbs' => ['Payroll' => null, 'Reports' => null, 'Summary' => null],
        ]);
    }

    public function register(Request $request): Response
    {
        return $this->render('payroll/reports/register', [
            'pageTitle'   => 'Payroll Register',
            'breadcrumbs' => ['Payroll' => null, 'Reports' => null, 'Register' => null],
        ]);
    }

    public function taxReport(Request $request): Response
    {
        return $this->render('payroll/reports/tax', [
            'pageTitle'   => 'Tax Report',
            'breadcrumbs' => ['Payroll' => null, 'Reports' => null, 'Tax Report' => null],
        ]);
    }

    public function export(Request $request): Response
    {
        $this->error('Export is not yet implemented.');
        return $this->redirect('/payroll/reports/summary');
    }
}
