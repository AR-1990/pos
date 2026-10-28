@extends('layouts.app')

@section('content')
<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Stock Ledger</h2>
        <a href="{{ route('stock.index') }}" class="themeBtn themeBtn--gray"><i class="fa-solid fa-layer-group me-1"></i> Stock Levels</a>
    </div>

    <div class="transaction-ledger app-card">
        <div class="row g-2 align-items-end mb-3" id="movementFilters">
            @if(auth()->user()->canViewAllStores())
                <div class="col-md-2">
                    <label class="form-label">Store</label>
                    <select id="f_store" class="form-select">
                        <option value="">All stores</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ session('store_filter') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">Product</label>
                <input type="text" id="f_product" class="form-control" placeholder="Name, code or barcode" value="{{ request('product') }}">
                <input type="hidden" id="f_product_id" value="{{ request('product_id') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select id="f_type" class="form-select">
                    <option value="">All types</option>
                    @foreach($types as $key => $meta)
                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $meta[0] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" id="f_start" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" id="f_end" class="form-control" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-1">
                <button type="button" id="f_reset" class="themeBtn themeBtn--gray w-100" title="Reset"><i class="fa-solid fa-rotate-left"></i></button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="movementsTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Store</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Balance</th>
                        <th>User</th>
                        <th>Reference</th>
                        <th>Note</th>
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
        var table = $('#movementsTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: {
                url: "{{ route('stock.movements') }}",
                data: function (d) {
                    d.store_id = $('#f_store').val() || '';
                    d.product = $('#f_product').val();
                    d.product_id = $('#f_product_id').val();
                    d.type = $('#f_type').val();
                    d.start_date = $('#f_start').val();
                    d.end_date = $('#f_end').val();
                }
            },
            columns: [
                {data: 'created_at', name: 'stock_movements.created_at'},
                {data: 'store_name', name: 'store_name', orderable: false},
                {data: 'product_name', name: 'product_name', orderable: false},
                {data: 'type', name: 'stock_movements.type'},
                {data: 'quantity', name: 'stock_movements.quantity', className: 'text-end', searchable: false},
                {data: 'balance_after', name: 'stock_movements.balance_after', className: 'text-end', searchable: false},
                {data: 'user_name', name: 'user_name', orderable: false},
                {data: 'reference_link', name: 'reference_link', orderable: false, searchable: false},
                {data: 'note', name: 'stock_movements.note'}
            ]
        });

        var timer;
        $('#f_product').on('input', function () {
            $('#f_product_id').val('');
            clearTimeout(timer);
            timer = setTimeout(function () { table.draw(); }, 350);
        });
        $('#f_store, #f_type, #f_start, #f_end').on('change', function () { table.draw(); });
        $('#f_reset').on('click', function () {
            $('#movementFilters').find('input, select').val('');
            table.draw();
        });
    });
</script>
@endpush
