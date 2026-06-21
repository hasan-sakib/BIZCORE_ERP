<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\BaseController;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReconciliationController extends BaseController
{
    public function index(Request $request): View
    {
        $accounts = Account::where('type', 'bank')->where('is_active', true)->orderBy('code')->get();
        return view('accounting.reconciliation.index', compact('accounts'));
    }

    public function show(Request $request, int $accountId): View
    {
        $account = Account::findOrFail($accountId);
        $from    = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to      = $request->get('to', now()->format('Y-m-d'));

        $lines = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted')
                ->whereBetween('entry_date', [$from, $to]))
            ->with('journalEntry')
            ->orderBy('id')
            ->get();

        return view('accounting.reconciliation.show', compact('account', 'lines', 'from', 'to'));
    }

    public function markReconciled(Request $request, int $accountId): RedirectResponse
    {
        $data = $request->validate([
            'line_ids'   => ['required', 'array'],
            'line_ids.*' => ['integer'],
        ]);

        JournalEntryLine::whereIn('id', $data['line_ids'])
            ->where('account_id', $accountId)
            ->update(['is_reconciled' => true]);

        $this->success('Lines marked as reconciled.');
        return back();
    }
}
