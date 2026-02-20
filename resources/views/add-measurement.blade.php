@extends('layouts.app')

@section('content')

<!-- Measurement Dashboard -->
<div class="measurement-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($measurement) ? 'Edit Measurement' : 'Add Measurement' }}</h2>
        <a href="{{ route('measurement-unit.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($measurement) ? route('measurement-unit.update', $measurement->id) : route('measurement-unit.store') }}" method="POST">
            @csrf
            @if(isset($measurement))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-6">
                    <label class="form-label">Measurement Scale <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Kilogram (Kg)" value="{{ old('name', $measurement->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $measurement->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $measurement->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            {{ isset($measurement) ? 'Update Measurement' : 'Save Measurement' }}
                        </button>
                        <a href="{{ route('measurement-unit.index') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
