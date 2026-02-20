@extends('layouts.app')

@section('content')

<!-- Discount Dashboard -->
<div class="discount-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($discount) ? 'Edit Discount' : 'Add Discount' }}</h2>   
        <a href="{{ route('discount.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($discount) ? route('discount.update', $discount->id) : route('discount.store') }}" method="POST">
            @csrf
            @if(isset($discount))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Discount Name" value="{{ old('name', $discount->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" placeholder="100" value="{{ old('amount', $discount->amount ?? '') }}" step="0.01" min="0" required>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $discount->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $discount->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-actions mt-4 text-end">
                <button type="submit" class="btn btn-primary">{{ isset($discount) ? 'Update' : 'Save' }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
