@extends('layouts.app')

@section('content')

<!-- Sub Category Dashboard -->
<div class="product-category-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($subCategory) ? 'Edit Sub Category' : 'Add Sub Category' }}</h2>
        <a href="{{ route('sub-category.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ isset($subCategory) ? route('sub-category.update', $subCategory->id) : route('sub-category.store') }}" method="POST">
            @csrf
            @if(isset($subCategory))
                @method('PUT')
            @endif
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select @error('category_id') is-invalid @enderror" name="category_id" required>
                        <option value="" selected disabled>Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $subCategory->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub Category Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="SUB001" value="{{ old('code', $subCategory->code ?? '') }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Name" value="{{ old('name', $subCategory->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 2 -->
                <div class="col-md-6">
                    <label class="form-label">Taxes (Select & Enter Rate)</label>
                    <div class="border p-2 rounded" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40%">Tax Type</th>
                                    <th style="width: 60%">Rate (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($taxes as $tax)
                                    @php
                                        $isChecked = (is_array(old('taxes')) && in_array($tax->id, old('taxes'))) || (isset($subCategory) && $subCategory->taxes->contains($tax->id));
                                        $oldRate = old('tax_rates.'.$tax->id);
                                        $dbRate = isset($subCategory) ? $subCategory->taxes->find($tax->id)?->pivot->tax_rate : '';
                                        $currentRate = $oldRate !== null ? $oldRate : $dbRate;
                                    @endphp
                                    <tr>
                                        <td class="align-middle">
                                            <div class="form-check">
                                                <input class="form-check-input tax-checkbox" type="checkbox" name="taxes[]" value="{{ $tax->id }}" id="tax_{{ $tax->id }}"
                                                    {{ $isChecked ? 'checked' : '' }} onchange="toggleTaxInput(this, '{{ $tax->id }}')">
                                                <label class="form-check-label" for="tax_{{ $tax->id }}">
                                                    {{ $tax->tax_type }}
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm tax-input" 
                                                name="tax_rates[{{ $tax->id }}]" 
                                                id="tax_rate_{{ $tax->id }}" 
                                                placeholder="0.00" 
                                                step="0.01" 
                                                min="0"
                                                value="{{ $currentRate }}"
                                                {{ $isChecked ? '' : 'disabled' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('taxes')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $subCategory->status ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $subCategory->status ?? '') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 3 -->
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Enter sub category description">{{ old('description', $subCategory->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            {{ isset($subCategory) ? 'Update Sub Category' : 'Save Sub Category' }}
                        </button>
                        <a href="{{ route('sub-category.index') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleTaxInput(checkbox, id) {
        var input = document.getElementById('tax_rate_' + id);
        if (checkbox.checked) {
            input.disabled = false;
            input.focus();
        } else {
            input.disabled = true;
            input.value = '';
        }
    }
</script>

@endsection
