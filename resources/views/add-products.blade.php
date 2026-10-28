@extends('layouts.app')

@section('content')

<!-- Products Dashboard -->
<div class="products-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Add Products</h2>
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
        <form action="{{ route('products.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="sku" placeholder="Enter SKU" value="{{ old('sku', $skuPrefix) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" placeholder="Enter Product Code" value="{{ old('code', $codePrefix) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" placeholder="Product Name" value="{{ old('name') }}" required>
                </div>

                <!-- Row 2 -->
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" name="category_id" id="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub Category <span class="text-danger">*</span></label>
                    <select class="form-select" name="sub_category_id" id="sub_category_id" required>
                        <option value="">Select Sub Category</option>
                        <!-- Populated by JS -->
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendor</label>
                    <select class="form-select" name="vendor_id">
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Row 3 -->
                <div class="col-md-3">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <select class="form-select" name="unit_id" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit Value</label>
                    <input type="number" class="form-control" name="unit_value" placeholder="e.g. 500" value="{{ old('unit_value') }}" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Secondary UOM</label>
                    <input type="text" class="form-control" name="secondary_unit" placeholder="e.g. Box" value="{{ old('secondary_unit') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pack Size</label>
                    <input type="text" class="form-control" name="pack_size" placeholder="Pieces Per Box" value="{{ old('pack_size') }}">
                </div>

                <!-- Row 4 -->
                <div class="col-md-4">
                    @include('partials.barcode-field', ['value' => old('barcode'), 'productId' => null])
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="purchase_price" placeholder="0.00" value="{{ old('purchase_price') }}" step="0.01" min="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="selling_price" placeholder="0.00" value="{{ old('selling_price') }}" step="0.01" min="0" required>
                </div>

                <!-- Row 5 -->
                <div class="col-md-3">
                    <label class="form-label">Opening Stock</label>
                    <input type="number" class="form-control" name="opening_stock" placeholder="0" value="{{ old('opening_stock', 0) }}" min="0" step="any">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Opening Stock Store</label>
                    @if(auth()->user()->canViewAllStores())
                        <select class="form-select" name="store_id">
                            <option value="">Select store</option>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ (string) old('store_id', $defaultStoreId) === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="form-control" value="{{ $stores->first()->name ?? 'No store assigned' }}" disabled>
                    @endif
                    <small class="text-muted">Later changes go through Stock Adjustments.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min Stock Level</label>
                    <input type="number" class="form-control" name="min_stock_level" placeholder="Reorder Level" value="{{ old('min_stock_level') }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Stock Level</label>
                    <input type="number" class="form-control" name="max_stock_level" placeholder="Optional" value="{{ old('max_stock_level') }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Batch / Expiry</label>
                    <select class="form-select" name="batch_expiry" id="batch_expiry" onchange="toggleExpiryDate()">
                        <option value="">Batch / Expiry</option>
                        <option value="Yes" {{ old('batch_expiry') == 'Yes' ? 'selected' : '' }}>Yes</option>
                        <option value="No" {{ old('batch_expiry') == 'No' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3" id="expiry_date_div" style="display: none;">
                    <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="expiry_date" id="expiry_date" value="{{ old('expiry_date') }}">
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
                                        $isChecked = is_array(old('taxes')) && in_array($tax->id, old('taxes'));
                                        $currentRate = old('tax_rates.'.$tax->id);
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
                        <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="print_label" value="1" id="printLabel" {{ old('print_label') ? 'checked' : '' }}>
                        <label class="form-check-label" for="printLabel">Open barcode label printing after saving</label>
                    </div>
                    <div class="add-product-form__actions">
                        <button type="submit" class="themeBtn">
                            Save Product
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
                        
                        // Reselect old value if validation failed
                        var oldSubCategory = "{{ old('sub_category_id') }}";
                        if(oldSubCategory) {
                            subCategorySelect.val(oldSubCategory);
                        }
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr.responseText);
                    }
                });
            }
        });

        $('#sub_category_id').on('change', function() {
            var subCategoryId = $(this).val();
            
            // Reset taxes
            $('input[name="taxes[]"]').prop('checked', false);
            $('input[name^="tax_rates"]').val('').prop('disabled', true);

            if (subCategoryId) {
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

        // Trigger change if old value exists
        if("{{ old('category_id') }}") {
            $('#category_id').trigger('change');
        }
    });
</script>
@endpush
