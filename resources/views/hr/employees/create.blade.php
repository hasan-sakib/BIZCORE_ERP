@extends('layouts.app')
@section('title', 'Add Employee')
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('employees.index') }}" class="text-muted"><i class="fa-solid fa-arrow-left"></i></a>
    <h5 class="mb-0 fw-semibold">Add Employee</h5>
</div>
@include('components.flash-messages')
<form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Personal Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" value="{{ old('employee_id') }}" required>
                            @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="male" @selected(old('gender') === 'male')>Male</option>
                                <option value="female" @selected(old('gender') === 'female')>Female</option>
                                <option value="other" @selected(old('gender') === 'other')>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Blood Group</label>
                            <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group') }}" placeholder="A+, B-, etc.">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NID / Passport</label>
                            <input type="text" name="nid" class="form-control" value="{{ old('nid') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact') }}">
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
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach($designations as $desig)
                            <option value="{{ $desig->id }}" @selected(old('designation_id') == $desig->id)>{{ $desig->title }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Join Date <span class="text-danger">*</span></label>
                        <input type="date" name="join_date" class="form-control @error('join_date') is-invalid @enderror" value="{{ old('join_date') }}" required>
                        @error('join_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Basic Salary</label>
                        <input type="number" name="basic_salary" class="form-control" value="{{ old('basic_salary', 0) }}" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employment Type</label>
                        <select name="employment_type" class="form-select">
                            <option value="full_time" @selected(old('employment_type') === 'full_time')>Full Time</option>
                            <option value="part_time" @selected(old('employment_type') === 'part_time')>Part Time</option>
                            <option value="contract" @selected(old('employment_type') === 'contract')>Contract</option>
                            <option value="intern" @selected(old('employment_type') === 'intern')>Intern</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">Create Employee</button>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
