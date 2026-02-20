@extends('layouts.app')

@section('content')

<!-- Customer Dashboard -->
<div class="customer-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($customer) ? 'Edit Customer' : 'Add Customer' }}</h2>
        <a href="{{ route('customer.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($customer) ? route('customer.update', $customer->id) : route('customer.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($customer))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-6">
                    <label class="form-label">Customer Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Customer Name" value="{{ old('name', $customer->name ?? '') }}">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="HO-001" value="{{ old('code', $customer->code ?? '') }}">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 2 -->
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="8759-4775-7385" value="{{ old('phone', $customer->phone ?? '') }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">CNIC (Optional)</label>
                    <input type="text" class="form-control @error('cnic') is-invalid @enderror" name="cnic" placeholder="XXXXX-XXXXXXX-X" value="{{ old('cnic', $customer->cnic ?? '') }}">
                    @error('cnic')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 3 -->
                <div class="col-12 mt-4">
                    <h5 class="mb-3">NIC / Security number</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NIC Front Image (Optional)</label>
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
                                @if(isset($customer->nic_front))
                                    <img src="{{ asset($customer->nic_front) }}" alt="NIC Front" class="img-thumbnail" style="max-height: 150px;">
                                @endif
                            </div>
                            @error('nic_front')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIC Back Image (Optional)</label>
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
                                @if(isset($customer->nic_back))
                                    <img src="{{ asset($customer->nic_back) }}" alt="NIC Back" class="img-thumbnail" style="max-height: 150px;">
                                @endif
                            </div>
                            @error('nic_back')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Row 4 -->
                <div class="col-12">
                    <label class="form-label">Location (Optional)</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="5" placeholder="Karachi Pakistan">{{ old('address', $customer->address ?? '') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 5 -->
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $customer->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $customer->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            {{ isset($customer) ? 'Update Customer' : 'Save Customer' }}
                        </button>
                        <a href="{{ route('customer.index') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
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

@endsection
