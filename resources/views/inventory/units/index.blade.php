@extends('layouts.app')
@section('title', 'Units')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Units of Measure</h5>
    <a href="{{ route('units.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Unit</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Abbreviation</th><th>Type</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                    <tr>
                        <td class="fw-semibold">{{ $unit->name }}</td>
                        <td><code>{{ $unit->abbreviation }}</code></td>
                        <td>{{ $unit->type ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('units.destroy', $unit) }}" class="d-inline" onsubmit="return confirm('Delete unit?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No units found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
