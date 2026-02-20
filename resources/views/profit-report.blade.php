@extends('layouts.app')

@section('content')
<div class="report-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Profit & Loss Report</h2>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title opacity-75">Revenue</h5>
                    <h3 class="card-text fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($totalRevenue, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title opacity-75">COGS</h5>
                    <h3 class="card-text fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($totalCOGS, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title opacity-75">Gross Profit</h5>
                    <h3 class="card-text fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($grossProfit, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title opacity-75">Expenses</h5>
                    <h3 class="card-text fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($totalAllExpenses, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card {{ $netProfit >= 0 ? 'bg-success' : 'bg-danger' }} text-white h-100">
                <div class="card-body">
                    <h5 class="card-title opacity-75">Net Profit</h5>
                    <h3 class="card-text fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($netProfit, 2) }}</h3>
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
                <a href="{{ route('reports.profit.export') }}" id="exportBtn" class="themeBtn themeBtn--green">
                    <i class="fa-solid fa-file-csv me-2"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <h3 class="transaction-ledger__title">Profit & Loss Report</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="profitReportTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Revenue</th>
                            <th>COGS</th>
                            <th>Gross Profit</th>
                            <th>Expenses</th>
                            <th>Net Profit</th>
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
        if ($.fn.DataTable.isDataTable('#profitReportTable')) {
            $('#profitReportTable').DataTable().destroy();
        }

        var table = $('#profitReportTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('reports.profit') }}",
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
                    data: 'revenue',
                    name: 'revenue',
                    searchable: false
                },
                {
                    data: 'cogs',
                    name: 'cogs',
                    searchable: false
                },
                {
                    data: 'gross_profit',
                    name: 'gross_profit',
                    searchable: false
                },
                {
                    data: 'expenses',
                    name: 'expenses',
                    searchable: false
                },
                {
                    data: 'net_profit',
                    name: 'net_profit',
                    searchable: false
                }
            ],
            order: [
                [0, 'desc']
            ]
        });

        $('#resetBtn').on('click', function () {
            window.location.href = "{{ route('reports.profit') }}";
        });

        function updateExportLink() {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var url = "{{ route('reports.profit.export') }}";
            $('#exportBtn').attr('href', url + '?start_date=' + startDate + '&end_date=' + endDate);
        }

        $('#startDate, #endDate').on('change', updateExportLink);
        updateExportLink();
    });

</script>
@endpush
@endsection
