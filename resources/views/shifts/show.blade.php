@extends('layouts.app')

@section('content')
@php
    $cur = $currency ?? ($globalSettings->currency ?? 'Rs');
    $m = fn ($v) => $cur . ' ' . number_format((float) $v, 2);
    $diff = $shift->status === 'closed' ? (float) $shift->difference : null;
    $totals = ['received' => 0, 'refunded' => 0, 'credit_collected' => 0, 'net' => 0];
    foreach ($payments as $p) { foreach ($totals as $k => $_) { $totals[$k] += (float) $p->$k; } }
@endphp

<style>
    .z-report { max-width: 820px; margin: 0 auto; }
    .z-report h3 { font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted, #64748b); margin: 1.25rem 0 .5rem; }
    .z-report table { margin-bottom: 0; }
    .z-report td.num, .z-report th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .z-report .z-total td { font-weight: 700; border-top: 2px solid #334155; }
    @media print {
        @page { margin: 10mm; }
        body * { visibility: hidden !important; }
        .z-report, .z-report * { visibility: visible !important; }
        .z-report { position: absolute; left: 0; top: 0; width: 100%; max-width: none; box-shadow: none !important; border: 0 !important; padding: 0 !important; }
        .no-print { display: none !important; }
        .z-report .badge { border: 1px solid #000; color: #000 !important; background: none !important; }
    }
</style>

<div class="wareHouse-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print z-report">
        <a href="{{ route('shifts.index') }}" class="themeBtn themeBtn--gray"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
        <button type="button" class="themeBtn" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
    </div>

    <div class="app-card z-report">
        <div class="text-center mb-3">
            <h2 class="mb-0" style="font-size:1.25rem;font-weight:700">{{ $globalSettings->company_name ?? config('app.name') }}</h2>
            <div class="fw-semibold">{{ $shift->store->name ?? 'Store #' . $shift->store_id }}</div>
            <div class="mt-1">
                {{ $shift->status === 'open' ? 'X-Report (shift still open)' : 'Z-Report' }} &mdash; Shift #{{ $shift->id }}
                <span class="badge {{ $shift->status === 'open' ? 'bg-success' : 'bg-secondary' }} ms-1">{{ ucfirst($shift->status) }}</span>
            </div>
        </div>

        <table class="table table-sm">
            <tr><td>Cashier</td><td class="num">{{ $shift->user->name ?? '-' }}</td></tr>
            <tr><td>Opened</td><td class="num">{{ $shift->opened_at->format('d M Y h:i A') }}</td></tr>
            <tr><td>Closed</td><td class="num">{{ $shift->closed_at ? $shift->closed_at->format('d M Y h:i A') : '-' }}</td></tr>
            <tr><td>Duration</td><td class="num">{{ $shift->opened_at->diff($shift->closed_at ?? now())->format('%hh %im') }}{{ $shift->opened_at->diffInDays($shift->closed_at ?? now()) >= 1 ? ' (+' . (int) $shift->opened_at->diffInDays($shift->closed_at ?? now()) . 'd)' : '' }}</td></tr>
        </table>

        <h3>Sales</h3>
        <table class="table table-sm">
            <thead><tr><th></th><th class="num">Count</th><th class="num">Amount</th></tr></thead>
            <tr><td>Completed sales</td><td class="num">{{ (int) $sales->sales_count }}</td><td class="num">{{ $m($sales->sales_total) }}</td></tr>
            <tr><td>Returns</td><td class="num">{{ (int) $sales->returns_count }}</td><td class="num">{{ $m($sales->returns_total) }}</td></tr>
            <tr class="z-total"><td>Net sales</td><td class="num"></td><td class="num">{{ $m($sales->sales_total + $sales->returns_total) }}</td></tr>
            <tr><td>Voided (excluded)</td><td class="num">{{ (int) $sales->voids_count }}</td><td class="num text-muted">{{ $m($sales->voids_total) }}</td></tr>
            <tr><td>Discounts given</td><td class="num"></td><td class="num">{{ $m($sales->discount_total) }}</td></tr>
            <tr><td>Tax collected</td><td class="num"></td><td class="num">{{ $m($sales->tax_total) }}</td></tr>
            <tr><td>Credit given (udhaar, still due)</td><td class="num"></td><td class="num">{{ $m($sales->credit_given) }}</td></tr>
        </table>

        <h3>Payments by method</h3>
        <table class="table table-sm">
            <thead><tr><th>Method</th><th class="num">Received</th><th class="num">Refunded</th><th class="num">Credit collected</th><th class="num">Net</th></tr></thead>
            @forelse($payments as $method => $p)
                <tr>
                    <td>{{ $methods[$method] ?? ucfirst(str_replace('_', ' ', $method)) }}</td>
                    <td class="num">{{ $m($p->received) }}</td>
                    <td class="num">{{ $m($p->refunded) }}</td>
                    <td class="num">{{ $m($p->credit_collected) }}</td>
                    <td class="num">{{ $m($p->net) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted text-center">No payments in this shift.</td></tr>
            @endforelse
            @if($payments->count())
                <tr class="z-total">
                    <td>Total</td>
                    <td class="num">{{ $m($totals['received']) }}</td>
                    <td class="num">{{ $m($totals['refunded']) }}</td>
                    <td class="num">{{ $m($totals['credit_collected']) }}</td>
                    <td class="num">{{ $m($totals['net']) }}</td>
                </tr>
            @endif
        </table>

        <h3>Customer credit collected</h3>
        <table class="table table-sm">
            <tr><td>Payments received ({{ (int) $creditCollected->cnt }})</td><td class="num">{{ $m($creditCollected->total) }}</td></tr>
        </table>

        <h3>Cash drawer</h3>
        <table class="table table-sm">
            <tr><td>Opening cash</td><td class="num">{{ $m($shift->opening_cash) }}</td></tr>
            <tr><td>+ Cash in (net)</td><td class="num">{{ $m($expected - (float) $shift->opening_cash) }}</td></tr>
            <tr class="z-total"><td>Expected cash</td><td class="num">{{ $m($expected) }}</td></tr>
            <tr><td>Counted cash</td><td class="num">{{ $shift->closing_cash !== null ? $m($shift->closing_cash) : '-' }}</td></tr>
            <tr class="z-total">
                <td>Difference {{ $diff !== null ? ($diff < 0 ? '(short)' : ($diff > 0 ? '(over)' : '')) : '' }}</td>
                <td class="num {{ $diff !== null && $diff < 0 ? 'text-danger' : '' }}">{{ $diff !== null ? $m($diff) : '-' }}</td>
            </tr>
        </table>

        @if($shift->note)
            <h3>Note</h3>
            <p class="mb-0" style="white-space:pre-line">{{ $shift->note }}</p>
        @endif

        <div class="row mt-5 pt-3 small text-muted">
            <div class="col-6">Cashier signature: ____________________</div>
            <div class="col-6 text-end">Manager signature: ____________________</div>
        </div>
        <div class="text-center small text-muted mt-3">Printed {{ now()->format('d M Y h:i A') }}</div>
    </div>
</div>
@endsection
