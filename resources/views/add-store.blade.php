@extends('layouts.app')

@section('content')

<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($store) ? 'Edit Store' : 'Add Store' }}</h2>
        <a href="{{ route('stores.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($store) ? route('stores.update', $store->id) : route('stores.store') }}" method="POST">
            @csrf
            @if(isset($store))
                @method('PUT')
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Store Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Main Branch" value="{{ old('name', $store->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="ST-001" value="{{ old('code', $store->code ?? '') }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $store->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $store->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="0300-0000000" value="{{ old('phone', $store->phone ?? '') }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" placeholder="Enter full address">{{ old('address', $store->address ?? '') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            {{ isset($store) ? 'Update Store' : 'Save Store' }}
                        </button>
                        <a href="{{ route('stores.index') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
