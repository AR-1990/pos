@extends('layouts.app')

@section('content')
<div class="wareHouse-dashboard">
    <div class="transaction-ledger app-card">
        <div class="transaction-ledger__header d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3">
            <div class="d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label">From date</label>
                    <input type="date" id="f_start" class="form-control">
                </div>
                <div>
                    <label class="form-label">To date</label>
                    <input type="date" id="f_end" class="form-control">
                </div>
            </div>
            @permission('stock_transfers', 'add')
            <div class="transaction-ledger__controls">
                <a href="{{ route('stock-transfers.create') }}" class="themeBtn"><i class="fa-solid fa-plus me-1"></i> New Transfer</a>
            </div>
            @endpermission
        </div>
        <div class="table-responsive">
            <table id="transfersTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Transfer #</th>
                        <th>Date</th>
                        <th>From</th>
                        <th>To</th>
                        <th class="text-end">Items</th>
                        <th class="text-end">Total Qty</th>
                        <th>By</th>
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
        var table = $('#transfersTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: {
                url: "{{ route('stock-transfers.index') }}",
                data: function (d) {
                    d.start_date = $('#f_start').val();
                    d.end_date = $('#f_end').val();
                }
            },
            columns: [
                {data: 'transfer_no', name: 'stock_transfers.transfer_no'},
                {data: 'date', name: 'stock_transfers.date'},
                {data: 'from_store', name: 'from_store', orderable: false},
                {data: 'to_store', name: 'to_store', orderable: false},
                {data: 'items_count', name: 'items_count', className: 'text-end', searchable: false},
                {data: 'total_qty', name: 'items_sum_quantity', className: 'text-end', searchable: false},
                {data: 'user_name', name: 'user_name', orderable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
        $('#f_start, #f_end').on('change', function () { table.draw(); });
    });
</script>
@endpush
