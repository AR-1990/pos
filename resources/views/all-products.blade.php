@extends('layouts.app')

@section('content')

<!-- Products Dashboard -->
<div class="products-dashboard">
    <div class="dashboard-card">
        <!-- Navigation Links -->
        <ul class="nav nav-tabs products-tabs" id="productsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <span class="nav-link active" id="all-products-tab" role="tab">
                    All Products
                </span>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ url('category') }}" class="nav-link" id="category-tab" role="tab">
                    Category
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ url('sub-category') }}" class="nav-link" id="sub-category-tab" role="tab">
                    Sub Category
                </a>
            </li>
        </ul>

        <!-- All Products Table -->
        <div class="products-table-section">
            <div class="products-table__header">
                <div class="products-table__header-left">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                            id="productFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-filter me-2"></i> Filter
                        </button>
                        <ul class="dropdown-menu products-filter-menu" aria-labelledby="productFilterDropdown">
                            <li>
                                <h6 class="dropdown-header">Filter by Status</h6>
                            </li>
                            <li><a class="dropdown-item active" href="javascript:void(0)" data-filter="all">All</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" data-filter="active">Active</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" data-filter="inactive">Inactive</a>
                            </li>
                        </ul>
                    </div>
                    <a href="{{ url('add-products') }}" class="btn btn-outline-secondary ms-2">
                        <i class="fa-solid fa-plus"></i>
                    </a>
                </div>
                <a href="" class="products-table__header-right">
                    <span class="me-2">View All</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table id="productsTable" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>Product Code</th>
                            <th>Unit</th>
                            <th>Pack Size</th>
                            <th>Barcode</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Batch / Expiry</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr>
                            <td>{{ $product->code }}</td>
                            <td>{{ $product->unit->name ?? 'N/A' }}</td>
                            <td>{{ $product->pack_size ?? '-' }}</td>
                            <td>{{ $product->barcode ?? '-' }}</td>
                            <td>{{ $globalSettings->currency ?? '$' }}{{ number_format($product->purchase_price, 2) }}</td>
                            <td>{{ $globalSettings->currency ?? '$' }}{{ number_format($product->selling_price, 2) }}</td>
                            <td>
                                @if($product->batch_expiry_enabled)
                                    {{ $product->expiry_date }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($product->status == 'Active')
                                    <span class="badge active">Active</span>
                                @else
                                    <span class="badge inActive">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('products.edit', $product->id) }}">
                                                <i class="fa-regular fa-pen-to-square me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="toggleStatus({{ $product->id }})">
                                                @if($product->status == 'Active')
                                                    <i class="fa-solid fa-ban me-2"></i> Deactivate
                                                @else
                                                    <i class="fa-solid fa-check me-2"></i> Activate
                                                @endif
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function toggleStatus(id) {
        if (confirm('Are you sure you want to change the status of this product?')) {
            $.ajax({
                url: "{{ url('products') }}/" + id + "/toggle-status",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Something went wrong. Please try again.');
                    }
                },
                error: function() {
                    alert('Error changing status.');
                }
            });
        }
    }
</script>
@endpush
