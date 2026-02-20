@extends('layouts.app')

@section('content')

<!-- Products Dashboard -->
<div class="products-dashboard">
    <div class="dashboard-card">
        <!-- Navigation Links -->
        <ul class="nav nav-tabs products-tabs" id="productsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="{{ url('all-products') }}" class="nav-link" id="all-products-tab" role="tab">
                    All Products
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ url('category') }}" class="nav-link" id="category-tab" role="tab">
                    Category
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <span class="nav-link active" id="sub-category-tab" role="tab">
                    Sub Category
                </span>
            </li>
        </ul>

        <!-- Sub Category Table -->
        <div class="products-table-section">
            <div class="products-table__header">
                <div class="products-table__header-left">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                            id="subCategoryFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-filter me-2"></i> Filter
                        </button>
                        <ul class="dropdown-menu products-filter-menu" aria-labelledby="subCategoryFilterDropdown">
                            <li>
                                <h6 class="dropdown-header">Filter by Status</h6>
                            </li>
                            <li><a class="dropdown-item active" href="javascript:void(0)" onclick="filterStatus('all')">All</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterStatus('Active')">Active</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterStatus('Inactive')">Inactive</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('sub-category.create') }}" class="btn btn-outline-secondary ms-2">
                        <i class="fa-solid fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="subCategoryTableAjax" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>S. No</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Taxes</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var table = $('#subCategoryTableAjax').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sub-category.index') }}",
                data: function (d) {
                    d.status = window.currentStatus || 'all';
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'code', name: 'code'},
                {data: 'name', name: 'name'},
                {data: 'category_name', name: 'category.name'},
                {data: 'taxes', name: 'taxes', orderable: false, searchable: false},
                {data: 'description', name: 'description'},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        window.filterStatus = function(status) {
            window.currentStatus = status;
            table.draw();
            
            // Update active class in dropdown
            $('.products-filter-menu .dropdown-item').removeClass('active');
            $('.products-filter-menu .dropdown-item').each(function() {
                if ($(this).text() === (status === 'all' ? 'All' : status)) {
                    $(this).addClass('active');
                }
            });
        };

        window.toggleStatus = function(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to change the status of this sub category?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/sub-category/" + id + "/toggle-status",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            table.ajax.reload(null, false); // Reload without resetting pagination
                            Swal.fire(
                                'Updated!',
                                'Sub Category status has been updated.',
                                'success'
                            );
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Something went wrong.',
                                'error'
                            );
                        }
                    });
                }
            });
        };
    });
</script>

@endsection
