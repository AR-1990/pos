@extends('layouts.app')

@section('content')

<!-- Products Dashboard -->
<div class="products-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Edit Product</h2>
        <a href="{{ route('all-products') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('products.update', $product->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="sku" placeholder="Enter SKU" value="{{ old('sku', $product->sku) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" placeholder="Enter Product Code" value="{{ old('code', $product->code) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" placeholder="Product Name" value="{{ old('name', $product->name) }}" required>
                </div>

                <!-- Row 2 -->
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" name="category_id" id="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub Category <span class="text-danger">*</span></label>
                    <select class="form-select" name="sub_category_id" id="sub_category_id" required>
                        <option value="">Select Sub Category</option>
                        @foreach($subCategories as $subCategory)
                            <option value="{{ $subCategory->id }}" {{ old('sub_category_id', $product->sub_category_id) == $subCategory->id ? 'selected' : '' }}>{{ $subCategory->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendor</label>
                    <select class="form-select" name="vendor_id">
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id', $product->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Row 3 -->
                <div class="col-md-3">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <select class="form-select" name="unit_id" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit Value</label>
                    <input type="number" class="form-control" name="unit_value" placeholder="e.g. 500" value="{{ old('unit_value', $product->unit_value) }}" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Secondary UOM</label>
                    <input type="text" class="form-control" name="secondary_unit" placeholder="e.g. Box" value="{{ old('secondary_unit', $product->secondary_unit) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pack Size</label>
                    <input type="text" class="form-control" name="pack_size" placeholder="Pieces Per Box" value="{{ old('pack_size', $product->pack_size) }}">
                </div>

                <!-- Row 4 -->
                <div class="col-md-4">
                    <label class="form-label">Barcode</label>
                    <input type="text" class="form-control" name="barcode" placeholder="Optional" value="{{ old('barcode', $product->barcode) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="purchase_price" placeholder="0.00" value="{{ old('purchase_price', $product->purchase_price) }}" step="0.01" min="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="selling_price" placeholder="0.00" value="{{ old('selling_price', $product->selling_price) }}" step="0.01" min="0" required>
                </div>

                <!-- Row 5 -->
                <div class="col-md-3">
                    <label class="form-label">Current Stock</label>
                    <div class="form-control bg-light" style="height:auto; min-height:38px;">
                        @forelse($storeStocks as $ss)
                            <div class="d-flex justify-content-between"><span>{{ $ss->store->name ?? 'Store #' . $ss->store_id }}</span><strong>{{ rtrim(rtrim(number_format($ss->quantity, 3, '.', ''), '0'), '.') }}</strong></div>
                        @empty
                            <span class="text-muted">No stock recorded</span>
                        @endforelse
                    </div>
                    @permission('stock_adjustments', 'add')
                        <small><a href="{{ route('stock-adjustments.create') }}">Change stock via Stock Adjustments</a></small>
                    @else
                        <small class="text-muted">Stock changes go through Stock Adjustments.</small>
                    @endpermission
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min Stock Level</label>
                    <input type="number" class="form-control" name="min_stock_level" placeholder="Reorder Level" value="{{ old('min_stock_level', $product->min_stock_level) }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Stock Level</label>
                    <input type="number" class="form-control" name="max_stock_level" placeholder="Optional" value="{{ old('max_stock_level', $product->max_stock_level) }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Batch / Expiry</label>
                    <select class="form-select" name="batch_expiry" id="batch_expiry" onchange="toggleExpiryDate()">
                        <option value="">Batch / Expiry</option>
                        <option value="Yes" {{ old('batch_expiry', $product->batch_expiry_enabled ? 'Yes' : 'No') == 'Yes' ? 'selected' : '' }}>Yes</option>
                        <option value="No" {{ old('batch_expiry', $product->batch_expiry_enabled ? 'Yes' : 'No') == 'No' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3" id="expiry_date_div" style="display: none;">
                    <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="expiry_date" id="expiry_date" value="{{ old('expiry_date', $product->expiry_date) }}">
                </div>

                <!-- Row 6 -->
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
                                        // Check if tax is in old input or in existing product taxes
                                        if(old('_token')) {
                                            $isChecked = is_array(old('taxes')) && in_array($tax->id, old('taxes'));
                                            $currentRate = old('tax_rates.'.$tax->id);
                                        } else {
                                            $existingTax = $product->taxes->find($tax->id);
                                            $isChecked = $existingTax ? true : false;
                                            $currentRate = $existingTax ? $existingTax->pivot->tax_rate : '';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="align-middle">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="taxes[]" value="{{ $tax->id }}" id="tax_{{ $tax->id }}"
                                                    {{ $isChecked ? 'checked' : '' }} onchange="toggleTaxInput(this, '{{ $tax->id }}')">
                                                <label class="form-check-label" for="tax_{{ $tax->id }}">
                                                    {{ $tax->tax_type }}
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" 
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
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active" {{ old('status', $product->status) == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $product->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            Update Product
                        </button>
                        <a href="{{ route('all-products') }}" class="themeBtn themeBtn--gray">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function toggleExpiryDate() {
        var batchExpiry = document.getElementById('batch_expiry').value;
        var expiryDateDiv = document.getElementById('expiry_date_div');
        var expiryDateInput = document.getElementById('expiry_date');
        
        if (batchExpiry === 'Yes') {
            expiryDateDiv.style.display = 'block';
            expiryDateInput.required = true;
        } else {
            expiryDateDiv.style.display = 'none';
            expiryDateInput.required = false;
            expiryDateInput.value = '';
        }
    }

    // Run on page load to handle old values
    document.addEventListener('DOMContentLoaded', function() {
        toggleExpiryDate();
    });

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

    $(document).ready(function() {
        $('#category_id').on('change', function() {
            var categoryId = $(this).val();
            var subCategorySelect = $('#sub_category_id');
            
            subCategorySelect.html('<option value="">Select Sub Category</option>');
            
            if (categoryId) {
                var url = "{{ url('get-sub-categories') }}/" + categoryId;

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        $.each(data, function(key, value) {
                            subCategorySelect.append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr.responseText);
                    }
                });
            }
        });

        $('#sub_category_id').on('change', function() {
            var subCategoryId = $(this).val();
            
            // Note: We only auto-populate taxes if the user explicitly changes the sub-category.
            // But checking if it was a user change vs initial load is tricky.
            // Since we are in edit mode, and we don't trigger this on load, it should be fine.
            
            // Ask user confirmation before overwriting taxes? 
            // Or just do it. Usually in these systems, changing sub-category implies resetting defaults.
            // But let's just do it for now as per previous logic.
            
            if (subCategoryId) {
                // Check if this is the original sub-category (don't overwrite if it's the same as loaded)
                // But this event only fires on change.
                
                $('input[name="taxes[]"]').prop('checked', false);
                $('input[name^="tax_rates"]').val('').prop('disabled', true);

                $.ajax({
                    url: "{{ url('get-sub-category-taxes') }}/" + subCategoryId,
                    type: 'GET',
                    success: function(data) {
                        $.each(data, function(index, tax) {
                            $('#tax_' + tax.id).prop('checked', true);
                            $('#tax_rate_' + tax.id).val(tax.rate).prop('disabled', false);
                        });
                    },
                    error: function(xhr) {
                        console.error('Tax Fetch Error:', xhr.responseText);
                    }
                });
            }
        });
    });
</script>
@endpush