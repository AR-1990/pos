@extends('layouts.app')

@section('content')

<!-- Product Category Dashboard -->
<div class="product-category-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($category) ? 'Edit Product Category' : 'Add Product Category' }}</h2>
        <a href="{{ route('product-category.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($category) ? route('product-category.update', $category->id) : route('product-category.store') }}" method="POST">
            @csrf
            @if(isset($category))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Category Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="CAT001" value="{{ old('code', $category->code ?? '') }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Electronics" value="{{ old('name', $category->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $category->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $category->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 2 -->
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Enter category description">{{ old('description', $category->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            {{ isset($category) ? 'Update Category' : 'Save Category' }}
                        </button>
                        <a href="{{ route('product-category.index') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
