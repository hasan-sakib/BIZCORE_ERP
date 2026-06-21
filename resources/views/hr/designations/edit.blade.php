@extends('layouts.app')
@section('title', 'Edit Designation')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('designations.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Designation — {{ $designation->title }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('designations.update', $designation) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $designation->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">None</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(old('department_id', $designation->department_id) == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Level</label>
                <input type="text" name="level" class="form-control" value="{{ old('level', $designation->level) }}">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $designation->description) }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('designations.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
