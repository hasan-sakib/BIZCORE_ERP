@extends('layouts.app')
@section('title', 'Brands')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Brands</h5>
    <a href="{{ route('brands.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Brand</a>
</div>
@include('components.flash-messages')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Brand</th><th>Country</th><th>Products</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($brand->logo)
                                <img src="{{ asset('storage/'.$brand->logo) }}" height="32" class="rounded">
                                @endif
                                <span class="fw-semibold">{{ $brand->name }}</span>
                            </div>
                        </td>
                        <td>{{ $brand->country ?? '—' }}</td>
                        <td>{{ $brand->products_count ?? 0 }}</td>
                        <td class="text-end">
                            <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('brands.destroy', $brand) }}" class="d-inline" onsubmit="return confirm('Delete brand?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No brands found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
