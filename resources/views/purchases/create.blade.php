@extends('layouts.app')

@section('content')
@php $currency = $globalSettings->currency ?? 'Rs'; @endphp

<div class="wareHouse-dashboard">
    <div class="dashboard-content__head d-flex justify-content-between align-items-center">
        <h2 class="heading mb-0">New Purchase</h2>
        <a href="{{ route('purchases.index') }}" class="back-arrow" title="Back to purchases">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>

    <form id="purchaseForm" autocomplete="off" novalidate>
        <div class="app-card mb-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Store <span class="text-danger">*</span></label>
                    @if($lockedStore)
                        <div><span class="badge bg-success fs-6"><i class="fa-solid fa-store me-1"></i>{{ $lockedStore->name }}</span></div>
                        <input type="hidden" name="store_id" id="storeId" value="{{ $lockedStore->id }}">
                    @elseif(!auth()->user()->canViewAllStores())
                        <div><span class="badge bg-danger">No store assigned</span></div>
                        <input type="hidden" name="store_id" id="storeId" value="">
                    @else
                        <select name="store_id" id="storeId" class="form-select" required>
                            <option value="">Select store</option>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ (int) $defaultStoreId === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="vendorId">Vendor <span class="text-danger">*</span></label>
                    <select name="vendor_id" id="vendorId" class="form-select" required>
                        <option value="">Select vendor</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}{{ $v->code ? ' (' . $v->code . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="vendorInvoiceNo">Vendor invoice no</label>
                    <input type="text" name="vendor_invoice_no" id="vendorInvoiceNo" class="form-control" maxlength="100" placeholder="e.g. INV-4471">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="purchaseDate">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" id="purchaseDate" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
            </div>
        </div>

        <div class="app-card mb-3">
            <label class="form-label" for="productSearch">Add products</label>
            <div class="position-relative">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
                    <input type="text" id="productSearch" class="form-control" placeholder="Search by name, code, SKU or scan a barcode, then press Enter">
                </div>
                <div id="searchResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1050; max-height: 320px; overflow-y: auto;"></div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle mb-0" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">In stock</th>
                            <th style="width: 140px;">Qty</th>
                            <th style="width: 160px;">Unit cost</th>
                            <th class="text-end" style="width: 160px;">Line total</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="empty-row"><td colspan="6" class="text-center text-muted py-4">No products added yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="app-card h-100">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="paidAmount">Paid now ({{ $currency }})</label>
                            <input type="number" name="paid_amount" id="paidAmount" class="form-control" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="paymentMethod">Payment method</label>
                            <select name="payment_method" id="paymentMethod" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank transfer</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-none" id="bankWrap">
                            <label class="form-label" for="bankId">Bank <span class="text-danger">*</span></label>
                            <select name="bank_id" id="bankId" class="form-select">
                                <option value="">Select bank</option>
                                @foreach($banks as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}{{ $b->account_number ? ' - ' . $b->account_number : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="note">Note</label>
                            <textarea name="note" id="note" class="form-control" rows="3" maxlength="1000" placeholder="Optional"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="app-card h-100">
                    <table class="table mb-3">
                        <tr><th>Subtotal</th><td class="text-end" id="subtotalText">0.00</td></tr>
                        <tr>
                            <th class="align-middle">Discount</th>
                            <td><input type="number" name="discount" id="discount" class="form-control text-end" min="0" step="0.01" value="0"></td>
                        </tr>
                        <tr>
                            <th class="align-middle">Tax</th>
                            <td><input type="number" name="tax" id="tax" class="form-control text-end" min="0" step="0.01" value="0"></td>
                        </tr>
                        <tr class="fs-5"><th>Grand total</th><td class="text-end fw-bold" id="grandTotalText">0.00</td></tr>
                        <tr><th>Due</th><td class="text-end text-danger fw-semibold" id="dueText">0.00</td></tr>
                    </table>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('purchases.index') }}" class="themeBtn themeBtn--gray">Cancel</a>
                        <button type="submit" class="themeBtn" id="saveBtn"><i class="fa-solid fa-floppy-disk me-1"></i> Save Purchase</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    var currency = @json($currency);
    var searchUrl = @json(route('purchases.products.search'));
    var storeUrl = @json(route('purchases.store'));
    var results = [];
    var searchXhr = null, searchTimer = null, enterPending = false;

    function money(n) { return currency + ' ' + (Math.round((parseFloat(n) || 0) * 100) / 100).toFixed(2); }
    function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function storeId() { return $('#storeId').val(); }

    function recalc() {
        var subtotal = 0;
        $('#itemsTable tbody tr[data-id]').each(function () {
            var line = Math.round(num($(this).find('.qty').val()) * num($(this).find('.cost').val()) * 100) / 100;
            $(this).find('.line-total').text(money(line));
            subtotal += line;
        });
        var grand = subtotal - num($('#discount').val()) + num($('#tax').val());
        $('#subtotalText').text(money(subtotal));
        $('#grandTotalText').text(money(grand));
        $('#dueText').text(money(Math.max(grand - num($('#paidAmount').val()), 0)));
        $('#itemsTable .empty-row').toggle($('#itemsTable tbody tr[data-id]').length === 0);
    }

    function addProduct(p) {
        var $row = $('#itemsTable tbody tr[data-id="' + p.id + '"]');
        if ($row.length) {
            var $q = $row.find('.qty');
            $q.val(num($q.val()) + 1);
        } else {
            $row = $('<tr>').attr('data-id', p.id).html(
                '<td><div class="fw-semibold">' + esc(p.name) + '</div><small class="text-muted">' + esc(p.code || p.sku || '') + '</small></td>' +
                '<td class="text-end stock">' + (p.stock === null || p.stock === undefined ? '-' : esc(p.stock)) + '</td>' +
                '<td><input type="number" class="form-control qty" min="0.001" step="any" value="1"></td>' +
                '<td><input type="number" class="form-control cost" min="0" step="0.01" value="' + esc(p.purchase_price || 0) + '"></td>' +
                '<td class="text-end line-total">0.00</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove"><i class="fa-solid fa-trash"></i></button></td>'
            );
            $('#itemsTable tbody').append($row);
        }
        recalc();
        $row.find('.qty').trigger('focus').trigger('select');
    }

    function hideResults() { $('#searchResults').addClass('d-none').empty(); }

    function renderResults() {
        var $box = $('#searchResults').empty();
        if (!results.length) {
            $box.append('<div class="list-group-item text-muted">No matching products</div>');
        }
        results.forEach(function (p, i) {
            $('<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between">')
                .attr('data-index', i)
                .html('<span><strong>' + esc(p.name) + '</strong> <small class="text-muted">' + esc(p.code || '') + '</small></span>' +
                      '<small class="text-muted">Stock: ' + (p.stock === null ? '-' : esc(p.stock)) + ' &middot; ' + esc(money(p.purchase_price)) + '</small>')
                .appendTo($box);
        });
        $box.removeClass('d-none');
    }

    function runSearch(term) {
        if (searchXhr) searchXhr.abort();
        searchXhr = $.get(searchUrl, {q: term, store_id: storeId()})
            .done(function (data) {
                results = data || [];
                if (enterPending) {
                    enterPending = false;
                    if (results.length) {
                        addProduct(results[0]);
                        $('#productSearch').val('');
                        hideResults();
                    } else {
                        toast('No product found for "' + term + '"', 'error');
                    }
                    return;
                }
                renderResults();
            })
            .fail(function (xhr) {
                if (xhr.statusText !== 'abort') toast('Product search failed.', 'error');
                enterPending = false;
            });
    }

    $('#productSearch').on('input', function () {
        var term = $.trim(this.value);
        clearTimeout(searchTimer);
        if (!term) { hideResults(); return; }
        searchTimer = setTimeout(function () { runSearch(term); }, 250);
    }).on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var term = $.trim(this.value);
            if (!term) return;
            clearTimeout(searchTimer);
            // Always search fresh on Enter so a scanner's full code is used, not stale results
            enterPending = true;
            runSearch(term);
        } else if (e.key === 'Escape') {
            hideResults();
        }
    });

    $('#searchResults').on('click', 'button[data-index]', function () {
        addProduct(results[$(this).data('index')]);
        $('#productSearch').val('').trigger('focus');
        hideResults();
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#searchResults, #productSearch').length) hideResults();
    });

    $('#itemsTable').on('input', '.qty, .cost', recalc)
        .on('click', '.remove-row', function () { $(this).closest('tr').remove(); recalc(); });
    $('#discount, #tax, #paidAmount').on('input', recalc);

    $('#paymentMethod').on('change', function () {
        $('#bankWrap').toggleClass('d-none', this.value !== 'bank_transfer');
    });

    $('select#storeId').on('change', function () {
        // Stock figures belong to the previously selected store
        $('#itemsTable tbody tr[data-id] .stock').text('-');
    });

    $('#purchaseForm').on('submit', function (e) {
        e.preventDefault();
        var items = [];
        $('#itemsTable tbody tr[data-id]').each(function () {
            items.push({
                product_id: parseInt($(this).data('id'), 10),
                quantity: $(this).find('.qty').val(),
                unit_cost: $(this).find('.cost').val()
            });
        });
        if (!storeId()) { toast('Please select a store.', 'error'); return; }
        if (!$('#vendorId').val()) { toast('Please select a vendor.', 'error'); return; }
        if (!items.length) { toast('Add at least one product.', 'error'); return; }

        var payload = {
            store_id: storeId(),
            vendor_id: $('#vendorId').val(),
            vendor_invoice_no: $('#vendorInvoiceNo').val(),
            date: $('#purchaseDate').val(),
            items: items,
            discount: $('#discount').val() || 0,
            tax: $('#tax').val() || 0,
            paid_amount: $('#paidAmount').val() || 0,
            payment_method: $('#paymentMethod').val(),
            bank_id: $('#paymentMethod').val() === 'bank_transfer' ? $('#bankId').val() : null,
            note: $('#note').val()
        };

        var $btn = $('#saveBtn').prop('disabled', true);
        $.ajax({
            url: storeUrl,
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(payload)
        }).done(function (res) {
            toast(res.message || 'Purchase saved.', 'success');
            window.location.href = res.redirect;
        }).fail(function (xhr) {
            $btn.prop('disabled', false);
            var r = xhr.responseJSON || {};
            if (r.errors) {
                var seen = {};
                Object.keys(r.errors).forEach(function (k) {
                    r.errors[k].forEach(function (m) { if (!seen[m]) { seen[m] = 1; toast(m, 'error'); } });
                });
            } else {
                toast(r.message || r.error || 'Could not save the purchase.', 'error');
            }
        });
    });

    recalc();
});
</script>
@endpush
