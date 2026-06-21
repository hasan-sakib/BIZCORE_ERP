@extends('layouts.app')
@section('title', 'Designations')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Designations</h5>
    <a href="{{ route('designations.create') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Add Designation
    </a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Title</th><th>Department</th><th>Level</th><th>Employees</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($designations as $designation)
                    <tr>
                        <td class="fw-semibold">{{ $designation->title }}</td>
                        <td>{{ $designation->department?->name ?? '—' }}</td>
                        <td>{{ $designation->level ?? '—' }}</td>
                        <td>{{ $designation->employees_count ?? 0 }}</td>
                        <td class="text-end">
                            <a href="{{ route('designations.edit', $designation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('designations.destroy', $designation) }}" class="d-inline" onsubmit="return confirm('Delete designation?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No designations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
