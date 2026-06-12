<?php

declare(strict_types=1);

namespace App\Controllers\Accounting;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\AccountRepository;

final class ChartOfAccountsController extends BaseController
{
    public function __construct(private readonly AccountRepository $accounts) {}

    public function index(Request $request): Response
    {
        $search   = (string) $request->query('search', '');
        $type     = (string) $request->query('type', '');
        $accounts = $this->accounts->all($search, $type);

        return $this->render('accounting/accounts/index', [
            'pageTitle'     => 'Chart of Accounts',
            'breadcrumbs'   => ['Accounting' => null, 'Chart of Accounts' => null],
            'accounts'      => $accounts,
            'filters'       => compact('search', 'type'),
            'headerActions' => '<a href="/accounting/accounts/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Account</a>',
        ]);
    }

    public function create(): Response
    {
        return $this->render('accounting/accounts/create', [
            'pageTitle'   => 'New Account',
            'breadcrumbs' => ['Accounting' => null, 'Chart of Accounts' => '/accounting/accounts', 'New' => null],
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
            'parents'     => $this->accounts->all(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data   = $request->except(['_token', '_method']);
        $errors = [];

        if (empty($data['code'])) {
            $errors['code'] = 'Account code is required.';
        }
        if (empty($data['name'])) {
            $errors['name'] = 'Account name is required.';
        }
        if (empty($data['type'])) {
            $errors['type'] = 'Account type is required.';
        }

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/accounting/accounts/create');
        }

        $id = $this->accounts->create($data);
        $this->success('Account created successfully.');
        return $this->redirect('/accounting/accounts/' . $id);
    }

    public function show(int $id): Response
    {
        $account = $this->accounts->findById($id);
        if ($account === null) {
            $this->error('Account not found.');
            return $this->redirect('/accounting/accounts');
        }

        return $this->render('accounting/accounts/show', [
            'pageTitle'   => sanitize($account['name']),
            'breadcrumbs' => ['Accounting' => null, 'Chart of Accounts' => '/accounting/accounts', $account['name'] => null],
            'account'     => $account,
        ]);
    }

    public function edit(int $id): Response
    {
        $account = $this->accounts->findById($id);
        if ($account === null) {
            $this->error('Account not found.');
            return $this->redirect('/accounting/accounts');
        }

        return $this->render('accounting/accounts/edit', [
            'pageTitle'   => 'Edit Account',
            'breadcrumbs' => ['Accounting' => null, 'Chart of Accounts' => '/accounting/accounts', 'Edit' => null],
            'account'     => $account,
            'parents'     => $this->accounts->all(),
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $account = $this->accounts->findById($id);
        if ($account === null) {
            $this->error('Account not found.');
            return $this->redirect('/accounting/accounts');
        }

        $data   = $request->except(['_token', '_method']);
        $errors = [];

        if (empty($data['code'])) {
            $errors['code'] = 'Account code is required.';
        }
        if (empty($data['name'])) {
            $errors['name'] = 'Account name is required.';
        }

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/accounting/accounts/' . $id . '/edit');
        }

        $this->accounts->update($id, $data);
        $this->success('Account updated successfully.');
        return $this->redirect('/accounting/accounts/' . $id);
    }

    public function destroy(int $id): Response
    {
        $account = $this->accounts->findById($id);
        if ($account === null) {
            $this->error('Account not found.');
            return $this->redirect('/accounting/accounts');
        }

        if ($account['is_system']) {
            $this->error('System accounts cannot be deleted.');
            return $this->redirect('/accounting/accounts');
        }

        $this->accounts->delete($id);
        $this->success('Account deleted.');
        return $this->redirect('/accounting/accounts');
    }
}
