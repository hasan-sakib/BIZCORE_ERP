@extends('layouts.app')
@section('title', $employee->name)
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('employees.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">{{ $employee->name }}</h5>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('employees.timeline', $employee) }}" class="btn btn-sm btn-outline-secondary">Timeline</a>
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
</div>
@include('components.flash-messages')
<div class="row g-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-4">
                @if($employee->photo)
                <img src="{{ asset('storage/'.$employee->photo) }}" class="rounded-circle mb-3" width="90" height="90" style="object-fit:cover">
                @else
                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width:90px;height:90px;font-size:32px">
                    {{ strtoupper(substr($employee->name,0,1)) }}
                </div>
                @endif
                <h6 class="fw-semibold mb-0">{{ $employee->name }}</h6>
                <p class="text-muted small mb-1">{{ $employee->designation?->title ?? '—' }}</p>
                <span class="badge bg-{{ $employee->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $employee->status === 'active' ? 'success' : 'secondary' }} mb-2">
                    {{ ucfirst($employee->status) }}
                </span>
                <p class="text-muted small mb-0"><code>{{ $employee->employee_id }}</code></p>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card mb-3">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $employee->email }}</dd>
                    <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $employee->phone ?? '—' }}</dd>
                    <dt class="col-sm-3">Date of Birth</dt><dd class="col-sm-9">{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-sm-3">Gender</dt><dd class="col-sm-9">{{ ucfirst($employee->gender ?? '—') }}</dd>
                    <dt class="col-sm-3">Blood Group</dt><dd class="col-sm-9">{{ $employee->blood_group ?? '—' }}</dd>
                    <dt class="col-sm-3">Address</dt><dd class="col-sm-9">{{ $employee->address ?? '—' }}</dd>
                    <dt class="col-sm-3">NID</dt><dd class="col-sm-9">{{ $employee->nid ?? '—' }}</dd>
                    <dt class="col-sm-3">Emergency</dt><dd class="col-sm-9">{{ $employee->emergency_contact ?? '—' }}</dd>
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Employment Information</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Department</dt><dd class="col-sm-9">{{ $employee->department?->name ?? '—' }}</dd>
                    <dt class="col-sm-3">Designation</dt><dd class="col-sm-9">{{ $employee->designation?->title ?? '—' }}</dd>
                    <dt class="col-sm-3">Branch</dt><dd class="col-sm-9">{{ $employee->branch?->name ?? '—' }}</dd>
                    <dt class="col-sm-3">Join Date</dt><dd class="col-sm-9">{{ $employee->join_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-sm-3">Employment Type</dt><dd class="col-sm-9">{{ str_replace('_', ' ', ucfirst($employee->employment_type ?? '—')) }}</dd>
                    <dt class="col-sm-3">Basic Salary</dt><dd class="col-sm-9">৳ {{ number_format($employee->basic_salary ?? 0, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
