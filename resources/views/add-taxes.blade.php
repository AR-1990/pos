@extends('layouts.app')

@section('content')

<!-- Taxes Dashboard -->
<div class="taxes-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($tax) ? 'Edit Tax' : 'Add Taxes' }}</h2>
        <a href="{{ route('taxes.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($tax) ? route('taxes.update', $tax->id) : route('taxes.store') }}" method="POST">
            @csrf
            @if(isset($tax))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Tax Type</label>
                    <input type="text" class="form-control @error('tax_type') is-invalid @enderror" name="tax_type" placeholder="GST / VAT" value="{{ old('tax_type', $tax->tax_type ?? '') }}">
                    @error('tax_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Applied On</label>
                    <select class="form-select @error('applied_on') is-invalid @enderror" name="applied_on">
                        <option value="" disabled selected>Select Applied On</option>
                        <option value="Purchase" {{ old('applied_on', $tax->applied_on ?? '') == 'Purchase' ? 'selected' : '' }}>Purchase</option>
                        <option value="Sales" {{ old('applied_on', $tax->applied_on ?? '') == 'Sales' ? 'selected' : '' }}>Sales</option>
                    </select>
                    @error('applied_on')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 2 -->
                <div class="col-md-4">
                    <label class="form-label">Effective Date</label>
                    <div class="position-relative">
                        <input type="date" class="form-control @error('effective_date') is-invalid @enderror" name="effective_date" value="{{ old('effective_date', isset($tax->effective_date) ? \Carbon\Carbon::parse($tax->effective_date)->format('Y-m-d') : '') }}">
                        @error('effective_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $tax->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $tax->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            {{ isset($tax) ? 'Update Taxes' : 'Save Taxes' }}
                        </button>
                        <a href="{{ route('taxes.index') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
