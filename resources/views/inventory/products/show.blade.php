@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('products.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">{{ $product->name }}</h5>
    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary ms-auto">Edit</a>
</div>
@include('components.flash-messages')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card text-center mb-3">
            <div class="card-body">
                @if($product->image)
                <img src="{{ asset('storage/'.$product->image) }}" class="img-fluid rounded" style="max-height:200px;object-fit:contain">
                @else
                <div class="text-muted py-4"><i class="fa-solid fa-box fa-3x opacity-25"></i></div>
                @endif
                <h6 class="fw-semibold mt-2 mb-1">{{ $product->name }}</h6>
                <code class="small">{{ $product->sku }}</code>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Stock Summary</div>
            <div class="card-body text-center">
                <h2 class="fw-bold {{ ($product->stockLevel?->quantity ?? 0) <= ($product->reorder_level ?? 0) ? 'text-danger' : 'text-success' }}">
                    {{ $product->stockLevel?->quantity ?? 0 }}
                </h2>
                <p class="text-muted mb-0">{{ $product->unit?->abbreviation ?? 'units' }} in stock</p>
                @if(($product->stockLevel?->quantity ?? 0) <= ($product->reorder_level ?? 0))
                <div class="alert alert-warning mt-2 mb-0 py-1 small">Below reorder level ({{ $product->reorder_level }})</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Product Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $product->category?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Brand</dt><dd class="col-sm-8">{{ $product->brand?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Unit</dt><dd class="col-sm-8">{{ $product->unit?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Buying Price</dt><dd class="col-sm-8">৳ {{ number_format($product->buying_price ?? 0, 2) }}</dd>
                    <dt class="col-sm-4">Selling Price</dt><dd class="col-sm-8 fw-semibold">৳ {{ number_format($product->selling_price ?? 0, 2) }}</dd>
                    <dt class="col-sm-4">VAT</dt><dd class="col-sm-8">{{ $product->vat_percent ?? 0 }}%</dd>
                    <dt class="col-sm-4">Reorder Level</dt><dd class="col-sm-8">{{ $product->reorder_level ?? 0 }}</dd>
                    <dt class="col-sm-4">Barcode</dt><dd class="col-sm-8">{{ $product->barcode ?? '—' }}</dd>
                    <dt class="col-sm-4">Status</dt><dd class="col-sm-8">
                        <span class="badge bg-{{ $product->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $product->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </dd>
                    @if($product->description)
                    <dt class="col-sm-4">Description</dt><dd class="col-sm-8">{{ $product->description }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
