@extends('layouts.app')
@section('title', 'Attendance')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-semibold">Attendance</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('attendance.my-records') }}" class="btn btn-sm btn-outline-secondary">My Records</a>
        <a href="{{ route('attendance.create') }}" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-plus me-1"></i>Mark Attendance
        </a>
    </div>
</div>
@include('components.flash-messages')
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="present" @selected(request('status') === 'present')>Present</option>
                    <option value="absent" @selected(request('status') === 'absent')>Absent</option>
                    <option value="late" @selected(request('status') === 'late')>Late</option>
                    <option value="half_day" @selected(request('status') === 'half_day')>Half Day</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Employee</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($attendance as $record)
                    <tr>
                        <td>{{ $record->employee?->name ?? '—' }}</td>
                        <td>{{ $record->date?->format('d M Y') }}</td>
                        <td>{{ $record->check_in ?? '—' }}</td>
                        <td>{{ $record->check_out ?? '—' }}</td>
                        <td>{{ $record->working_hours ?? '—' }}</td>
                        <td>
                            @php
                                $statusColors = ['present'=>'success','absent'=>'danger','late'=>'warning','half_day'=>'info'];
                                $color = $statusColors[$record->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ ucfirst(str_replace('_',' ',$record->status)) }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('attendance.edit', $record) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No attendance records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($attendance->hasPages())
    <div class="card-footer">{{ $attendance->links() }}</div>
    @endif
</div>
@endsection
