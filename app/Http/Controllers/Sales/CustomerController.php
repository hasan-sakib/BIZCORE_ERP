<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\BaseController;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends BaseController
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(Request $request): View
    {
        $customers = $this->customerService->paginate($request->all());
        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['nullable', 'email', 'max:150', 'unique:customers,email'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'address'        => ['nullable', 'string', 'max:500'],
            'vat_number'     => ['nullable', 'string', 'max:50'],
            'credit_limit'   => ['nullable', 'numeric', 'min:0'],
            'credit_days'    => ['nullable', 'integer', 'min:0'],
            'discount_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status'         => ['nullable', 'string'],
        ]);

        $customer = $this->customerService->create($data);
        $this->success('Customer created successfully.');
        return redirect()->route('customers.show', $customer->id);
    }

    public function show(int $id): View
    {
        $customer = $this->customerService->findWithHistory($id);
        return view('customers.show', compact('customer'));
    }

    public function edit(int $id): View
    {
        $customer = \App\Models\Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'email'         => ['nullable', 'email', 'max:150', 'unique:customers,email,' . $id],
            'phone'         => ['nullable', 'string', 'max:30'],
            'address'       => ['nullable', 'string', 'max:500'],
            'vat_number'    => ['nullable', 'string', 'max:50'],
            'credit_limit'  => ['nullable', 'numeric', 'min:0'],
            'credit_days'   => ['nullable', 'integer', 'min:0'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status'        => ['nullable', 'string'],
        ]);

        $this->customerService->update($id, $data);
        $this->success('Customer updated.');
        return redirect()->route('customers.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->customerService->delete($id);
        $this->success('Customer deleted.');
        return redirect()->route('customers.index');
    }

    public function orders(int $id): View
    {
        $customer = \App\Models\Customer::with('salesOrders')->findOrFail($id);
        return view('customers.orders', compact('customer'));
    }

    public function ledger(int $id): View
    {
        $customer = \App\Models\Customer::findOrFail($id);
        $ledger   = $this->customerService->getLedger($id);
        return view('customers.ledger', compact('customer', 'ledger'));
    }
}
