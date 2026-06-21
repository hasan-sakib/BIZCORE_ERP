@extends('layouts.app')
@section('title', 'Edit Product')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('products.show', $product) }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit — {{ $product->name }}</h5>
</div>
@include('components.flash-messages')
<form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Product Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">SKU</label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Barcode</label>
                            <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Buying Price</label>
                            <input type="number" name="buying_price" class="form-control" value="{{ old('buying_price', $product->buying_price) }}" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Selling Price <span class="text-danger">*</span></label>
                            <input type="number" name="selling_price" class="form-control" value="{{ old('selling_price', $product->selling_price) }}" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">VAT %</label>
                            <input type="number" name="vat_percent" class="form-control" value="{{ old('vat_percent', $product->vat_percent) }}" step="0.01">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', $product->reorder_level) }}" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" @selected(old('status', $product->status) === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $product->status) === 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Product Image</label>
                        @if($product->image)
                        <div class="mb-2"><img src="{{ asset('storage/'.$product->image) }}" height="60" class="rounded"></div>
                        @endif
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Classification</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Brand</label>
                        <select name="brand_id" class="form-select">
                            <option value="">None</option>
                            @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Unit</label>
                        <select name="unit_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
