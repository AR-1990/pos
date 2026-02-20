@extends('layouts.app')

@section('content')

<!-- View Vendor Dashboard -->
<div class="vendor-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Vendor Details</h2>
        <a href="{{ route('vendor.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card">
        <div class="row g-4">
            <!-- Vendor Info -->
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-4">Basic Information</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="text-muted small">Vendor Name</label>
                                <p class="fw-bold">{{ $vendor->name }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Code</label>
                                <p class="fw-bold">{{ $vendor->code }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Status</label>
                                <p>
                                    @if($vendor->status == 'Active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Email Address</label>
                                <p class="fw-bold">{{ $vendor->email ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Phone</label>
                                <p class="fw-bold">{{ $vendor->phone ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">CNIC</label>
                                <p class="fw-bold">{{ $vendor->cnic ?? 'N/A' }}</p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small">Address</label>
                                <p class="fw-bold">{{ $vendor->address ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NIC Images -->
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-4">NIC / Security number</h5>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="text-center">
                                    <h6 class="mb-3 text-muted">Front Side</h6>
                                    <div class="p-3 border rounded bg-light">
                                        @if($vendor->nic_front)
                                            <img src="{{ asset($vendor->nic_front) }}" alt="NIC Front" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                                        @else
                                            <div class="py-5 text-muted">
                                                <i class="fa-regular fa-image fa-3x mb-3"></i>
                                                <p>No Image Available</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <h6 class="mb-3 text-muted">Back Side</h6>
                                    <div class="p-3 border rounded bg-light">
                                        @if($vendor->nic_back)
                                            <img src="{{ asset($vendor->nic_back) }}" alt="NIC Back" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                                        @else
                                            <div class="py-5 text-muted">
                                                <i class="fa-regular fa-image fa-3x mb-3"></i>
                                                <p>No Image Available</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-end mt-4">
            <a href="{{ route('vendor.edit', $vendor->id) }}" class="btn btn-primary">
                <i class="fa-regular fa-pen-to-square me-2"></i>Edit Vendor
            </a>
        </div>
    </div>
</div>

@endsection
