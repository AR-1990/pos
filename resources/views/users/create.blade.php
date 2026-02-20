@extends('layouts.app')

@section('content')

<div class="users-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Add User</h2>   
        <a href="{{ route('users.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <h5 class="mb-3 text-muted">Personal Information</h5>
            <div class="row g-3">
                <!-- Profile Image -->
                <div class="col-md-12 mb-3">
                    <label class="form-label">Profile Image</label>
                    <input type="file" class="form-control @error('profile_image') is-invalid @enderror" name="profile_image" accept="image/*">
                     @error('profile_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Name -->
                <div class="col-md-4">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="John Doe" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Email -->
                <div class="col-md-4">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="john@example.com" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Phone -->
                <div class="col-md-4">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="0300-1234567" value="{{ old('phone') }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mt-2">
                <!-- Password -->
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Confirm Password -->
                <div class="col-md-6">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password_confirmation" required>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3 text-muted">Role & Designation</h5>
            <div class="row g-3">
                 <!-- Role -->
                 <div class="col-md-4">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select @error('role_id') is-invalid @enderror" name="role_id" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Designation -->
                <div class="col-md-4">
                    <label class="form-label">Designation</label>
                    <input type="text" class="form-control @error('designation') is-invalid @enderror" name="designation" placeholder="e.g. Senior Accountant" value="{{ old('designation') }}">
                    @error('designation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                 <!-- Salary -->
                 <div class="col-md-4">
                    <label class="form-label">Salary</label>
                    <input type="number" step="0.01" class="form-control @error('salary') is-invalid @enderror" name="salary" placeholder="0.00" value="{{ old('salary') }}">
                    @error('salary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mt-2">
                <!-- Joining Date -->
                <div class="col-md-4">
                    <label class="form-label">Date of Joining</label>
                    <input type="date" class="form-control @error('joining_date') is-invalid @enderror" name="joining_date" value="{{ old('joining_date') }}">
                    @error('joining_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                 <!-- Status -->
                 <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status">
                        <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3 text-muted">Identity & Documents</h5>
            <div class="row g-3">
                <!-- Identity Number -->
                <div class="col-md-4">
                    <label class="form-label">Identity Number (CNIC)</label>
                    <input type="text" class="form-control @error('identity_number') is-invalid @enderror" name="identity_number" placeholder="XXXXX-XXXXXXX-X" value="{{ old('identity_number') }}">
                    @error('identity_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Address -->
                <div class="col-md-8">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" placeholder="Full Address" value="{{ old('address') }}">
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mt-2">
                <!-- Identity Card Front -->
                <div class="col-md-4">
                    <label class="form-label">Identity Card Front (Image)</label>
                    <input type="file" class="form-control @error('identity_card_front') is-invalid @enderror" name="identity_card_front" accept="image/*">
                    @error('identity_card_front')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                 <!-- Identity Card Back -->
                 <div class="col-md-4">
                    <label class="form-label">Identity Card Back (Image)</label>
                    <input type="file" class="form-control @error('identity_card_back') is-invalid @enderror" name="identity_card_back" accept="image/*">
                    @error('identity_card_back')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                 <!-- CV Upload -->
                 <div class="col-md-4">
                    <label class="form-label">Upload CV (PDF/Doc)</label>
                    <input type="file" class="form-control @error('cv_path') is-invalid @enderror" name="cv_path" accept=".pdf,.doc,.docx">
                    @error('cv_path')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-actions mt-4 text-end">
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

@endsection
