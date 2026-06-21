@extends('layouts.app')
@section('title', 'Employees')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Employees</h5>
    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i>Add Employee
    </a>
</div>
@include('components.flash-messages')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or ID..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-link text-muted">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($employee->photo)
                                <img src="{{ asset('storage/'.$employee->photo) }}" class="rounded-circle" width="36" height="36" style="object-fit:cover">
                                @else
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:13px">
                                    {{ strtoupper(substr($employee->name,0,1)) }}
                                </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $employee->name }}</div>
                                    <div class="text-muted small">{{ $employee->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><code>{{ $employee->employee_id }}</code></td>
                        <td>{{ $employee->department?->name ?? '—' }}</td>
                        <td>{{ $employee->designation?->title ?? '—' }}</td>
                        <td class="text-muted small">{{ $employee->join_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $employee->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($employee->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($employees->hasPages())
    <div class="card-footer">{{ $employees->links() }}</div>
    @endif
</div>
@endsection
