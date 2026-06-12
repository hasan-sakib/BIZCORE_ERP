<?php

declare(strict_types=1);

namespace App\Controllers\Payroll;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PayrollRepository;

final class DeductionController extends BaseController
{
    public function __construct(private readonly PayrollRepository $payroll) {}

    public function index(Request $request): Response
    {
        $components = array_filter(
            $this->payroll->allComponents(),
            static fn ($c) => ($c['type'] ?? '') === 'deduction',
        );

        return $this->render('payroll/deductions/index', [
            'pageTitle'   => 'Deductions',
            'breadcrumbs' => ['Payroll' => null, 'Deductions' => null],
            'components'  => array_values($components),
        ]);
    }

    public function store(Request $request): Response
    {
        $this->error('Deduction management not yet implemented.');
        return $this->redirect('/payroll/deductions');
    }

    public function update(Request $request, int $id): Response
    {
        $this->error('Deduction management not yet implemented.');
        return $this->redirect('/payroll/deductions');
    }

    public function destroy(int $id): Response
    {
        $this->error('Deduction management not yet implemented.');
        return $this->redirect('/payroll/deductions');
    }
}
