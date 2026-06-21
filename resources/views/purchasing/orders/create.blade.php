@extends('layouts.app')
@section('title', 'New Purchase Order')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('purchase-orders.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">New Purchase Order</h5>
</div>
@include('components.flash-messages')
<form method="POST" action="{{ route('purchase-orders.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRow"><i class="fa-solid fa-plus me-1"></i>Add Row</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr><th>Product</th><th>Qty</th><th>Unit Cost</th><th>Total</th><th></th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select form-select-sm" required>
                                            <option value="">Select product</option>
                                            @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-cost="{{ $p->buying_price }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm qty" min="1" value="1" required style="width:80px"></td>
                                    <td><input type="number" name="items[0][unit_cost]" class="form-control form-control-sm price" min="0" step="0.01" value="0" required style="width:110px"></td>
                                    <td class="row-total fw-semibold align-middle">৳ 0.00</td>
                                    <td></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-semibold">Total:</td>
                                    <td id="grandTotal" class="fw-bold">৳ 0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">PO Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                            <option value="">Select supplier</option>
                            @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Order Date</label>
                        <input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Expected Delivery</label>
                        <input type="date" name="expected_date" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">Create PO</button>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@push('scripts')
<script>
let rowIdx = 1;
const products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'cost' => $p->buying_price]));
function recalcRow(row) {
    const qty = parseFloat(row.querySelector('.qty').value)||0, price = parseFloat(row.querySelector('.price').value)||0;
    row.querySelector('.row-total').textContent = '৳ ' + (qty*price).toFixed(2);
    let sum = 0; document.querySelectorAll('.row-total').forEach(el => sum += parseFloat(el.textContent.replace('৳','').trim())||0);
    document.getElementById('grandTotal').textContent = '৳ ' + sum.toFixed(2);
}
document.querySelector('#itemsTable').addEventListener('input', e => {
    if (e.target.classList.contains('qty')||e.target.classList.contains('price')) recalcRow(e.target.closest('tr'));
    if (e.target.tagName==='SELECT') {
        const opt = e.target.selectedOptions[0]; const row = e.target.closest('tr');
        if (opt && opt.dataset.cost) row.querySelector('.price').value = opt.dataset.cost;
        recalcRow(row);
    }
});
document.getElementById('addRow').addEventListener('click', () => {
    const tbody = document.querySelector('#itemsTable tbody');
    const opts = products.map(p => `<option value="${p.id}" data-cost="${p.cost}">${p.name}</option>`).join('');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="items[${rowIdx}][product_id]" class="form-select form-select-sm" required><option value="">Select</option>${opts}</select></td>
        <td><input type="number" name="items[${rowIdx}][quantity]" class="form-control form-control-sm qty" min="1" value="1" required style="width:80px"></td>
        <td><input type="number" name="items[${rowIdx}][unit_cost]" class="form-control form-control-sm price" min="0" step="0.01" value="0" required style="width:110px"></td>
        <td class="row-total fw-semibold align-middle">৳ 0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr); rowIdx++;
});
</script>
@endpush
@endsection
