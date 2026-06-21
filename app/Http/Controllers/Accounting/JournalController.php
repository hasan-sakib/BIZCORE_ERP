<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\BaseController;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Services\PdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JournalController extends BaseController
{
    public function __construct(
        private readonly AccountingService $accountingService,
        private readonly PdfService        $pdfService,
    ) {}

    public function index(Request $request): View
    {
        $entries = JournalEntry::with('lines')
            ->when($request->get('from'), fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
            ->when($request->get('to'),   fn ($q, $d) => $q->whereDate('entry_date', '<=', $d))
            ->latest('entry_date')
            ->paginate(20);
        return view('accounting.journals.index', compact('entries'));
    }

    public function create(): View
    {
        $accounts = Account::where('is_active', true)->orderBy('code')->get();
        return view('accounting.journals.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entry_date'        => ['required', 'date'],
            'reference'         => ['nullable', 'string', 'max:100'],
            'description'       => ['required', 'string', 'max:500'],
            'lines'             => ['required', 'array', 'min:2'],
            'lines.*.account_id'=> ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit'     => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit'    => ['nullable', 'numeric', 'min:0'],
            'lines.*.note'      => ['nullable', 'string', 'max:255'],
        ]);

        $entry = $this->accountingService->createEntry($data, Auth::id());
        $this->success('Journal entry created.');
        return redirect()->route('journals.show', $entry->id);
    }

    public function show(int $id): View
    {
        $entry = JournalEntry::with(['lines.account', 'createdBy'])->findOrFail($id);
        return view('accounting.journals.show', compact('entry'));
    }

    public function post(int $id): RedirectResponse
    {
        $this->accountingService->postEntry($id, Auth::id());
        $this->success('Journal entry posted.');
        return back();
    }

    public function reverse(int $id): RedirectResponse
    {
        $reversal = $this->accountingService->reverseEntry($id, Auth::id());
        $this->success('Reversal entry created.');
        return redirect()->route('journals.show', $reversal->id);
    }

    public function pdf(int $id): Response
    {
        $entry = JournalEntry::with('lines.account')->findOrFail($id);
        return $this->pdfService->download('accounting.journals.pdf', compact('entry'), "journal-{$entry->id}.pdf");
    }
}
