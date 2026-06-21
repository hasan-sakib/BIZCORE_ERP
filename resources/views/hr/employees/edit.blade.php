@extends('layouts.app')
@section('title', 'Edit Employee')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('employees.show', $employee) }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Edit — {{ $employee->name }}</h5>
</div>
@include('components.flash-messages')
<form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Personal Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $employee->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employee ID</label>
                            <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', $employee->employee_id) }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="male" @selected(old('gender', $employee->gender) === 'male')>Male</option>
                                <option value="female" @selected(old('gender', $employee->gender) === 'female')>Female</option>
                                <option value="other" @selected(old('gender', $employee->gender) === 'other')>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Blood Group</label>
                            <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group', $employee->blood_group) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $employee->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NID</label>
                            <input type="text" name="nid" class="form-control" value="{{ old('nid', $employee->nid) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $employee->emergency_contact) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">Employment Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id', $employee->department_id) == $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Designation</label>
                        <select name="designation_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($designations as $desig)
                            <option value="{{ $desig->id }}" @selected(old('designation_id', $employee->designation_id) == $desig->id)>{{ $desig->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Basic Salary</label>
                        <input type="number" name="basic_salary" class="form-control" value="{{ old('basic_salary', $employee->basic_salary) }}" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $employee->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $employee->status) === 'inactive')>Inactive</option>
                            <option value="terminated" @selected(old('status', $employee->status) === 'terminated')>Terminated</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Photo</label>
                        @if($employee->photo)
                        <div class="mb-2">
                            <img src="{{ asset('storage/'.$employee->photo) }}" width="50" height="50" class="rounded-circle" style="object-fit:cover">
                        </div>
                        @endif
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
