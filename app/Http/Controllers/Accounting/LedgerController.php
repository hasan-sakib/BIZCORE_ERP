<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\BaseController;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends BaseController
{
    public function index(Request $request): View
    {
        $accounts = Account::where('is_active', true)->orderBy('code')->get();
        return view('accounting.ledger.index', compact('accounts'));
    }

    public function show(Request $request, int $accountId): View
    {
        $account = Account::findOrFail($accountId);

        $lines = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->when($request->get('from'), fn ($q, $d) => $q->whereHas('journalEntry', fn ($j) => $j->whereDate('entry_date', '>=', $d)))
            ->when($request->get('to'),   fn ($q, $d) => $q->whereHas('journalEntry', fn ($j) => $j->whereDate('entry_date', '<=', $d)))
            ->with('journalEntry')
            ->orderBy('id')
            ->get();

        $runningBalance = 0;
        $lines = $lines->map(function ($line) use (&$runningBalance) {
            $runningBalance += ($line->debit - $line->credit);
            $line->running_balance = $runningBalance;
            return $line;
        });

        return view('accounting.ledger.show', compact('account', 'lines'));
    }
}
