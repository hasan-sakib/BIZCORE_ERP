<?php

declare(strict_types=1);

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\BaseController;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends BaseController
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::when($request->get('search'), fn ($q, $s) => $q->where('name', 'like', "%$s%"))
            ->orderBy('name')
            ->paginate(20);
        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:150'],
            'email'        => ['nullable', 'email', 'max:150', 'unique:suppliers,email'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'address'      => ['nullable', 'string', 'max:500'],
            'vat_number'   => ['nullable', 'string', 'max:50'],
            'credit_days'  => ['nullable', 'integer', 'min:0'],
            'status'       => ['nullable', 'string'],
        ]);

        $supplier = Supplier::create($data);
        $this->success('Supplier created.');
        return redirect()->route('suppliers.show', $supplier->id);
    }

    public function show(int $id): View
    {
        $supplier = Supplier::with('purchaseOrders')->findOrFail($id);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(int $id): View
    {
        $supplier = Supplier::findOrFail($id);
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'email'       => ['nullable', 'email', 'max:150', 'unique:suppliers,email,' . $id],
            'phone'       => ['nullable', 'string', 'max:30'],
            'address'     => ['nullable', 'string', 'max:500'],
            'vat_number'  => ['nullable', 'string', 'max:50'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'status'      => ['nullable', 'string'],
        ]);

        Supplier::findOrFail($id)->update($data);
        $this->success('Supplier updated.');
        return redirect()->route('suppliers.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        Supplier::findOrFail($id)->delete();
        $this->success('Supplier deleted.');
        return redirect()->route('suppliers.index');
    }

    public function orders(int $id): View
    {
        $supplier = Supplier::with('purchaseOrders')->findOrFail($id);
        return view('suppliers.orders', compact('supplier'));
    }

    public function ledger(int $id): View
    {
        $supplier = Supplier::findOrFail($id);
        $ledger   = \App\Models\Payment::where('payer_type', Supplier::class)
            ->where('payer_id', $id)
            ->with('allocations')
            ->latest()
            ->get();
        return view('suppliers.ledger', compact('supplier', 'ledger'));
    }
}
