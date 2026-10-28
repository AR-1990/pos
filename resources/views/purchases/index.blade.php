@extends('layouts.app')

@section('content')

<div class="wareHouse-dashboard">
    <div class="dashboard-content__head d-flex justify-content-between align-items-center">
        <h2 class="heading mb-0">Purchases</h2>
        @permission('purchases', 'add')
        <a href="{{ route('purchases.create') }}" class="themeBtn">
            <i class="fa-solid fa-plus me-1"></i> New Purchase
        </a>
        @endpermission
    </div>

    <div class="transaction-ledger">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-sm-6 col-md-3">
                <label class="form-label" for="fromDate">From</label>
                <input type="date" id="fromDate" class="form-control">
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label" for="toDate">To</label>
                <input type="date" id="toDate" class="form-control">
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label" for="statusFilter">Payment status</label>
                <select id="statusFilter" class="form-select">
                    <option value="">All</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3 d-flex gap-2">
                <button type="button" id="applyFilters" class="themeBtn"><i class="fa-solid fa-filter me-1"></i> Apply</button>
                <button type="button" id="resetFilters" class="themeBtn themeBtn--gray">Reset</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="purchasesTable" class="table table-hover tables w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Purchase No</th>
                        <th>Date</th>
                        <th>Store</th>
                        <th>Vendor</th>
                        <th>Vendor Inv.</th>
                        <th>Grand Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(function () {
        var table = $('#purchasesTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[2, 'desc']],
            ajax: {
                url: @json(route('purchases.index')),
                data: function (d) {
                    d.from_date = $('#fromDate').val();
                    d.to_date = $('#toDate').val();
                    d.payment_status = $('#statusFilter').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'purchase_no', name: 'purchases.purchase_no'},
                {data: 'date', name: 'date', searchable: false},
                {data: 'store_name', name: 'store_name', orderable: false},
                {data: 'vendor_name', name: 'vendor_name', orderable: false},
                {data: 'vendor_invoice_no', name: 'purchases.vendor_invoice_no'},
                {data: 'grand_total', name: 'purchases.grand_total', searchable: false},
                {data: 'paid_amount', name: 'purchases.paid_amount', searchable: false},
                {data: 'due_amount', name: 'purchases.due_amount', searchable: false},
                {data: 'payment_status', name: 'purchases.payment_status', searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('#applyFilters').on('click', function () { table.draw(); });
        $('#statusFilter').on('change', function () { table.draw(); });
        $('#resetFilters').on('click', function () {
            $('#fromDate, #toDate, #statusFilter').val('');
            table.draw();
        });
    });
</script>
@endpush
