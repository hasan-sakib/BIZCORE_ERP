@extends('layouts.app')
@section('title', 'Stock Out')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <h5 class="mb-0 fw-semibold">Issue Stock (Stock Out)</h5>
</div>
@include('components.flash-messages')
<form method="POST" action="{{ route('stock-out.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">
                        <i class="fa-solid fa-plus me-1"></i>Add Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr><th>Product</th><th>Quantity</th><th></th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select form-select-sm" required>
                                            <option value="">Select product</option>
                                            @foreach($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm" min="0.01" step="0.01" value="1" required style="width:120px"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected($wh->is_default)>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="sale">Sale</option>
                            <option value="damage">Damage / Loss</option>
                            <option value="sample">Sample</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reference</label>
                        <input type="text" name="reference" class="form-control" placeholder="Invoice / SO number">
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
        <button type="submit" class="btn btn-warning">Issue Stock</button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@push('scripts')
<script>
let rowIndex = 1;
const products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name . ' (' . $p->sku . ')']));
document.getElementById('addRow').addEventListener('click', function() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    const opts = products.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
    tr.innerHTML = `
        <td><select name="items[${rowIndex}][product_id]" class="form-select form-select-sm" required><option value="">Select</option>${opts}</select></td>
        <td><input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm" min="0.01" step="0.01" value="1" required style="width:120px"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    rowIndex++;
});
</script>
@endpush
@endsection
