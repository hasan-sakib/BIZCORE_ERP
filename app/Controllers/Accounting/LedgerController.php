<?php

declare(strict_types=1);

namespace App\Controllers\Accounting;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\AccountRepository;
use App\Repositories\JournalRepository;

final class LedgerController extends BaseController
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly JournalRepository $journals,
    ) {}

    public function index(Request $request): Response
    {
        $accounts = $this->accounts->all();

        return $this->render('accounting/ledger/index', [
            'pageTitle'   => 'General Ledger',
            'breadcrumbs' => ['Accounting' => null, 'General Ledger' => null],
            'accounts'    => $accounts,
        ]);
    }

    public function show(int $accountId): Response
    {
        $account = $this->accounts->findById($accountId);
        if ($account === null) {
            $this->error('Account not found.');
            return $this->redirect('/accounting/ledger');
        }

        return $this->render('accounting/ledger/show', [
            'pageTitle'   => 'Ledger: ' . sanitize($account['name']),
            'breadcrumbs' => ['Accounting' => null, 'General Ledger' => '/accounting/ledger', $account['name'] => null],
            'account'     => $account,
        ]);
    }

    public function export(Request $request): Response
    {
        $this->error('Export is not yet implemented.');
        return $this->redirect('/accounting/ledger');
    }
}
