@extends('layouts.app')

@section('content')
<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">New Stock Adjustment</h2>
        <a href="{{ route('stock-adjustments.index') }}" class="back-arrow"><i class="fa-regular fa-arrow-left-long"></i></a>
    </div>

    <form id="adjustmentForm" class="dashboard-card add-product-form" autocomplete="off">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Store <span class="text-danger">*</span></label>
                @if($locked)
                    <input type="text" class="form-control" value="{{ $stores->first()->name ?? 'No store assigned' }}" disabled>
                    <input type="hidden" id="store_id" value="{{ $stores->first()->id ?? '' }}">
                @else
                    <select id="store_id" class="form-select" required>
                        <option value="">Select store</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $defaultStore == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label">Reason <span class="text-danger">*</span></label>
                <select id="reason" class="form-select" required>
                    @foreach($reasons as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date</label>
                <input type="date" id="date" class="form-control" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Note</label>
                <input type="text" id="note" class="form-control" maxlength="1000" placeholder="Optional">
            </div>

            <div class="col-12">
                <div class="alert alert-info py-2 mb-0" id="reasonHelp"></div>
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
                                <th class="text-end">Current stock</th>
                                <th class="col-direction" style="width:140px;">Direction</th>
                                <th style="width:160px;" id="qtyHeader">Quantity</th>
                                <th class="text-end" style="width:120px;">Change</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemRows"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-12">
                <div class="add-product-form__actions">
                    <button type="submit" class="themeBtn" id="saveBtn"><i class="fa-solid fa-sliders me-1"></i> Save Adjustment</button>
                    <a href="{{ route('stock-adjustments.index') }}" class="themeBtn themeBtn--gray">Cancel</a>
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
        var rows = {}; // product id -> {product, qty, direction}
        var order = [];
        var xhr, timer;
        var help = {
            count: 'Enter the quantity you physically counted. The difference from current stock is posted (rows with no difference are skipped).',
            other: 'Choose whether each line adds or removes stock, then enter the quantity.',
            _default: 'Enter the quantity to remove from stock.'
        };

        function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
        function storeId() { return $('#store_id').val(); }
        function reason() { return $('#reason').val(); }
        function fmt(n) { return (Math.round(n * 1000) / 1000).toString(); }

        function change(r) {
            var q = parseFloat(r.qty);
            if (isNaN(q)) { return 0; }
            if (reason() === 'count') { return q - r.product.available; }
            if (reason() === 'other' && r.direction === 'add') { return q; }
            return -q;
        }

        function changeHtml(r) {
            var c = Math.round(change(r) * 1000) / 1000;
            if (c > 0) { return '<span class="text-success fw-semibold">+' + fmt(c) + '</span>'; }
            if (c < 0) { return '<span class="text-danger fw-semibold">' + fmt(c) + '</span>'; }
            return '<span class="text-muted">0</span>';
        }

        function addProduct(p) {
            if (!storeId()) { toast('Select a store first', 'error'); return; }
            if (rows[p.id]) {
                if (reason() !== 'count') { rows[p.id].qty = (parseFloat(rows[p.id].qty) || 0) + 1; }
                rows[p.id].product.available = p.available;
            } else {
                rows[p.id] = {product: p, qty: reason() === 'count' ? fmt(p.available) : 1, direction: 'remove'};
                order.push(p.id);
            }
            render();
            $('#itemRows tr[data-id="' + p.id + '"] .qty-input').focus().select();
        }

        function render() {
            var isCount = reason() === 'count', isOther = reason() === 'other';
            $('#reasonHelp').text(help[reason()] || help._default);
            $('#qtyHeader').text(isCount ? 'Counted quantity' : (isOther ? 'Quantity' : 'Quantity to remove'));
            $('.col-direction').toggle(isOther);

            var $tb = $('#itemRows').empty();
            if (!order.length) {
                $tb.append('<tr class="empty-row"><td colspan="6" class="text-center text-muted">No products added</td></tr>');
                return;
            }
            order.forEach(function (id) {
                var r = rows[id], p = r.product;
                var dir = '<td class="col-direction"' + (isOther ? '' : ' style="display:none"') + '><select class="form-select form-select-sm dir-input">' +
                    '<option value="remove"' + (r.direction === 'remove' ? ' selected' : '') + '>Remove</option>' +
                    '<option value="add"' + (r.direction === 'add' ? ' selected' : '') + '>Add</option></select></td>';
                $tb.append('<tr data-id="' + id + '">' +
                    '<td>' + esc(p.name) + '<br><small class="text-muted">' + esc(p.code) + (p.barcode ? ' · ' + esc(p.barcode) : '') + '</small></td>' +
                    '<td class="text-end">' + fmt(p.available) + ' <small class="text-muted">' + esc(p.unit || '') + '</small></td>' +
                    dir +
                    '<td><input type="number" class="form-control form-control-sm qty-input" min="0" step="any" value="' + r.qty + '"></td>' +
                    '<td class="text-end change-cell">' + changeHtml(r) + '</td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger row-remove"><i class="fa-solid fa-xmark"></i></button></td></tr>');
            });
        }

        function refreshAvailable() {
            if (!order.length || !storeId()) { render(); return; }
            $.getJSON(searchUrl, {ids: order, store_id: storeId()}, function (list) {
                list.forEach(function (p) { if (rows[p.id]) { rows[p.id].product.available = p.available; } });
                render();
            });
        }

        function search(term, addFirst) {
            if (xhr) { xhr.abort(); }
            if (!term) { $('#productResults').empty(); return; }
            xhr = $.getJSON(searchUrl, {q: term, store_id: storeId()}, function (list) {
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
            var $tr = $(this).closest('tr'), id = $tr.data('id');
            rows[id].qty = this.value;
            $tr.find('.change-cell').html(changeHtml(rows[id]));
        }).on('change', '.dir-input', function () {
            var $tr = $(this).closest('tr'), id = $tr.data('id');
            rows[id].direction = this.value;
            $tr.find('.change-cell').html(changeHtml(rows[id]));
        }).on('keydown', '.qty-input', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#productSearch').focus(); }
        }).on('click', '.row-remove', function () {
            var id = $(this).closest('tr').data('id');
            delete rows[id];
            order = order.filter(function (x) { return x !== id; });
            render();
        });

        $('#store_id').on('change', refreshAvailable);
        $('#reason').on('change', function () {
            if (reason() === 'count') {
                order.forEach(function (id) { rows[id].qty = fmt(rows[id].product.available); });
            }
            render();
        });

        $('#adjustmentForm').on('submit', function (e) {
            e.preventDefault();
            if (!storeId()) { toast('Select a store', 'error'); return; }
            if (!order.length) { toast('Add at least one product', 'error'); return; }

            var data = {store_id: storeId(), reason: reason(), date: $('#date').val(), note: $('#note').val(), items: []};
            var nonZero = 0;
            for (var i = 0; i < order.length; i++) {
                var r = rows[order[i]], q = parseFloat(r.qty);
                if (isNaN(q) || q < 0) { toast('Enter a valid quantity for ' + r.product.name, 'error'); return; }
                if (Math.abs(change(r)) > 0.0005) { nonZero++; }
                data.items.push({product_id: r.product.id, quantity: q, direction: r.direction});
            }
            if (!nonZero) { toast('Nothing to adjust: every line has zero change', 'error'); return; }

            var $btn = $('#saveBtn').prop('disabled', true);
            $.ajax({
                url: "{{ route('stock-adjustments.store') }}",
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                dataType: 'json',
                headers: {'Accept': 'application/json'}
            }).done(function (res) {
                toast(res.message, 'success');
                window.location = res.redirect;
            }).fail(function (xhr) {
                var msg = 'Could not save the adjustment.';
                if (xhr.responseJSON) {
                    var errs = xhr.responseJSON.errors;
                    msg = errs ? Object.values(errs)[0][0] : (xhr.responseJSON.message || msg);
                }
                Swal.fire('Not saved', msg, 'error');
            }).always(function () { $btn.prop('disabled', false); });
        });

        render();
    });
</script>
@endpush
