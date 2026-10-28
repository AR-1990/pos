@extends('layouts.app')

@section('content')

<div class="wareHouse-dashboard">
    <div class="dashboard-card">
        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="storeFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-filter me-2"></i> Filter
                    </button>
                    <ul class="dropdown-menu products-filter-menu" aria-labelledby="storeFilterDropdown">
                        <li><h6 class="dropdown-header">Filter by Status</h6></li>
                        <li><a class="dropdown-item active" href="javascript:void(0)" onclick="filterStatus('all')">All</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterStatus('Active')">Active</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterStatus('Inactive')">Inactive</a></li>
                    </ul>
                </div>
                @permission('stores', 'add')
                <div class="transaction-ledger__controls">
                    <a href="{{ route('stores.create') }}" class="themeBtn">
                        <i class="fa-solid fa-plus me-1"></i> Add Store
                    </a>
                </div>
                @endpermission
            </div>
            <div class="table-responsive">
                <table id="storesTableAjax" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>S. No</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Phone</th>
                            <th>Staff</th>
                            <th>Today's Sale</th>
                            <th>Total Sale</th>
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
        var table = $('#storesTableAjax').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('stores.index') }}",
                data: function (d) {
                    d.status = window.currentStatus || 'all';
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'code', name: 'code'},
                {data: 'phone', name: 'phone'},
                {data: 'users_count', name: 'users_count', searchable: false},
                {data: 'today_sales', name: 'today_sales', searchable: false},
                {data: 'total_sales', name: 'total_sales', searchable: false},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        window.filterStatus = function(status) {
            window.currentStatus = status;
            table.draw();

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
                text: "Do you want to change the status of this store?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/stores/" + id + "/toggle-status",
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function() {
                            table.ajax.reload(null, false);
                            Swal.fire('Updated!', 'Store status has been updated.', 'success');
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        };
    });
</script>

@endsection
