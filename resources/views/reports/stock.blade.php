@extends('layouts.app')

@section('content')
<div class="sales-report-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Stock Report</h2>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="dashboard-card card-primary">
                <h3 class="fw-bold">{{ number_format($totalStockValue, 2) }}</h3>
                <p>Total Stock Value</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card card-success">
                <h3 class="fw-bold">{{ $totalItems }}</h3>
                <p>Total Items In Stock</p>
            </div>
        </div>
    </div>

    <div class="dashboard-card">

        <!-- Date Filter Section -->
        <div class="cashbook-filter-section">
            <div class="cashbook-filter">
                <form id="filterForm" class="cashbook-filter__dates">
                    <input type="date" class="form-control" name="start_date" id="startDate"
                        value="{{ date('Y-m-01') }}">
                    <span class="cashbook-filter__to">to</span>
                    <input type="date" class="form-control" name="end_date" id="endDate" value="{{ date('Y-m-t') }}">
                    <button type="button" class="themeBtn" id="applyFilter">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>
                </form>
                <a href="{{ route('reports.stock.export') }}" id="exportBtn" class="themeBtn themeBtn--green">
                    <i class="fa-solid fa-file-csv me-2"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <h3 class="transaction-ledger__title">Stock Report</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="stockReportTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Stock Qty</th>
                            <th>Expiry Date</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Stock Value</th>
                            <th>Potential Profit</th>
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
        if ($.fn.DataTable.isDataTable('#stockReportTable')) {
            $('#stockReportTable').DataTable().destroy();
        }

        var table = $('#stockReportTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('reports.stock') }}",
            },
            columns: [{
                    data: 'code',
                    name: 'code'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'category_name',
                    name: 'category_name'
                },
                {
                    data: 'stock_quantity',
                    name: 'stock_quantity'
                },
                {
                    data: 'expiry_date',
                    name: 'expiry_date'
                },
                {
                    data: 'purchase_price',
                    name: 'purchase_price'
                },
                {
                    data: 'selling_price',
                    name: 'selling_price'
                },
                {
                    data: 'stock_value',
                    name: 'stock_value',
                    searchable: false
                },
                {
                    data: 'potential_profit',
                    name: 'potential_profit',
                    searchable: false
                }
            ],
            order: [
                [1, 'asc']
            ]
        });
    });

</script>
@endpush
@endsection
