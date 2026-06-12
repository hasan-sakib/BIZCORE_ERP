<?php

declare(strict_types=1);

namespace App\Controllers\Accounting;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\AccountRepository;

final class TrialBalanceController extends BaseController
{
    public function __construct(private readonly AccountRepository $accounts) {}

    public function index(Request $request): Response
    {
        $accounts = $this->accounts->all();

        $totalDebit  = 0;
        $totalCredit = 0;
        foreach ($accounts as $a) {
            if ($a['normal_balance'] === 'debit') {
                $totalDebit += (float) $a['balance'];
            } else {
                $totalCredit += (float) $a['balance'];
            }
        }

        return $this->render('accounting/trial-balance', [
            'pageTitle'   => 'Trial Balance',
            'breadcrumbs' => ['Accounting' => null, 'Trial Balance' => null],
            'accounts'    => $accounts,
            'totalDebit'  => $totalDebit,
            'totalCredit' => $totalCredit,
        ]);
    }

    public function export(Request $request): Response
    {
        $this->error('Export is not yet implemented.');
        return $this->redirect('/accounting/trial-balance');
    }
}
