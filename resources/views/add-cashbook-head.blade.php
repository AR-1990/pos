@extends('layouts.app')

@section('content')

<!-- Cashbook Head Dashboard -->
<div class="discount-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Add Cashbook Head</h2>   
        <a href="{{ route('cashbook-head.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ route('cashbook-head.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Cashbook Head Name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="Code (Optional)" value="{{ old('code') }}">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
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
            
            <div class="form-actions mt-4 text-end">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection
