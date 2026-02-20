@extends('layouts.app')

@section('content')

<!-- Loan Types Dashboard -->
<div class="taxes-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($loanType) ? 'Edit Loan Type' : 'Add Loan Type' }}</h2>
        <a href="{{ route('loan-types.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($loanType) ? route('loan-types.update', $loanType->id) : route('loan-types.store') }}" method="POST">
            @csrf
            @if(isset($loanType))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Name -->
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="e.g. Personal Loan" value="{{ old('name', $loanType->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Status -->
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $loanType->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $loanType->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Optional description...">{{ old('description', $loanType->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            {{ isset($loanType) ? 'Update Loan Type' : 'Save Loan Type' }}
                        </button>
                        <a href="{{ route('loan-types.index') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
