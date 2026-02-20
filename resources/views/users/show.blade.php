@extends('layouts.app')

@section('content')

<div class="users-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">User Details</h2>   
        <a href="{{ route('users.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>

    <div class="dashboard-card p-4">
        <div class="row">
            <!-- Profile Image & Basic Info -->
            <div class="col-md-3 text-center border-end">
                @if($user->profile_image)
                    <img src="{{ asset($user->profile_image) }}" alt="Profile Image" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #eee;">
                @else
                    <div class="d-flex justify-content-center align-items-center bg-light rounded-circle mb-3 mx-auto" style="width: 150px; height: 150px; border: 3px solid #eee;">
                        <i class="fa-solid fa-user fa-4x text-muted"></i>
                    </div>
                @endif
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-2">{{ $user->designation ?? 'No Designation' }}</p>
                <span class="badge {{ $user->status == 'Active' ? 'bg-success' : 'bg-danger' }}">{{ $user->status }}</span>
            </div>

            <!-- Detailed Info -->
            <div class="col-md-9 ps-md-4">
                <h5 class="text-primary mb-3">Personal Information</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-2">
                        <strong>Email:</strong> <span class="text-muted">{{ $user->email }}</span>
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Phone:</strong> <span class="text-muted">{{ $user->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="col-md-12 mb-2">
                        <strong>Address:</strong> <span class="text-muted">{{ $user->address ?? 'N/A' }}</span>
                    </div>
                </div>

                <hr>

                <h5 class="text-primary mb-3">Job Details</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-2">
                        <strong>Role:</strong> <span class="badge bg-info text-dark">{{ $user->role->name ?? 'N/A' }}</span>
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Salary:</strong> <span class="text-muted">{{ $user->salary ? number_format($user->salary, 2) : 'N/A' }}</span>
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Joining Date:</strong> <span class="text-muted">{{ $user->joining_date ? \Carbon\Carbon::parse($user->joining_date)->format('d M, Y') : 'N/A' }}</span>
                    </div>
                </div>

                <hr>

                <h5 class="text-primary mb-3">Identity & Documents</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-2">
                        <strong>Identity Number (CNIC):</strong> <span class="text-muted">{{ $user->identity_number ?? 'N/A' }}</span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold d-block">Identity Card Front</label>
                        @if($user->identity_card_front)
                            <a href="{{ asset($user->identity_card_front) }}" target="_blank">
                                <img src="{{ asset($user->identity_card_front) }}" class="img-thumbnail" style="height: 150px; width: 100%; object-fit: cover;">
                            </a>
                        @else
                            <span class="text-muted fst-italic">Not Uploaded</span>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold d-block">Identity Card Back</label>
                        @if($user->identity_card_back)
                            <a href="{{ asset($user->identity_card_back) }}" target="_blank">
                                <img src="{{ asset($user->identity_card_back) }}" class="img-thumbnail" style="height: 150px; width: 100%; object-fit: cover;">
                            </a>
                        @else
                            <span class="text-muted fst-italic">Not Uploaded</span>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold d-block">Curriculum Vitae (CV)</label>
                        @if($user->cv_path)
                            <div class="mt-4">
                                <a href="{{ asset($user->cv_path) }}" target="_blank" class="btn btn-outline-primary">
                                    <i class="fa-solid fa-file-pdf me-2"></i> Download / View CV
                                </a>
                            </div>
                        @else
                            <span class="text-muted fst-italic">Not Uploaded</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
