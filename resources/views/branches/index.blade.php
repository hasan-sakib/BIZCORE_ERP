@extends('layouts.app')
@section('title', 'Branches')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Branches</h5>
    <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Add Branch
    </a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Head Office</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                    <tr>
                        <td class="fw-semibold">{{ $branch->name }}</td>
                        <td><code>{{ $branch->code }}</code></td>
                        <td class="text-muted small">{{ $branch->address ?? '—' }}</td>
                        <td>{{ $branch->phone ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $branch->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $branch->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($branch->status) }}
                            </span>
                        </td>
                        <td>{{ $branch->is_head ? '<span class="badge bg-info-subtle text-info">Head</span>' : '' }}</td>
                        <td class="text-end">
                            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @unless($branch->is_head)
                            <form method="POST" action="{{ route('branches.destroy', $branch) }}" class="d-inline" onsubmit="return confirm('Delete branch?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No branches found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
