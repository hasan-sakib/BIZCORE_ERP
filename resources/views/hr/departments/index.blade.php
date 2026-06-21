@extends('layouts.app')
@section('title', 'Departments')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Departments</h5>
    <a href="{{ route('departments.create') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Add Department
    </a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Code</th><th>Manager</th><th>Employees</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                    <tr>
                        <td class="fw-semibold">{{ $department->name }}</td>
                        <td><code>{{ $department->code }}</code></td>
                        <td>{{ $department->manager?->name ?? '—' }}</td>
                        <td>{{ $department->employees_count ?? 0 }}</td>
                        <td>
                            <span class="badge bg-{{ $department->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $department->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($department->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('departments.edit', $department) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('departments.destroy', $department) }}" class="d-inline" onsubmit="return confirm('Delete department?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No departments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
