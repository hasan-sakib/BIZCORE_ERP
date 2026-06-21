@extends('layouts.app')
@section('title', 'Warehouses')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Warehouses</h5>
    <a href="{{ route('warehouses.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Warehouse</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Location</th><th>Capacity</th><th>Default</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $wh)
                    <tr>
                        <td class="fw-semibold">{{ $wh->name }}</td>
                        <td>{{ $wh->location ?? '—' }}</td>
                        <td>{{ $wh->capacity ? number_format($wh->capacity) : '—' }}</td>
                        <td>{{ $wh->is_default ? '<span class="badge bg-info-subtle text-info">Default</span>' : '' }}</td>
                        <td><span class="badge bg-{{ $wh->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $wh->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($wh->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('warehouses.edit', $wh) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('warehouses.destroy', $wh) }}" class="d-inline" onsubmit="return confirm('Delete warehouse?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No warehouses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
