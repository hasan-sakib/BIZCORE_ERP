<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\BaseController;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\PdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuotationController extends BaseController
{
    public function __construct(private readonly PdfService $pdfService) {}

    public function index(Request $request): View
    {
        $quotations = Quotation::with('customer')
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);
        return view('sales.quotations.index', compact('quotations'));
    }

    public function create(): View
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();
        return view('sales.quotations.create', compact('customers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id'           => ['required', 'integer', 'exists:customers,id'],
            'quotation_date'        => ['required', 'date'],
            'valid_until'           => ['required', 'date', 'after:quotation_date'],
            'discount_amount'       => ['nullable', 'numeric', 'min:0'],
            'note'                  => ['nullable', 'string'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $quotation = Quotation::create([
            'customer_id'     => $data['customer_id'],
            'quotation_date'  => $data['quotation_date'],
            'valid_until'     => $data['valid_until'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'note'            => $data['note'] ?? null,
            'status'          => 'draft',
            'created_by'      => Auth::id(),
        ]);

        foreach ($data['items'] as $item) {
            $qty        = $item['quantity'];
            $price      = $item['unit_price'];
            $discRate   = $item['discount_rate'] ?? 0;
            $subtotal   = $qty * $price * (1 - $discRate / 100);

            $quotation->items()->create([
                'product_id'    => $item['product_id'],
                'quantity'      => $qty,
                'unit_price'    => $price,
                'discount_rate' => $discRate,
                'subtotal'      => $subtotal,
            ]);
        }

        $this->success('Quotation created.');
        return redirect()->route('quotations.show', $quotation->id);
    }

    public function show(int $id): View
    {
        $quotation = Quotation::with(['customer', 'items.product'])->findOrFail($id);
        return view('sales.quotations.show', compact('quotation'));
    }

    public function edit(int $id): View
    {
        $quotation = Quotation::with('items')->findOrFail($id);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();
        return view('sales.quotations.edit', compact('quotation', 'customers', 'products'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'quotation_date'        => ['required', 'date'],
            'valid_until'           => ['required', 'date'],
            'discount_amount'       => ['nullable', 'numeric', 'min:0'],
            'note'                  => ['nullable', 'string'],
            'status'                => ['required', 'string'],
        ]);

        Quotation::findOrFail($id)->update($data);
        $this->success('Quotation updated.');
        return redirect()->route('quotations.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        Quotation::findOrFail($id)->delete();
        $this->success('Quotation deleted.');
        return redirect()->route('quotations.index');
    }

    public function pdf(int $id): Response
    {
        $quotation = Quotation::with(['customer', 'items.product'])->findOrFail($id);
        return $this->pdfService->download('sales.quotations.pdf', compact('quotation'), "quotation-{$quotation->id}.pdf");
    }
}
