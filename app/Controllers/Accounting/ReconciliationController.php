<?php

declare(strict_types=1);

namespace App\Controllers\Accounting;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class ReconciliationController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->render('accounting/reconciliation/index', [
            'pageTitle'   => 'Bank Reconciliation',
            'breadcrumbs' => ['Accounting' => null, 'Bank Reconciliation' => null],
        ]);
    }

    public function store(Request $request): Response
    {
        $this->error('Bank reconciliation is not yet implemented.');
        return $this->redirect('/accounting/reconciliation');
    }

    public function show(int $id): Response
    {
        return $this->render('accounting/reconciliation/show', [
            'pageTitle'   => 'Reconciliation #' . $id,
            'breadcrumbs' => ['Accounting' => null, 'Bank Reconciliation' => '/accounting/reconciliation', '#' . $id => null],
        ]);
    }
}
