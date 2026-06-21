@extends('layouts.app')
@section('title', 'Mark Attendance')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('attendance.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Mark Attendance</h5>
</div>
@include('components.flash-messages')
<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(old('employee_id') == $emp->id)>{{ $emp->name }}</option>
                    @endforeach
                </select>
                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Check In</label>
                    <input type="time" name="check_in" class="form-control" value="{{ old('check_in') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Check Out</label>
                    <input type="time" name="check_out" class="form-control" value="{{ old('check_out') }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="present" @selected(old('status') === 'present')>Present</option>
                    <option value="absent" @selected(old('status') === 'absent')>Absent</option>
                    <option value="late" @selected(old('status') === 'late')>Late</option>
                    <option value="half_day" @selected(old('status') === 'half_day')>Half Day</option>
                    <option value="leave" @selected(old('status') === 'leave')>On Leave</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Note</label>
                <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
