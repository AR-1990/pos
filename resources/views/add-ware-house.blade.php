@extends('layouts.app')

@section('content')

<!-- Ware House Dashboard -->
<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($warehouse) ? 'Edit Ware House' : 'Add Ware House' }}</h2>
        <a href="{{ route('ware-house.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($warehouse) ? route('ware-house.update', $warehouse->id) : route('ware-house.store') }}" method="POST">
            @csrf
            @if(isset($warehouse))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Ware House" value="{{ old('name', $warehouse->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="HO-001" value="{{ old('code', $warehouse->code ?? '') }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $warehouse->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $warehouse->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 2 -->
                <div class="col-12">
                    <label class="form-label">Location</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" placeholder="Enter full address">{{ old('address', $warehouse->address ?? '') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            {{ isset($warehouse) ? 'Update Ware House' : 'Save Ware House' }}
                        </button>
                        <a href="{{ route('ware-house.index') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
