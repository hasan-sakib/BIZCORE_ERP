@extends('layouts.app')
@section('title', 'Categories')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Product Categories</h5>
    <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Category</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Parent</th><th>Products</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                    <tr>
                        <td class="fw-semibold">{{ $cat->name }}</td>
                        <td>{{ $cat->parent?->name ?? '—' }}</td>
                        <td>{{ $cat->products_count ?? 0 }}</td>
                        <td><span class="badge bg-{{ $cat->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $cat->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($cat->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('categories.edit', $cat) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('categories.destroy', $cat) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
