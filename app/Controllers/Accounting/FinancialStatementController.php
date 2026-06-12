<?php

declare(strict_types=1);

namespace App\Controllers\Accounting;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\AccountRepository;

final class FinancialStatementController extends BaseController
{
    public function __construct(private readonly AccountRepository $accounts) {}

    public function incomeStatement(Request $request): Response
    {
        $revenues = $this->accounts->all('', 'revenue');
        $expenses = $this->accounts->all('', 'expense');

        $totalRevenue = array_sum(array_column($revenues, 'balance'));
        $totalExpense = array_sum(array_column($expenses, 'balance'));

        return $this->render('accounting/statements/income-statement', [
            'pageTitle'    => 'Income Statement',
            'breadcrumbs'  => ['Accounting' => null, 'Income Statement' => null],
            'revenues'     => $revenues,
            'expenses'     => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netIncome'    => $totalRevenue - $totalExpense,
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $assets      = $this->accounts->all('', 'asset');
        $liabilities = $this->accounts->all('', 'liability');
        $equity      = $this->accounts->all('', 'equity');

        return $this->render('accounting/statements/balance-sheet', [
            'pageTitle'    => 'Balance Sheet',
            'breadcrumbs'  => ['Accounting' => null, 'Balance Sheet' => null],
            'assets'       => $assets,
            'liabilities'  => $liabilities,
            'equity'       => $equity,
            'totalAssets'  => array_sum(array_column($assets, 'balance')),
            'totalLiabEquity' => array_sum(array_column($liabilities, 'balance')) + array_sum(array_column($equity, 'balance')),
        ]);
    }

    public function cashFlow(Request $request): Response
    {
        return $this->render('accounting/statements/cash-flow', [
            'pageTitle'   => 'Cash Flow Statement',
            'breadcrumbs' => ['Accounting' => null, 'Cash Flow' => null],
        ]);
    }

    public function retainedEarnings(Request $request): Response
    {
        return $this->render('accounting/statements/retained-earnings', [
            'pageTitle'   => 'Retained Earnings',
            'breadcrumbs' => ['Accounting' => null, 'Retained Earnings' => null],
        ]);
    }

    public function export(Request $request): Response
    {
        $this->error('Export is not yet implemented.');
        return $this->redirect('/accounting/income-statement');
    }
}
