@extends('layouts.app')

@section('content')

<!-- Cashbook Dashboard -->
<div class="cashbook-dashboard">
    <p>Financial Year: {{ date('Y') }}-{{ date('Y', strtotime('+1 year')) }}</p>
    <div class="dashboard-card">
        <!-- Top Row - Key Metrics -->
        <div class="row g-3">
            <div class="col-xxl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon">
                        <i class="fa-regular fa-wallet"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Opening Balance</h3>
                        <p class="metric-card__value">{{ number_format($openingBalance, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--green">
                        <i class="fa-regular fa-arrow-down"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Receipts (Debit)</h3>
                        <p class="metric-card__value text-success">+{{ number_format($totalReceipts, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--red">
                        <i class="fa-regular fa-arrow-up"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Payments (Credit)</h3>
                        <p class="metric-card__value text-danger">-{{ number_format($totalPayments, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon">
                        <i class="fa-regular fa-scale-balanced"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Closing Balance</h3>
                        <p class="metric-card__value">{{ number_format($closingBalance, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Filter Section -->
        <div class="cashbook-filter-section">
            <div class="cashbook-filter">
                <form id="filterForm" class="cashbook-filter__dates">
                    <input type="date" class="form-control" name="start_date" id="startDate" value="{{ date('Y-m-01') }}">
                    <span class="cashbook-filter__to">to</span>
                    <input type="date" class="form-control" name="end_date" id="endDate" value="{{ date('Y-m-t') }}">
                    <button type="button" class="themeBtn" id="applyFilter">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>
                </form>
                <button type="button" class="themeBtn themeBtn--green" id="exportBtn">
                    <i class="fa-solid fa-file-export"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Transaction Ledger Section -->
        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <h3 class="transaction-ledger__title">Transaction Ledger</h3>
                <div class="transaction-ledger__controls">
                    <a href="{{ route('cashbook.create') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-plus"></i> New Transaction
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="cashbookTable" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Voucher #</th>
                            <th>Description</th>
                            <th>Head</th>
                            <th>Bank Name</th>
                            <th>Debit (In)</th>
                            <th>Credit (Out)</th>
                            <th>Mode</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via Ajax -->
                    </tbody>
                    <tfoot>
                        <!-- Footer totals can be added here if needed -->
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#cashbookTable')) {
            $('#cashbookTable').DataTable().destroy();
        }

        var table = $('#cashbookTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('cashbook.index') }}",
                data: function (d) {
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                }
            },
            columns: [
                {data: 'transaction_date', name: 'transaction_date'},
                {data: 'voucher_no', name: 'voucher_no'},
                {data: 'description', name: 'description'},
                {data: 'cashbook_head.name', name: 'cashbookHead.name', defaultContent: '-'},
                {data: 'bank_name', name: 'bank_name'},
                {data: 'debit', name: 'debit', className: 'text-success'},
                {data: 'credit', name: 'credit', className: 'text-danger'},
                {data: 'payment_mode', name: 'payment_mode'},
                {data: 'remarks', name: 'remarks'}
            ],
            order: [[0, 'desc']] // Sort by date descending by default
        });

        $('#applyFilter').on('click', function() {
            table.draw();
            var start = $('#startDate').val();
            var end = $('#endDate').val();
            window.location.href = "{{ route('cashbook.index') }}?start_date=" + start + "&end_date=" + end;
        });

        $('#exportBtn').on('click', function() {
            var start = $('#startDate').val();
            var end = $('#endDate').val();
            var url = "{{ route('cashbook.export') }}?start_date=" + start + "&end_date=" + end;
            window.location.href = url;
        });
    });
</script>
@endpush

@endsection
