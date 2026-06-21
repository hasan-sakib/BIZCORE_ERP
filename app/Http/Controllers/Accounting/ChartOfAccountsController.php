<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\BaseController;
use App\Models\Account;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountsController extends BaseController
{
    public function __construct(private readonly AccountingService $accountingService) {}

    public function index(): View
    {
        $accounts = Account::orderBy('code')->paginate(30);
        return view('accounting.accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $parents = Account::orderBy('code')->get();
        return view('accounting.accounts.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:20', 'unique:accounts,code'],
            'name'        => ['required', 'string', 'max:150'],
            'type'        => ['required', 'string'],
            'parent_id'   => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ]);

        Account::create($data);
        $this->success('Account created.');
        return redirect()->route('accounts.index');
    }

    public function show(int $id): View
    {
        $account = Account::with('journalLines')->findOrFail($id);
        return view('accounting.accounts.show', compact('account'));
    }

    public function edit(int $id): View
    {
        $account = Account::findOrFail($id);
        $parents = Account::where('id', '!=', $id)->orderBy('code')->get();
        return view('accounting.accounts.edit', compact('account', 'parents'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:20', 'unique:accounts,code,' . $id],
            'name'        => ['required', 'string', 'max:150'],
            'type'        => ['required', 'string'],
            'parent_id'   => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ]);

        Account::findOrFail($id)->update($data);
        $this->success('Account updated.');
        return redirect()->route('accounts.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $account = Account::findOrFail($id);

        if ($account->journalLines()->exists()) {
            $this->error('Cannot delete account with journal entries.');
            return back();
        }

        $account->delete();
        $this->success('Account deleted.');
        return redirect()->route('accounts.index');
    }
}
