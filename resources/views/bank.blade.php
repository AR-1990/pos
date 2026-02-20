@extends('layouts.app')

@section('content')

<!-- Bank Dashboard -->
<div class="bank-dashboard">
    <div class="dashboard-card">
        <!-- Top Navigation Tabs -->
        <ul class="nav products-tabs">
            <li class="nav-item">
                <a class="nav-link" href="{{ url('taxes') }}">Taxes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('product-category') }}">Product Category</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('measurement-unit') }}">Measurement Unit</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('ware-house') }}">Ware House</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('discount') }}">Discount</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('vendor') }}">Vendor</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('customer') }}">Customer</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ url('bank') }}">Bank</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('expense-head') }}">Expense Head</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('cashbook-head') }}">Cashbook Head</a>
            </li>
        </ul>

        <!-- Transaction Ledger Section -->
        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="bankFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-filter me-2"></i> Filter
                    </button>
                    <ul class="dropdown-menu products-filter-menu" aria-labelledby="bankFilterDropdown">
                        <li><h6 class="dropdown-header">Filter by Status</h6></li>
                        <li><a class="dropdown-item active" href="javascript:void(0)" onclick="filterStatus('all')">All</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterStatus('Active')">Active</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="filterStatus('Inactive')">Inactive</a></li>
                    </ul>
                </div>
                <div class="transaction-ledger__controls">
                    <a href="{{ route('bank.create') }}" class="btn btn-outline-secondary ms-2" >
                        <i class="fa-solid fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="bankTableAjax" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>S. No</th>
                            <th>Bank Name</th>
                            <th>Account Title</th>
                            <th>Account Number</th>
                            <th>Branch Code</th>
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
        var table = $('#bankTableAjax').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('bank.index') }}",
                data: function (d) {
                    d.status = window.currentStatus || 'all';
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'account_title', name: 'account_title'},
                {data: 'account_number', name: 'account_number'},
                {data: 'branch_code', name: 'branch_code'},
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
                text: "Do you want to change the status of this bank?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/bank/" + id + "/toggle-status",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            table.ajax.reload(null, false); // Reload without resetting pagination
                            Swal.fire(
                                'Updated!',
                                'Bank status has been updated.',
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
