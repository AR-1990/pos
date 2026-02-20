@extends('layouts.app')

@section('content')

<!-- Vendor Dashboard -->
<div class="vendor-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($vendor) ? 'Edit Vendor' : 'Add Vendor' }}</h2>
        <a href="{{ route('vendor.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($vendor) ? route('vendor.update', $vendor->id) : route('vendor.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($vendor))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-6">
                    <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Vendor Name" value="{{ old('name', $vendor->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="example@email.com" value="{{ old('email', $vendor->email ?? '') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 2 -->
                <div class="col-md-4">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="HO-001" value="{{ old('code', $vendor->code ?? '') }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="8759-4775-7385" value="{{ old('phone', $vendor->phone ?? '') }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">CNIC</label>
                    <input type="text" class="form-control @error('cnic') is-invalid @enderror" name="cnic" placeholder="XXXXX-XXXXXXX-X" value="{{ old('cnic', $vendor->cnic ?? '') }}">
                    @error('cnic')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- NIC Upload Section -->
                <div class="col-12 mt-4">
                    <h5 class="mb-3">NIC / Security number</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NIC Front Image</label>
                            <input type="file" class="file-upload-input" id="nicFrontUpload" name="nic_front" accept="image/*" hidden onchange="previewImage(this, 'nicFrontPreview')">
                            <label for="nicFrontUpload" class="file-upload-area @error('nic_front') border-danger @enderror">
                                <div class="file-upload-icon">
                                    <i class="fa-solid fa-folder-arrow-up"></i>
                                </div>
                                <p class="file-upload-text">
                                    Drag and drop files, or
                                    <strong class="text-primary">Browse</strong>
                                </p>
                            </label>
                            <div class="mt-2" id="nicFrontPreview">
                                @if(isset($vendor->nic_front))
                                    <img src="{{ asset($vendor->nic_front) }}" alt="NIC Front" class="img-thumbnail" style="max-height: 150px;">
                                @endif
                            </div>
                            @error('nic_front')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIC Back Image</label>
                            <input type="file" class="file-upload-input" id="nicBackUpload" name="nic_back" accept="image/*" hidden onchange="previewImage(this, 'nicBackPreview')">
                            <label for="nicBackUpload" class="file-upload-area @error('nic_back') border-danger @enderror">
                                <div class="file-upload-icon">
                                    <i class="fa-solid fa-folder-arrow-up"></i>
                                </div>
                                <p class="file-upload-text">
                                    Drag and drop files, or
                                    <strong class="text-primary">Browse</strong>
                                </p>
                            </label>
                            <div class="mt-2" id="nicBackPreview">
                                @if(isset($vendor->nic_back))
                                    <img src="{{ asset($vendor->nic_back) }}" alt="NIC Back" class="img-thumbnail" style="max-height: 150px;">
                                @endif
                            </div>
                            @error('nic_back')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Row 3 -->
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" placeholder="Enter Address">{{ old('address', $vendor->address ?? '') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status -->
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $vendor->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $vendor->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-actions mt-4 text-end">
                <button type="submit" class="btn btn-primary">{{ isset($vendor) ? 'Update' : 'Save' }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                var preview = document.getElementById(previewId);
                preview.innerHTML = '<img src="' + e.target.result + '" class="img-thumbnail" style="max-height: 150px;">';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<style>
    .file-upload-area {
        display: block;
        padding: 20px;
        text-align: center;
        border: 2px dashed #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .file-upload-area:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }
    .file-upload-icon {
        font-size: 24px;
        color: #6c757d;
        margin-bottom: 10px;
    }
    .file-upload-text {
        color: #6c757d;
        margin: 0;
    }
</style>

@endsection
