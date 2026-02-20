@extends('layouts.app')

@section('content')

<!-- Expense Dashboard -->
<div class="expense-dashboard">
    <p>Financial Year: {{ date('Y') }}-{{ date('Y') + 1 }}</p>
    <div class="dashboard-card">
        <!-- Top Row - Key Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--red">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Expenses ({{ date('M') }})</h3>
                        <p class="metric-card__value text-danger">{{ $currency }}{{ number_format($totalExpensesMonth, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Pending Approval</h3>
                        <p class="metric-card__value text-primary">{{ $pendingCount }} Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--red">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Expenses ({{ date('Y') }})</h3>
                        <p class="metric-card__value text-danger">{{ $currency }}{{ number_format($totalExpensesYear, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Filter Section -->
        <div class="cashbook-filter-section">
            <div class="cashbook-filter">
                <div class="cashbook-filter__dates">
                    <input type="date" class="form-control" value="{{ date('Y-m-01') }}" id="startDate">
                    <span class="cashbook-filter__to">to</span>
                    <input type="date" class="form-control" value="{{ date('Y-m-d') }}" id="endDate">
                    <button type="button" class="themeBtn" id="filterBtn">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>
                </div>
                <a href="{{ route('expense.export') }}" class="themeBtn themeBtn--green" id="exportBtn">
                    <i class="fa-regular fa-file-excel"></i> Export
                </a>
            </div>
        </div>

        <!-- Expense Register Section -->
        <div class="expense-register">
            <div class="expense-register__header">
                <h3 class="expense-register__title">Expense Register</h3>
                <div class="expense-register__controls">
                    <a href="{{ route('expense.create') }}" class="btn btn-outline-secondary ms-2">
                        <i class="fa-solid fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="expenseTable" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference #</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Paid To</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Destroy existing instance if any (from custom.js)
        if ($.fn.DataTable.isDataTable('#expenseTable')) {
            $('#expenseTable').DataTable().destroy();
        }

        var table = $('#expenseTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('expense.index') }}",
                data: function (d) {
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                }
            },
            columns: [
                {data: 'date', name: 'date'},
                {data: 'reference_no', name: 'reference_no'},
                {data: 'category', name: 'expenseHead.name'},
                {data: 'description', name: 'description'},
                {data: 'paid_to', name: 'paid_to'},
                {data: 'amount', name: 'amount'},
                {data: 'status', name: 'status'}
            ],
            order: [[0, 'desc']]
        });

        $('#filterBtn').on('click', function() {
            table.draw();
        });

        $('#exportBtn').on('click', function(e) {
            e.preventDefault();
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var url = "{{ route('expense.export') }}?start_date=" + startDate + "&end_date=" + endDate;
            window.location.href = url;
        });
    });
</script>
@endpush

@endsection