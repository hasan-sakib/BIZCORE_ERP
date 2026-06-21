<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingApiController extends BaseApiController
{
    public function __construct(private readonly AccountingService $accountingService) {}

    public function accounts(Request $request): JsonResponse
    {
        $accounts = Account::when($request->get('type'), fn ($q, $t) => $q->where('type', $t))
            ->where('is_active', true)
            ->orderBy('code')
            ->paginate(50);
        return $this->paginate($accounts);
    }

    public function journals(Request $request): JsonResponse
    {
        $entries = JournalEntry::with('lines')
            ->when($request->get('from'), fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
            ->when($request->get('to'),   fn ($q, $d) => $q->whereDate('entry_date', '<=', $d))
            ->latest()
            ->paginate(20);
        return $this->paginate($entries);
    }

    public function createJournal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entry_date'         => ['required', 'date'],
            'description'        => ['required', 'string'],
            'lines'              => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit'      => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit'     => ['nullable', 'numeric', 'min:0'],
        ]);
        return $this->created($this->accountingService->createEntry($data, $this->currentUser()?->id));
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        return $this->success($this->accountingService->getTrialBalance($asOf));
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));
        return $this->success($this->accountingService->getIncomeStatement($from, $to));
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        return $this->success($this->accountingService->getBalanceSheet($asOf));
    }
}
