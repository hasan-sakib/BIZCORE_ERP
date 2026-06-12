<?php

declare(strict_types=1);

namespace App\Controllers\Accounting;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\AccountRepository;
use App\Repositories\JournalRepository;

final class JournalController extends BaseController
{
    public function __construct(
        private readonly JournalRepository $journals,
        private readonly AccountRepository $accounts,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);
        $page    = max(1, (int) $request->query('page', 1));
        $result  = $this->journals->paginate($filters, $page, 20);

        return $this->render('accounting/journals/index', [
            'pageTitle'     => 'Journal Entries',
            'breadcrumbs'   => ['Accounting' => null, 'Journal Entries' => null],
            'result'        => $result,
            'filters'       => $filters,
            'headerActions' => '<a href="/accounting/journals/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Entry</a>',
        ]);
    }

    public function create(): Response
    {
        return $this->render('accounting/journals/create', [
            'pageTitle'   => 'New Journal Entry',
            'breadcrumbs' => ['Accounting' => null, 'Journal Entries' => '/accounting/journals', 'New' => null],
            'accounts'    => $this->accounts->all(),
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $data  = $request->except(['_token', '_method']);
        $lines = $data['lines'] ?? [];

        $totalDebit  = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        $errors = [];
        if (empty($data['date'])) {
            $errors['date'] = 'Date is required.';
        }
        if (count($lines) < 2) {
            $errors['lines'] = 'At least two journal lines are required.';
        } elseif (abs($totalDebit - $totalCredit) > 0.01) {
            $errors['lines'] = 'Total debits must equal total credits.';
        }

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->withInput($request);
            return $this->redirect('/accounting/journals/create');
        }

        $user = $this->currentUser();
        $data['created_by'] = $user?->id ?? 1;

        $id = $this->journals->create($data, $lines);
        $this->success('Journal entry created.');
        return $this->redirect('/accounting/journals/' . $id);
    }

    public function show(int $id): Response
    {
        $entry = $this->journals->findById($id);
        if ($entry === null) {
            $this->error('Journal entry not found.');
            return $this->redirect('/accounting/journals');
        }

        return $this->render('accounting/journals/show', [
            'pageTitle'   => sanitize($entry['entry_number']),
            'breadcrumbs' => ['Accounting' => null, 'Journal Entries' => '/accounting/journals', $entry['entry_number'] => null],
            'entry'       => $entry,
        ]);
    }

    public function edit(int $id): Response
    {
        $entry = $this->journals->findById($id);
        if ($entry === null) {
            $this->error('Journal entry not found.');
            return $this->redirect('/accounting/journals');
        }

        if ($entry['status'] !== 'draft') {
            $this->error('Only draft entries can be edited.');
            return $this->redirect('/accounting/journals/' . $id);
        }

        return $this->render('accounting/journals/edit', [
            'pageTitle'   => 'Edit Journal Entry',
            'breadcrumbs' => ['Accounting' => null, 'Journal Entries' => '/accounting/journals', 'Edit' => null],
            'entry'       => $entry,
            'accounts'    => $this->accounts->all(),
            'errors'      => session()->getFlash('errors', []),
            'old'         => session()->getFlash('old', []),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $this->error('Editing posted journal entries is not allowed. Please void and recreate.');
        return $this->redirect('/accounting/journals/' . $id);
    }

    public function post(Request $request, int $id): Response
    {
        $user = $this->currentUser();
        $this->journals->post($id, $user?->id ?? 1);
        $this->success('Journal entry posted successfully.');
        return $this->redirect('/accounting/journals/' . $id);
    }

    public function void(Request $request, int $id): Response
    {
        $user = $this->currentUser();
        $this->journals->void($id, $user?->id ?? 1);
        $this->success('Journal entry voided.');
        return $this->redirect('/accounting/journals/' . $id);
    }

    public function pdf(int $id): Response
    {
        $this->error('PDF export is not yet implemented.');
        return $this->redirect('/accounting/journals/' . $id);
    }
}
