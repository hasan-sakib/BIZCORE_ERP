@extends('layouts.app')
@section('title', 'Edit Brand')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('brands.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Brand — {{ $brand->name }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:480px">
    <div class="card-body">
        <form method="POST" action="{{ route('brands.update', $brand) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Brand Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $brand->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Country of Origin</label>
                <input type="text" name="country" class="form-control" value="{{ old('country', $brand->country) }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $brand->description) }}</textarea>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Logo</label>
                @if($brand->logo)
                <div class="mb-2"><img src="{{ asset('storage/'.$brand->logo) }}" height="40" class="rounded"></div>
                @endif
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
