@extends('layouts.app')
@section('title', 'Edit Category')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('categories.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Category — {{ $category->name }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:480px">
    <div class="card-body">
        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Parent Category</label>
                <select name="parent_id" class="form-select">
                    <option value="">None (Top Level)</option>
                    @foreach($parents as $p)
                    <option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id) == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="active" @selected(old('status', $category->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $category->status) === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
