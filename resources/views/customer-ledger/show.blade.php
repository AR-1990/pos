@extends('layouts.app')

@section('content')
@php
    $cur = $currency ?? ($globalSettings->currency ?? 'Rs');
    $m = fn ($v) => number_format((float) $v, 2);
@endphp

<div class="wareHouse-dashboard">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('customer-ledger.index') }}" class="themeBtn themeBtn--gray"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="app-card h-100">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div>
                        <h2 class="mb-1" style="font-size:1.25rem;font-weight:700">{{ $customer->name }}</h2>
                        <div class="small text-muted">
                            @if($customer->code)<span class="me-3"><i class="fa-solid fa-id-badge me-1"></i>{{ $customer->code }}</span>@endif
                            @if($customer->phone)<span class="me-3"><i class="fa-solid fa-phone me-1"></i>{{ $customer->phone }}</span>@endif
                            @if($customer->address)<span><i class="fa-solid fa-location-dot me-1"></i>{{ $customer->address }}</span>@endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="metric-card__title">Total due</div>
                        <div class="metric-card__value {{ $totalDue > 0 ? 'text-danger' : 'text-success' }}" id="totalDue">{{ $cur }} {{ $m($totalDue) }}</div>
                        <div class="small text-muted">{{ $unpaid->count() }} unpaid invoice(s)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="app-card h-100">
                <h3 class="chart-card__title mb-3">Receive payment</h3>
                @permission('customer_credit', 'add')
                    @if($totalDue > 0)
                    <form id="receivePaymentForm" action="{{ route('customer-payments.store', $customer->id) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" max="{{ $totalDue }}" class="form-control" id="amount" name="amount" required>
                                <button type="button" class="btn btn-outline-secondary" id="payFull">Full</button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="method">Method</label>
                            <select class="form-select" id="method" name="method">
                                @foreach($methods as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2 d-none" id="bankWrap">
                            <label class="form-label" for="bank_id">Bank <span class="text-danger">*</span></label>
                            <select class="form-select" id="bank_id" name="bank_id">
                                <option value="">Select bank</option>
                                @foreach($banks as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="note">Note</label>
                            <input type="text" class="form-control" id="note" name="note" maxlength="255">
                        </div>
                        <button type="submit" class="themeBtn w-100"><i class="fa-solid fa-money-bill-wave me-1"></i> Receive payment</button>
                        <div class="small text-muted mt-2">Applied to the oldest invoices first.</div>
                    </form>
                    @else
                        <p class="text-muted mb-0">Nothing due from this customer.</p>
                    @endif
                @else
                    <p class="text-muted mb-0">You don't have permission to receive payments.</p>
                @endpermission
            </div>
        </div>
    </div>

    <div class="transaction-ledger mb-3">
        <div class="transaction-ledger__header">
            <h2 class="chart-card__title mb-0">Unpaid invoices</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr><th>Invoice</th><th>Date</th><th>Store</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($unpaid as $s)
                        <tr>
                            <td>{{ $s->number }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($s->date)->format('d M Y') }}</td>
                            <td>{{ $s->store->name ?? '-' }}</td>
                            <td class="text-end">{{ $m($s->grand_total) }}</td>
                            <td class="text-end">{{ $m($s->paid_amount) }}</td>
                            <td class="text-end fw-semibold text-danger">{{ $m($s->due_amount) }}</td>
                            <td><span class="badge {{ $s->payment_status === 'partial' ? 'bg-warning text-dark' : 'bg-danger' }}">{{ ucfirst($s->payment_status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No unpaid invoices.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="transaction-ledger">
        <div class="transaction-ledger__header">
            <h2 class="chart-card__title mb-0">Ledger</h2>
            <span class="small text-muted">Debit = owed by customer, credit = paid / returned</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr><th>Date</th><th>Type</th><th>Reference</th><th>Details</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                        <tr>
                            <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($e['date'])->format('d M Y') }}</td>
                            <td>
                                @php $cls = ['Sale' => 'bg-primary', 'Return' => 'bg-warning text-dark', 'Payment' => 'bg-success', 'Credit received' => 'bg-info text-dark'][$e['type']] ?? 'bg-secondary'; @endphp
                                <span class="badge {{ $cls }}">{{ $e['type'] }}</span>
                            </td>
                            <td class="text-nowrap">
                                @if(!empty($e['receipt_id']))
                                    <a href="{{ route('customer-payments.receipt', $e['receipt_id']) }}" target="_blank">{{ $e['ref'] }}</a>
                                @else
                                    {{ $e['ref'] }}
                                @endif
                            </td>
                            <td class="small">{{ $e['details'] }}</td>
                            <td class="text-end">{{ $e['debit'] ? $m($e['debit']) : '' }}</td>
                            <td class="text-end">{{ $e['credit'] ? $m($e['credit']) : '' }}</td>
                            <td class="text-end fw-semibold {{ $e['balance'] > 0 ? 'text-danger' : '' }}">{{ $m($e['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
                @if($entries->count())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="4">Total</td>
                            <td class="text-end">{{ $m($totals['debit']) }}</td>
                            <td class="text-end">{{ $m($totals['credit']) }}</td>
                            <td class="text-end">{{ $m($totals['debit'] - $totals['credit']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        var totalDue = @json($totalDue);

        $('#method').on('change', function () {
            var bank = $(this).val() === 'bank_transfer';
            $('#bankWrap').toggleClass('d-none', !bank);
            $('#bank_id').prop('required', bank);
        });
        $('#payFull').on('click', function () { $('#amount').val(totalDue.toFixed(2)); });

        $('#receivePaymentForm').on('submit', function (e) {
            e.preventDefault();
            var form = this;
            var amount = parseFloat($('#amount').val());
            if (!(amount > 0)) { toast('Enter an amount greater than zero.', 'error'); return; }
            if (amount > totalDue + 0.0001) { toast('Amount cannot exceed the total due.', 'error'); return; }

            Swal.fire({
                title: 'Receive payment?',
                html: 'Amount: <b>' + window.APP.currency + ' ' + amount.toFixed(2) + '</b> by <b>' + $('#method option:selected').text() + '</b>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, receive'
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                var $btn = $(form).find('button[type=submit]').prop('disabled', true);
                $.ajax({url: form.action, type: 'POST', data: $(form).serialize(), headers: {Accept: 'application/json'}})
                    .done(function (r) {
                        Swal.fire({
                            title: 'Payment received',
                            text: r.message,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fa-solid fa-print"></i> Print receipt',
                            cancelButtonText: 'Done'
                        }).then(function (res) {
                            if (res.isConfirmed) { window.open(r.receipt_url, '_blank'); }
                            location.reload();
                        });
                    })
                    .fail(function (xhr) {
                        var j = xhr.responseJSON || {};
                        toast(j.errors ? Object.values(j.errors)[0][0] : (j.message || j.error || 'Something went wrong.'), 'error');
                        $btn.prop('disabled', false);
                    });
            });
        });
    });
</script>
@endpush
