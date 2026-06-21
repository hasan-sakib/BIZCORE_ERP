@extends('layouts.app')
@section('title', 'Products')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Products</h5>
    <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Add Product
    </a>
</div>
@include('components.flash-messages')
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, SKU..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-link text-muted">Clear</a>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Product</th><th>SKU</th><th>Category</th><th>Brand</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $product->name }}</div>
                            @if($product->description)
                            <div class="text-muted small">{{ Str::limit($product->description, 40) }}</div>
                            @endif
                        </td>
                        <td><code>{{ $product->sku }}</code></td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td>{{ $product->brand?->name ?? '—' }}</td>
                        <td>৳ {{ number_format($product->selling_price ?? 0, 2) }}</td>
                        <td>
                            @if(($product->stockLevel?->quantity ?? 0) <= ($product->reorder_level ?? 0))
                            <span class="text-danger fw-semibold">{{ $product->stockLevel?->quantity ?? 0 }}</span>
                            @else
                            {{ $product->stockLevel?->quantity ?? 0 }}
                            @endif
                            <span class="text-muted small">{{ $product->unit?->abbreviation }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $product->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $product->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
    <div class="card-footer">{{ $products->links() }}</div>
    @endif
</div>
@endsection
