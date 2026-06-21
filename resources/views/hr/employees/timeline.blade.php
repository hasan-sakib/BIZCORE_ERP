@extends('layouts.app')
@section('title', 'Employee Timeline')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('employees.show', $employee) }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Timeline — {{ $employee->name }}</h5>
</div>
<div class="row g-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <h6 class="fw-semibold mb-0">{{ $employee->name }}</h6>
                <p class="text-muted small mb-0">{{ $employee->employee_id }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">Activity Timeline</div>
            <div class="card-body">
                @forelse($timeline ?? [] as $event)
                <div class="d-flex gap-3 mb-3">
                    <div class="text-primary mt-1"><i class="fa-solid fa-circle-dot"></i></div>
                    <div>
                        <div class="fw-semibold small">{{ $event['title'] ?? '' }}</div>
                        <div class="text-muted small">{{ $event['description'] ?? '' }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ $event['date'] ?? '' }}</div>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3">No timeline events yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
