@extends('layouts.app')

@section('content')

<!-- Roles Dashboard -->
<div class="roles-dashboard">
    <div class="dashboard-card">
        <div class="dashboard-content__head mb-4">
             <h2 class="heading">Roles Management</h2>
        </div>

        <!-- Transaction Ledger Section -->
        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="roleFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-filter me-2"></i> Filter
                    </button>
                    <ul class="dropdown-menu products-filter-menu" aria-labelledby="roleFilterDropdown">
                        <li><h6 class="dropdown-header">Filter by Status</h6></li>
                        <li><a class="dropdown-item active" href="javascript:void(0)" onclick="filterStatus('all')">All</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterStatus('Active')">Active</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterStatus('Inactive')">Inactive</a></li>
                    </ul>
                </div>
                <div class="transaction-ledger__controls">
                    <a href="{{ route('roles.create') }}" class="btn btn-primary ms-2" >
                        <i class="fa-solid fa-plus me-2"></i> Add Role
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="rolesTableAjax" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>S. No</th>
                            <th>Role Name</th>
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
        var table = $('#rolesTableAjax').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('roles.index') }}",
                data: function (d) {
                    d.status = window.currentStatus || 'all';
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
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
                text: "Do you want to change the status of this role?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/roles/" + id + "/toggle-status",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            table.ajax.reload(null, false);
                            Swal.fire(
                                'Updated!',
                                'Role status has been updated.',
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
