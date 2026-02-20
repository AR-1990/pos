@extends('layouts.app')

@section('content')
<div class="report-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Revenue Report</h2>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title opacity-75">Gross Sales</h5>
                    <h2 class="card-text fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($totalSales, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h5 class="card-title opacity-75">Returns</h5>
                    <h2 class="card-text fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($totalReturns, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title opacity-75">Net Revenue</h5>
                    <h2 class="card-text fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($netRevenue, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>


    <div class="dashboard-card">
        <div class="cashbook-filter-section">
            <div class="cashbook-filter">
                <form id="filterForm" class="cashbook-filter__dates">
                    <input type="date" id="startDate" name="start_date" class="form-control" value="{{ $startDate }}">
                    <input type="date" id="endDate" name="end_date" class="form-control" value="{{ $endDate }}">
                    <button type="submit" class="themeBtn"><i class="fa-solid fa-filter"></i> Filter</button>
                    <button type="button" id="resetBtn" class="themeBtn themeBtn--gray">Reset</button>
                </form>
                <a href="{{ route('reports.revenue.export') }}" id="exportBtn" class="themeBtn themeBtn--green">
                    <i class="fa-solid fa-file-csv me-2"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <h3 class="transaction-ledger__title">Revenue Report</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="revenueReportTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders (Completed)</th>
                            <th>Gross Sales</th>
                            <th>Returns</th>
                            <th>Net Revenue</th>
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
    $(document).ready(function () {
        if ($.fn.DataTable.isDataTable('#revenueReportTable')) {
            $('#revenueReportTable').DataTable().destroy();
        }

        var table = $('#revenueReportTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('reports.revenue') }}",
                data: function (d) {
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                }
            },
            columns: [{
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'orders',
                    name: 'orders',
                    searchable: false
                },
                {
                    data: 'gross_sales',
                    name: 'gross_sales',
                    searchable: false
                },
                {
                    data: 'returns',
                    name: 'returns',
                    searchable: false
                },
                {
                    data: 'net_revenue',
                    name: 'net_revenue',
                    searchable: false
                }
            ],
            order: [
                [0, 'desc']
            ]
        });

        // Use form submit for summary card update (page reload)
        // If we want AJAX update for cards too, we'd need separate endpoint or response
        // For now, reload on submit is fine for cards + table

        $('#resetBtn').on('click', function () {
            window.location.href = "{{ route('reports.revenue') }}";
        });

        function updateExportLink() {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var url = "{{ route('reports.revenue.export') }}";
            $('#exportBtn').attr('href', url + '?start_date=' + startDate + '&end_date=' + endDate);
        }

        $('#startDate, #endDate').on('change', updateExportLink);
        updateExportLink();
    });

</script>
@endpush
@endsection
