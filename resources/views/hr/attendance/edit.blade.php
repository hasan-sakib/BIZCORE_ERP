@extends('layouts.app')
@section('title', 'Edit Attendance')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('attendance.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit Attendance</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('attendance.update', $attendance) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Employee</label>
                <input type="text" class="form-control" value="{{ $attendance->employee?->name }}" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Date</label>
                <input type="date" name="date" class="form-control" value="{{ old('date', $attendance->date?->format('Y-m-d')) }}">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Check In</label>
                    <input type="time" name="check_in" class="form-control" value="{{ old('check_in', $attendance->check_in) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Check Out</label>
                    <input type="time" name="check_out" class="form-control" value="{{ old('check_out', $attendance->check_out) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="present" @selected(old('status', $attendance->status) === 'present')>Present</option>
                    <option value="absent" @selected(old('status', $attendance->status) === 'absent')>Absent</option>
                    <option value="late" @selected(old('status', $attendance->status) === 'late')>Late</option>
                    <option value="half_day" @selected(old('status', $attendance->status) === 'half_day')>Half Day</option>
                    <option value="leave" @selected(old('status', $attendance->status) === 'leave')>On Leave</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Note</label>
                <textarea name="note" class="form-control" rows="2">{{ old('note', $attendance->note) }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
