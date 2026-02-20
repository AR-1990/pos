@extends('layouts.app')

@section('content')
<div class="sales-report-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Sales Report</h2>
    </div>

    <div class="dashboard-card">
        <div class="cashbook-filter-section">
            <div class="cashbook-filter">
                <form id="filterForm" class="cashbook-filter__dates">
                    <input type="date" id="startDate" name="start_date" class="form-control" value="{{ $startDate }}">
                    <input type="date" id="endDate" name="end_date" class="form-control" value="{{ $endDate }}">
                    <button type="button" id="filterBtn" class="themeBtn">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <button type="button" id="resetBtn" class="themeBtn themeBtn--gray">Reset</button>
                </form>
                <a href="{{ route('reports.sales.export') }}" id="exportBtn" class="themeBtn themeBtn--green">
                    <i class="fa-solid fa-file-csv me-2"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <h3 class="transaction-ledger__title">Sales Report</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="salesReportTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Payment Method</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Grand Total</th>
                            <th>Action</th>
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
        if ($.fn.DataTable.isDataTable('#salesReportTable')) {
            $('#salesReportTable').DataTable().destroy();
        }

        var table = $('#salesReportTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('reports.sales') }}",
                data: function (d) {
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                }
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'customer_name',
                    name: 'customer_name',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'payment_method',
                    name: 'payment_method'
                },
                {
                    data: 'subtotal',
                    name: 'subtotal'
                },
                {
                    data: 'discount',
                    name: 'discount'
                },
                {
                    data: 'tax',
                    name: 'tax'
                },
                {
                    data: 'grand_total',
                    name: 'grand_total'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [0, 'desc']
            ]
        });

        $('#filterBtn').on('click', function () {
            table.draw();
            updateExportLink();
        });

        $('#resetBtn').on('click', function () {
            window.location.href = "{{ route('reports.sales') }}";
        });

        function updateExportLink() {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var url = "{{ route('reports.sales.export') }}";
            $('#exportBtn').attr('href', url + '?start_date=' + startDate + '&end_date=' + endDate);
        }

        // Initial update of export link
        updateExportLink();
    });

</script>
@endpush
@endsection
