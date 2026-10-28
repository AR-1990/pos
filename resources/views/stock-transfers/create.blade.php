@extends('layouts.app')

@section('content')
<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">New Stock Transfer</h2>
        <a href="{{ route('stock-transfers.index') }}" class="back-arrow"><i class="fa-regular fa-arrow-left-long"></i></a>
    </div>

    <form id="transferForm" class="dashboard-card add-product-form" autocomplete="off">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">From store <span class="text-danger">*</span></label>
                @if($locked)
                    <input type="text" class="form-control" value="{{ $fromStores->first()->name ?? 'No store assigned' }}" disabled>
                    <input type="hidden" id="from_store_id" name="from_store_id" value="{{ $fromStores->first()->id ?? '' }}">
                @else
                    <select id="from_store_id" name="from_store_id" class="form-select" required>
                        <option value="">Select store</option>
                        @foreach($fromStores as $s)
                            <option value="{{ $s->id }}" {{ $defaultFrom == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label">To store <span class="text-danger">*</span></label>
                <select id="to_store_id" name="to_store_id" class="form-select" required>
                    <option value="">Select store</option>
                    @foreach($toStores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Note</label>
                <input type="text" name="note" class="form-control" maxlength="1000" placeholder="Optional">
            </div>

            <div class="col-12">
                <label class="form-label">Add product</label>
                <div class="position-relative">
                    <input type="text" id="productSearch" class="form-control" placeholder="Scan barcode or type name / code, Enter adds the first match">
                    <div id="productResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:20; max-height:300px; overflow:auto;"></div>
                </div>
            </div>

            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Available (from)</th>
                                <th style="width:160px;">Quantity</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemRows">
                            <tr class="empty-row"><td colspan="4" class="text-center text-muted">No products added</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12">
                <div class="add-product-form__actions">
                    <button type="submit" class="themeBtn" id="saveBtn"><i class="fa-solid fa-right-left me-1"></i> Save Transfer</button>
                    <a href="{{ route('stock-transfers.index') }}" class="themeBtn themeBtn--gray">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        var searchUrl = "{{ route('stock.product-search') }}";
        var rows = {}; // product id -> {product, qty}
        var order = [];
        var xhr, timer;

        function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
        function fromStore() { return $('#from_store_id').val(); }
        function fmt(n) { return (Math.round(n * 1000) / 1000).toString(); }

        function addProduct(p) {
            if (!fromStore()) { toast('Select the store to transfer from first', 'error'); return; }
            if (rows[p.id]) {
                rows[p.id].qty = (parseFloat(rows[p.id].qty) || 0) + 1;
                rows[p.id].product.available = p.available;
            } else {
                rows[p.id] = {product: p, qty: 1};
                order.push(p.id);
            }
            render();
            $('#itemRows tr[data-id="' + p.id + '"] .qty-input').focus().select();
        }

        function render() {
            var $tb = $('#itemRows').empty();
            if (!order.length) {
                $tb.append('<tr class="empty-row"><td colspan="4" class="text-center text-muted">No products added</td></tr>');
                return;
            }
            order.forEach(function (id) {
                var r = rows[id], p = r.product;
                var over = (parseFloat(r.qty) || 0) > p.available;
                $tb.append('<tr data-id="' + id + '" class="' + (over ? 'table-danger' : '') + '">' +
                    '<td>' + esc(p.name) + '<br><small class="text-muted">' + esc(p.code) + (p.barcode ? ' · ' + esc(p.barcode) : '') + '</small></td>' +
                    '<td class="text-end ' + (p.available <= 0 ? 'text-danger' : '') + '">' + fmt(p.available) + ' <small class="text-muted">' + esc(p.unit || '') + '</small></td>' +
                    '<td><input type="number" class="form-control form-control-sm qty-input" min="0.001" step="any" value="' + r.qty + '"></td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger row-remove"><i class="fa-solid fa-xmark"></i></button></td></tr>');
            });
        }

        function search(term, addFirst) {
            if (xhr) { xhr.abort(); }
            if (!term) { $('#productResults').empty(); return; }
            xhr = $.getJSON(searchUrl, {q: term, store_id: fromStore()}, function (list) {
                if (addFirst) {
                    $('#productResults').empty();
                    if (list.length) { addProduct(list[0]); $('#productSearch').val(''); }
                    else { toast('No product found for "' + term + '"', 'error'); }
                    return;
                }
                var $r = $('#productResults').empty();
                list.forEach(function (p) {
                    $('<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between"></button>')
                        .html('<span>' + esc(p.name) + ' <small class="text-muted">' + esc(p.code) + '</small></span><span class="badge ' + (p.available > 0 ? 'bg-success' : 'bg-danger') + '">' + fmt(p.available) + '</span>')
                        .on('click', function () { addProduct(p); $('#productSearch').val('').focus(); $r.empty(); })
                        .appendTo($r);
                });
            });
        }

        $('#productSearch').on('input', function () {
            var term = $.trim(this.value);
            clearTimeout(timer);
            timer = setTimeout(function () { search(term, false); }, 250);
        }).on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(timer);
                search($.trim(this.value), true);
            }
        });
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#productSearch, #productResults').length) { $('#productResults').empty(); }
        });

        $('#itemRows').on('input', '.qty-input', function () {
            var id = $(this).closest('tr').data('id');
            rows[id].qty = this.value;
            var over = (parseFloat(this.value) || 0) > rows[id].product.available;
            $(this).closest('tr').toggleClass('table-danger', over);
        }).on('keydown', '.qty-input', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#productSearch').focus(); }
        }).on('click', '.row-remove', function () {
            var id = $(this).closest('tr').data('id');
            delete rows[id];
            order = order.filter(function (x) { return x !== id; });
            render();
        });

        // Refresh available quantities when the source store changes
        $('#from_store_id').on('change', function () {
            if (!order.length || !fromStore()) { return; }
            $.getJSON(searchUrl, {ids: order, store_id: fromStore()}, function (list) {
                list.forEach(function (p) { if (rows[p.id]) { rows[p.id].product.available = p.available; } });
                render();
            });
        });

        $('#transferForm').on('submit', function (e) {
            e.preventDefault();
            if (!fromStore()) { toast('Select the store to transfer from', 'error'); return; }
            if (!$('#to_store_id').val()) { toast('Select the store to transfer to', 'error'); return; }
            if ($('#to_store_id').val() === fromStore()) { toast('The destination store must be different', 'error'); return; }
            if (!order.length) { toast('Add at least one product', 'error'); return; }

            var data = {
                from_store_id: fromStore(),
                to_store_id: $('#to_store_id').val(),
                date: $('[name=date]').val(),
                note: $('[name=note]').val(),
                items: []
            };
            for (var i = 0; i < order.length; i++) {
                var r = rows[order[i]], q = parseFloat(r.qty);
                if (!(q > 0)) { toast('Quantity for ' + r.product.name + ' must be greater than 0', 'error'); return; }
                data.items.push({product_id: r.product.id, quantity: q});
            }

            var $btn = $('#saveBtn').prop('disabled', true);
            $.ajax({
                url: "{{ route('stock-transfers.store') }}",
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                dataType: 'json',
                headers: {'Accept': 'application/json'}
            }).done(function (res) {
                toast(res.message, 'success');
                window.location = res.redirect;
            }).fail(function (xhr) {
                var msg = 'Could not save the transfer.';
                if (xhr.responseJSON) {
                    var errs = xhr.responseJSON.errors;
                    msg = errs ? Object.values(errs)[0][0] : (xhr.responseJSON.message || msg);
                }
                Swal.fire('Not saved', msg, 'error');
            }).always(function () { $btn.prop('disabled', false); });
        });
    });
</script>
@endpush
