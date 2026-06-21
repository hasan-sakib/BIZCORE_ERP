@extends('layouts.app')
@section('title', 'New Invoice')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('invoices.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">New Invoice</h5>
</div>
@include('components.flash-messages')
<form method="POST" action="{{ route('invoices.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Line Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRow"><i class="fa-solid fa-plus me-1"></i>Add Row</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>VAT%</th><th>Total</th><th></th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                                            <option value="">Select</option>
                                            @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-price="{{ $p->selling_price }}" data-vat="{{ $p->vat_percent }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm qty" min="0.01" step="0.01" value="1" required style="width:80px"></td>
                                    <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm price" min="0" step="0.01" value="0" required style="width:110px"></td>
                                    <td><input type="number" name="items[0][vat_percent]" class="form-control form-control-sm" min="0" step="0.01" value="0" style="width:70px"></td>
                                    <td class="row-total fw-semibold align-middle">৳ 0.00</td>
                                    <td></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="4" class="text-end fw-semibold">Total:</td>
                                    <td id="grandTotal" class="fw-bold">৳ 0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <label class="form-label fw-semibold">Terms & Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Payment terms, notes..."></textarea>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Invoice Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sales Order (optional)</label>
                        <select name="sales_order_id" class="form-select">
                            <option value="">None</option>
                            @foreach($salesOrders as $so)
                            <option value="{{ $so->id }}" @selected(old('sales_order_id') == $so->id)>{{ $so->order_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Discount (৳)</label>
                        <input type="number" name="discount_amount" class="form-control" value="0" min="0" step="0.01">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">Create Invoice</button>
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@push('scripts')
<script>
let rowIdx = 1;
const products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->selling_price, 'vat' => $p->vat_percent]));

function recalcRow(row) {
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const price = parseFloat(row.querySelector('.price').value) || 0;
    row.querySelector('.row-total').textContent = '৳ ' + (qty * price).toFixed(2);
    recalcTotal();
}
function recalcTotal() {
    let sum = 0;
    document.querySelectorAll('.row-total').forEach(el => sum += parseFloat(el.textContent.replace('৳','').trim())||0);
    document.getElementById('grandTotal').textContent = '৳ ' + sum.toFixed(2);
}
document.querySelector('#itemsTable').addEventListener('input', e => {
    if (e.target.classList.contains('qty') || e.target.classList.contains('price')) recalcRow(e.target.closest('tr'));
    if (e.target.classList.contains('product-select')) {
        const opt = e.target.selectedOptions[0];
        const row = e.target.closest('tr');
        if (opt && opt.dataset.price) row.querySelector('.price').value = opt.dataset.price;
        recalcRow(row);
    }
});
document.getElementById('addRow').addEventListener('click', () => {
    const tbody = document.querySelector('#itemsTable tbody');
    const opts = products.map(p => `<option value="${p.id}" data-price="${p.price}" data-vat="${p.vat}">${p.name}</option>`).join('');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="items[${rowIdx}][product_id]" class="form-select form-select-sm product-select" required><option value="">Select</option>${opts}</select></td>
        <td><input type="number" name="items[${rowIdx}][quantity]" class="form-control form-control-sm qty" min="0.01" step="0.01" value="1" required style="width:80px"></td>
        <td><input type="number" name="items[${rowIdx}][unit_price]" class="form-control form-control-sm price" min="0" step="0.01" value="0" required style="width:110px"></td>
        <td><input type="number" name="items[${rowIdx}][vat_percent]" class="form-control form-control-sm" min="0" step="0.01" value="0" style="width:70px"></td>
        <td class="row-total fw-semibold align-middle">৳ 0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove();recalcTotal()"><i class="fa-solid fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    rowIdx++;
});
</script>
@endpush
@endsection
