@extends('layouts.app')

@section('content')
<div class="wareHouse-dashboard">
    <div class="transaction-ledger app-card">
        <div class="transaction-ledger__header d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3">
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label">Reason</label>
                    <select id="f_reason" class="form-select">
                        <option value="">All reasons</option>
                        @foreach($reasons as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">From date</label>
                    <input type="date" id="f_start" class="form-control">
                </div>
                <div>
                    <label class="form-label">To date</label>
                    <input type="date" id="f_end" class="form-control">
                </div>
            </div>
            @permission('stock_adjustments', 'add')
            <div class="transaction-ledger__controls">
                <a href="{{ route('stock-adjustments.create') }}" class="themeBtn"><i class="fa-solid fa-plus me-1"></i> New Adjustment</a>
            </div>
            @endpermission
        </div>
        <div class="table-responsive">
            <table id="adjustmentsTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Adjustment #</th>
                        <th>Date</th>
                        <th>Store</th>
                        <th>Reason</th>
                        <th class="text-end">Items</th>
                        <th class="text-end">Qty change</th>
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
        var table = $('#adjustmentsTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: {
                url: "{{ route('stock-adjustments.index') }}",
                data: function (d) {
                    d.reason = $('#f_reason').val();
                    d.start_date = $('#f_start').val();
                    d.end_date = $('#f_end').val();
                }
            },
            columns: [
                {data: 'adjustment_no', name: 'stock_adjustments.adjustment_no'},
                {data: 'date', name: 'stock_adjustments.date'},
                {data: 'store_name', name: 'store_name', orderable: false},
                {data: 'reason', name: 'stock_adjustments.reason'},
                {data: 'items_count', name: 'items_count', className: 'text-end', searchable: false},
                {data: 'qty_change', name: 'qty_change', className: 'text-end', orderable: false, searchable: false},
                {data: 'user_name', name: 'user_name', orderable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
        $('#f_reason, #f_start, #f_end').on('change', function () { table.draw(); });
    });
</script>
@endpush
