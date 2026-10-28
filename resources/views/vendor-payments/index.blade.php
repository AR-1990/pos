@extends('layouts.app')

@section('content')
@php
    $currency = $globalSettings->currency ?? 'Rs';
    $money = fn ($v) => $currency . ' ' . number_format((float) $v, 2);
    $methods = ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank transfer'];
    $canPay = auth()->user()->hasPermission('vendor_payments', 'add');
@endphp

<div class="wareHouse-dashboard">
    <div class="dashboard-content__head d-flex justify-content-between align-items-center">
        <h2 class="heading mb-0">Vendor Payments</h2>
        @permission('vendor_payments', 'add')
        <button type="button" class="themeBtn" data-pay-vendor="">
            <i class="fa-solid fa-plus me-1"></i> Record payment
        </button>
        @endpermission
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="chart-card"><div class="text-muted small">Total purchased</div><div class="fs-4 fw-bold">{{ $money($vendorRows->sum('total_purchased')) }}</div></div>
        </div>
        <div class="col-md-4">
            <div class="chart-card"><div class="text-muted small">Total paid</div><div class="fs-4 fw-bold text-success">{{ $money($vendorRows->sum('total_paid')) }}</div></div>
        </div>
        <div class="col-md-4">
            <div class="chart-card"><div class="text-muted small">Balance due</div><div class="fs-4 fw-bold text-danger">{{ $money($vendorRows->sum('total_due')) }}</div></div>
        </div>
    </div>

    <div class="transaction-ledger mb-3">
        <h6 class="chart-card__title mb-3">Payables by vendor</h6>
        <div class="table-responsive">
            <table class="table table-hover tables align-middle" id="vendorBalances">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Phone</th>
                        <th class="text-end">Purchases</th>
                        <th class="text-end">Total purchased</th>
                        <th class="text-end">Total paid</th>
                        <th class="text-end">Balance due</th>
                        @if($canPay)<th>Action</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendorRows as $v)
                        <tr>
                            <td>{{ $v->name }} @if($v->code)<small class="text-muted">({{ $v->code }})</small>@endif
                                @if($v->status !== 'Active')<span class="badge bg-danger ms-1">{{ $v->status }}</span>@endif</td>
                            <td>{{ $v->phone ?: '-' }}</td>
                            <td class="text-end">{{ $v->purchases_count }}</td>
                            <td class="text-end" data-order="{{ $v->total_purchased }}">{{ $money($v->total_purchased) }}</td>
                            <td class="text-end" data-order="{{ $v->total_paid }}">{{ $money($v->total_paid) }}</td>
                            <td class="text-end fw-semibold {{ $v->total_due > 0 ? 'text-danger' : '' }}" data-order="{{ $v->total_due }}">{{ $money($v->total_due) }}</td>
                            @if($canPay)
                                <td>
                                    @if($v->total_due > 0)
                                        <button type="button" class="btn btn-sm btn-primary" data-pay-vendor="{{ $v->id }}"><i class="fa-solid fa-money-bill-wave me-1"></i> Pay</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="transaction-ledger">
        <h6 class="chart-card__title mb-3">Recent payments</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Date</th><th>Vendor</th><th>Purchase</th><th>Store</th><th>Method</th><th>Note</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $p)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($p->date)->format('d M Y') }}</td>
                            <td>{{ $p->vendor->name ?? '-' }}</td>
                            <td>
                                @if($p->purchase)
                                    <a href="{{ route('purchases.show', $p->purchase_id) }}">{{ $p->purchase->purchase_no }}</a>
                                @else - @endif
                            </td>
                            <td>{{ $p->store->name ?? '-' }}</td>
                            <td>{{ $methods[$p->method] ?? $p->method }}{{ $p->bank_id ? ' (' . ($bankNames[$p->bank_id] ?? '') . ')' : '' }}</td>
                            <td>{{ $p->note }}</td>
                            <td class="text-end">{{ $money($p->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canPay)
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="paymentForm" autocomplete="off" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Record vendor payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="payVendor">Vendor <span class="text-danger">*</span></label>
                        <select id="payVendor" name="vendor_id" class="form-select" required>
                            <option value="">Select vendor</option>
                            @foreach($vendorRows->where('total_due', '>', 0) as $v)
                                <option value="{{ $v->id }}" data-due="{{ $v->total_due }}">{{ $v->name }} &mdash; due {{ $money($v->total_due) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="payPurchase">Apply to</label>
                        <select id="payPurchase" name="purchase_id" class="form-select">
                            <option value="">Oldest due purchases first</option>
                        </select>
                        <div class="form-text" id="dueHint"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="payAmount">Amount ({{ $currency }}) <span class="text-danger">*</span></label>
                        <input type="number" id="payAmount" name="amount" class="form-control" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="payDate">Date <span class="text-danger">*</span></label>
                        <input type="date" id="payDate" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="payMethod">Method <span class="text-danger">*</span></label>
                        <select id="payMethod" name="method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank transfer</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-none" id="payBankWrap">
                        <label class="form-label" for="payBank">Bank <span class="text-danger">*</span></label>
                        <select id="payBank" name="bank_id" class="form-select">
                            <option value="">Select bank</option>
                            @foreach($banks as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="payNote">Note</label>
                        <input type="text" id="payNote" name="note" class="form-control" maxlength="255" placeholder="Optional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="themeBtn themeBtn--gray" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="themeBtn" id="paySubmit">Save payment</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if($canPay)
<script>
$(function () {
    var duePurchases = @json($duePurchases);
    var currency = @json($currency);
    var modal = new bootstrap.Modal(document.getElementById('paymentModal'));

    function money(n) { return currency + ' ' + (parseFloat(n) || 0).toFixed(2); }

    function vendorDue() {
        return parseFloat($('#payVendor option:selected').data('due')) || 0;
    }

    function fillPurchases(selectId) {
        var vid = parseInt($('#payVendor').val(), 10);
        var $sel = $('#payPurchase').empty().append('<option value="">Oldest due purchases first</option>');
        duePurchases.filter(function (p) { return p.vendor_id === vid; }).forEach(function (p) {
            $('<option>').val(p.id).attr('data-due', p.due).text(p.label + ' — due ' + money(p.due)).appendTo($sel);
        });
        if (selectId) $sel.val(String(selectId));
        updateHint();
    }

    function maxDue() {
        var $p = $('#payPurchase option:selected');
        return $p.val() ? (parseFloat($p.data('due')) || 0) : vendorDue();
    }

    function updateHint() {
        var due = maxDue();
        $('#dueHint').text($('#payVendor').val() ? 'Payable: ' + money(due) : '');
        $('#payAmount').attr('max', due || null);
    }

    function openFor(vendorId, purchaseId) {
        $('#paymentForm')[0].reset();
        $('#payDate').val(@json(now()->toDateString()));
        $('#payBankWrap').addClass('d-none');
        $('#payVendor').val(vendorId ? String(vendorId) : '');
        fillPurchases(purchaseId);
        if (vendorId) $('#payAmount').val(maxDue().toFixed(2));
        modal.show();
    }

    $(document).on('click', '[data-pay-vendor]', function () { openFor($(this).data('pay-vendor')); });
    $('#payVendor').on('change', function () { fillPurchases(); $('#payAmount').val(this.value ? maxDue().toFixed(2) : ''); });
    $('#payPurchase').on('change', function () { updateHint(); $('#payAmount').val(maxDue().toFixed(2)); });
    $('#payMethod').on('change', function () { $('#payBankWrap').toggleClass('d-none', this.value !== 'bank_transfer'); });

    $('#paymentForm').on('submit', function (e) {
        e.preventDefault();
        if (!$('#payVendor').val()) { toast('Select a vendor.', 'error'); return; }
        var amount = parseFloat($('#payAmount').val());
        if (!(amount > 0)) { toast('Enter an amount greater than zero.', 'error'); return; }
        if (amount > maxDue() + 0.0001) { toast('Amount cannot be more than ' + money(maxDue()) + '.', 'error'); return; }

        var $btn = $('#paySubmit').prop('disabled', true);
        $.ajax({
            url: @json(route('vendor-payments.store')),
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                vendor_id: $('#payVendor').val(),
                purchase_id: $('#payPurchase').val() || null,
                amount: $('#payAmount').val(),
                date: $('#payDate').val(),
                method: $('#payMethod').val(),
                bank_id: $('#payMethod').val() === 'bank_transfer' ? $('#payBank').val() : null,
                note: $('#payNote').val()
            })
        }).done(function (res) {
            modal.hide();
            Swal.fire('Saved', res.message, 'success').then(function () { window.location.reload(); });
        }).fail(function (xhr) {
            $btn.prop('disabled', false);
            var r = xhr.responseJSON || {};
            if (r.errors) {
                Object.keys(r.errors).forEach(function (k) { toast(r.errors[k][0], 'error'); });
            } else {
                toast(r.message || r.error || 'Could not save the payment.', 'error');
            }
        });
    });

    // Deep link from a purchase page: /vendor-payments?vendor_id=..&purchase_id=..
    var qs = new URLSearchParams(window.location.search);
    if (qs.get('vendor_id') && $('#payVendor option[value="' + qs.get('vendor_id') + '"]').length) {
        openFor(qs.get('vendor_id'), qs.get('purchase_id'));
    }
});
</script>
@endif
@endpush
