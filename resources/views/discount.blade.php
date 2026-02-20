@extends('layouts.app')

@section('content')

<!-- Discount Dashboard -->
<div class="discount-dashboard">
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
                <a class="nav-link active" href="{{ url('discount') }}">Discount</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('vendor') }}">Vendor</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('customer') }}">Customer</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('bank') }}">Bank</a>
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
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="discountFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-filter me-2"></i> Filter
                    </button>
                    <ul class="dropdown-menu products-filter-menu" aria-labelledby="discountFilterDropdown">
                        <li><h6 class="dropdown-header">Filter by Status</h6></li>
                        <li><a class="dropdown-item active" href="javascript:void(0)" data-filter="all">All</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" data-filter="Active">Active</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" data-filter="Inactive">Inactive</a></li>
                    </ul>
                </div>
                <div class="transaction-ledger__controls">
                    <a href="{{ route('discount.create') }}" class="btn btn-outline-secondary ms-2">
                        <i class="fa-solid fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="table-responsive">
            <table id="discountTableAjax" class="table table-hover tables">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data loaded via Ajax -->
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        var table = $('#discountTableAjax').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('discount.index') }}",
                data: function (d) {
                    d.status = window.currentStatus || 'all';
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'amount', name: 'amount'},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        // Handle filter click
        $('.dropdown-item[data-filter]').on('click', function() {
            window.currentStatus = $(this).data('filter');
            $('.dropdown-item[data-filter]').removeClass('active');
            $(this).addClass('active');
            table.draw();
        });
    });

    function toggleStatus(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to change the status?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, change it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/discount/" + id + "/toggle-status",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Updated!',
                            'Status has been updated.',
                            'success'
                        );
                        $('#discountTableAjax').DataTable().ajax.reload();
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
    }
</script>

@endsection
