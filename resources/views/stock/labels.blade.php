@extends('layouts.app')

@section('content')
<style>
    .label-sheet { display: flex; flex-wrap: wrap; gap: 6px; }
    .barcode-label {
        width: 50mm; height: 30mm; border: 1px dashed #CBD5E1; border-radius: 4px; background: #fff;
        padding: 1.5mm 2mm; display: flex; flex-direction: column; align-items: center; justify-content: space-between;
        overflow: hidden; page-break-inside: avoid; break-inside: avoid; color: #000;
    }
    .barcode-label__name { font-size: 9pt; font-weight: 600; line-height: 1.1; text-align: center; max-height: 2.2em; overflow: hidden; width: 100%; }
    .barcode-label__price { font-size: 10pt; font-weight: 700; }
    .barcode-label svg { max-width: 100%; height: 14mm; }
    .label-row-qty { width: 90px; }

    @media print {
        @page { margin: 5mm; }
        body * { visibility: hidden !important; }
        #labelSheet, #labelSheet * { visibility: visible !important; }
        #labelSheet { position: absolute; left: 0; top: 0; width: 100%; gap: 2mm; }
        .barcode-label { border: 1px solid #ddd; }
        body.labels-no-border .barcode-label { border: 0; }
    }
</style>

<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Barcode Labels</h2>
        <a href="{{ route('all-products') }}" class="back-arrow"><i class="fa-regular fa-arrow-left-long"></i></a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="app-card">
                <label class="form-label">Find product</label>
                <div class="position-relative">
                    <input type="text" id="labelSearch" class="form-control" placeholder="Scan barcode or type name / code, Enter to add" autocomplete="off" autofocus>
                    <div id="labelResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:20; max-height:280px; overflow:auto;"></div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Product</th><th>Copies</th><th></th></tr></thead>
                        <tbody id="labelRows">
                            <tr class="empty-row"><td colspan="3" class="text-muted text-center">No products selected</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <label class="form-label">Label size</label>
                        <select id="labelSize" class="form-select">
                            <option value="50x30">50 × 30 mm</option>
                            <option value="38x25">38 × 25 mm</option>
                            <option value="60x40">60 × 40 mm</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Show</label>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="showPrice" checked><label class="form-check-label" for="showPrice">Price</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="showBorder" checked><label class="form-check-label" for="showBorder">Border when printing</label></div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="themeBtn" id="printLabels"><i class="fa-solid fa-print me-1"></i> Print</button>
                    <button type="button" class="themeBtn themeBtn--gray" id="clearLabels">Clear</button>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="app-card">
                <div class="d-flex justify-content-between mb-2">
                    <strong>Preview</strong>
                    <small class="text-muted" id="labelCount">0 labels</small>
                </div>
                <div id="labelSheet" class="label-sheet"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
    $(function () {
        var currency = (window.APP && APP.currency) || 'Rs';
        var items = {}; // id -> {product, copies}
        var order = [];
        var searchXhr, timer;

        function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

        function addProduct(p, copies) {
            if (items[p.id]) {
                items[p.id].copies += copies || 1;
            } else {
                items[p.id] = {product: p, copies: copies || 1};
                order.push(p.id);
            }
            renderRows();
            renderSheet();
        }

        function renderRows() {
            var $tb = $('#labelRows').empty();
            if (!order.length) {
                $tb.append('<tr class="empty-row"><td colspan="3" class="text-muted text-center">No products selected</td></tr>');
                return;
            }
            order.forEach(function (id) {
                var it = items[id];
                $tb.append('<tr data-id="' + id + '"><td>' + esc(it.product.name) + '<br><small class="text-muted">' + esc(it.product.barcode || it.product.code) + '</small></td>' +
                    '<td><input type="number" min="1" max="500" class="form-control form-control-sm label-row-qty" value="' + it.copies + '"></td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger label-remove"><i class="fa-solid fa-xmark"></i></button></td></tr>');
            });
        }

        function renderSheet() {
            var size = $('#labelSize').val().split('x');
            var $sheet = $('#labelSheet').empty();
            var showPrice = $('#showPrice').is(':checked');
            var total = 0;
            order.forEach(function (id) {
                var it = items[id], p = it.product;
                var value = p.barcode || p.code;
                for (var i = 0; i < it.copies; i++) {
                    total++;
                    var $l = $('<div class="barcode-label"></div>').css({width: size[0] + 'mm', height: size[1] + 'mm'});
                    $l.append('<div class="barcode-label__name">' + esc(p.name) + '</div>');
                    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    $l.append(svg);
                    if (showPrice) {
                        $l.append('<div class="barcode-label__price">' + esc(currency) + ' ' + Number(p.selling_price || 0).toFixed(2) + '</div>');
                    }
                    $sheet.append($l);
                    try {
                        JsBarcode(svg, value, {format: 'CODE128', displayValue: true, fontSize: 11, height: 40, margin: 0, width: 1.4});
                    } catch (e) {
                        $(svg).replaceWith('<small class="text-danger">Invalid barcode</small>');
                    }
                }
            });
            $('#labelCount').text(total + (total === 1 ? ' label' : ' labels'));
        }

        function search(term, addFirst) {
            if (searchXhr) { searchXhr.abort(); }
            if (!term) { $('#labelResults').empty(); return; }
            searchXhr = $.getJSON("{{ route('products.labels.search') }}", {q: term}, function (list) {
                if (addFirst) {
                    if (list.length) { addProduct(list[0]); $('#labelSearch').val(''); $('#labelResults').empty(); }
                    else { toast('No product found for "' + term + '"', 'error'); }
                    return;
                }
                var $r = $('#labelResults').empty();
                list.forEach(function (p) {
                    $('<button type="button" class="list-group-item list-group-item-action"></button>')
                        .html(esc(p.name) + ' <small class="text-muted">' + esc(p.barcode || p.code) + '</small>')
                        .on('click', function () { addProduct(p); $('#labelSearch').val('').focus(); $r.empty(); })
                        .appendTo($r);
                });
            });
        }

        $('#labelSearch').on('input', function () {
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

        $('#labelRows').on('input change', '.label-row-qty', function () {
            var id = $(this).closest('tr').data('id');
            items[id].copies = Math.max(1, Math.min(500, parseInt(this.value, 10) || 1));
            renderSheet();
        }).on('click', '.label-remove', function () {
            var id = $(this).closest('tr').data('id');
            delete items[id];
            order = order.filter(function (x) { return x !== id; });
            renderRows();
            renderSheet();
        });

        $('#labelSize, #showPrice').on('change', renderSheet);
        $('#clearLabels').on('click', function () { items = {}; order = []; renderRows(); renderSheet(); });
        $('#printLabels').on('click', function () {
            if (!order.length) { toast('Add at least one product', 'error'); return; }
            $('body').toggleClass('labels-no-border', !$('#showBorder').is(':checked'));
            window.print();
        });
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#labelSearch, #labelResults').length) { $('#labelResults').empty(); }
        });

        @foreach($preselected as $p)
            addProduct(@json($p), 1);
        @endforeach
    });
</script>
@endpush
