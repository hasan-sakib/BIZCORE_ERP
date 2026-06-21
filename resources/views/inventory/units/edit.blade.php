@extends('layouts.app')
@section('title', 'Edit Unit')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('units.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Unit — {{ $unit->name }}</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:480px">
    <div class="card-body">
        <form method="POST" action="{{ route('units.update', $unit) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Unit Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $unit->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Abbreviation <span class="text-danger">*</span></label>
                <input type="text" name="abbreviation" class="form-control @error('abbreviation') is-invalid @enderror" value="{{ old('abbreviation', $unit->abbreviation) }}" required>
                @error('abbreviation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Type</label>
                <select name="type" class="form-select">
                    <option value="">General</option>
                    <option value="weight" @selected(old('type', $unit->type) === 'weight')>Weight</option>
                    <option value="volume" @selected(old('type', $unit->type) === 'volume')>Volume</option>
                    <option value="length" @selected(old('type', $unit->type) === 'length')>Length</option>
                    <option value="piece" @selected(old('type', $unit->type) === 'piece')>Piece</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('units.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
