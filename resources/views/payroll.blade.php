@extends('layouts.app')

@section('content')

<!-- Payroll Dashboard -->
<div class="payroll-dashboard">
    <p>Financial Year: {{ date('Y') }}-{{ date('Y', strtotime('+1 year')) }}</p>
    <div class="dashboard-card">
        <!-- Top Row - Key Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Salaries ({{ date('M') }})</h3>
                        <p class="metric-card__value text-primary">{{ $globalSettings->currency ?? '$' }}{{ number_format($totalSalaries, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Paid Employees</h3>
                        <p class="metric-card__value">{{ $paidEmployees }}/{{ $totalEmployees }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--red">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Pending Payment</h3>
                        <p class="metric-card__value text-danger">{{ $globalSettings->currency ?? '$' }}{{ number_format($pendingPayment, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter and Actions Section -->
        <div class="cashbook-filter-section">
            <div class="cashbook-filter">
                <form id="filterForm" class="cashbook-filter__dates">
                    <input type="date" class="form-control" id="startDate">
                    <span class="cashbook-filter__to">to</span>
                    <input type="date" class="form-control" id="endDate">
                    <select class="form-control form-select" id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                    </select>
                    <button type="button" class="themeBtn" id="applyFilter">
                        <i class="fa-solid fa-filter me-1"></i> Apply
                    </button>
                    <button type="button" class="themeBtn themeBtn--green" id="exportBtn">
                        <i class="fa-solid fa-file-csv me-1"></i> Export
                    </button>
                </form>
                <a href="{{ route('loan-and-advances.index') }}" class="themeBtn themeBtn--green">
                    <i class="fa-solid fa-dollar-sign me-1"></i> Manage Loans
                </a>
            </div>
        </div>

        <!-- Employee Salary Sheet Section -->
        <div class="expense-register">
            <div class="expense-register__header">
                <h3 class="expense-register__title">Employee Salary Sheet</h3>
                <div class="expense-register__controls">
                    <a href="{{ route('payroll.create') }}" class="btn btn-outline-secondary ms-2">
                        <i class="fa-solid fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="payrollTableAjax" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>S. No</th>
                            <th>Employee</th>
                            <th>Month</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
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
    document.addEventListener("DOMContentLoaded", function () {
        var table = $('#payrollTableAjax').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('payroll.index') }}",
                data: function (d) {
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                    d.status = $('#statusFilter').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'employee_name',
                    name: 'user.name'
                },
                {
                    data: 'salary_month',
                    name: 'salary_month'
                },
                {
                    data: 'basic_salary',
                    name: 'basic_salary'
                },
                {
                    data: 'allowances',
                    name: 'allowances'
                },
                {
                    data: 'deductions',
                    name: 'deductions',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'net_salary',
                    name: 'net_salary'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ]
        });

        $('#applyFilter').click(function () {
            table.draw();
        });

        $('#exportBtn').click(function () {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var url = "{{ route('payroll.export') }}?start_date=" + startDate + "&end_date=" +
                endDate;
            window.location.href = url;
        });
    });

    function markAsPaid(id) {
        if (!confirm('Mark this payroll as paid?')) {
            return;
        }
        var token = document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content');
        $.ajax({
            url: "{{ url('/payroll') }}/" + id + "/pay",
            type: 'POST',
            data: {
                _token: token
            },
            success: function (response) {
                alert(response.success);
                $('#payrollTableAjax').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                var message = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                alert(message);
            }
        });
    }

</script>

@endsection
