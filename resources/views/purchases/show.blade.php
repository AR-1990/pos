@extends('layouts.app')

@section('content')
@php
    $currency = $globalSettings->currency ?? 'Rs';
    $money = fn ($v) => $currency . ' ' . number_format((float) $v, 2);
    $methods = ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank transfer'];
@endphp

<div class="wareHouse-dashboard">
    <div class="dashboard-content__head d-flex justify-content-between align-items-center no-print">
        <h2 class="heading mb-0">Purchase {{ $purchase->purchase_no }}</h2>
        <div class="d-flex gap-2 align-items-center">
            @if($purchase->due_amount > 0 && auth()->user()->hasPermission('vendor_payments', 'add'))
                <a href="{{ route('vendor-payments.index', ['vendor_id' => $purchase->vendor_id, 'purchase_id' => $purchase->id]) }}" class="themeBtn themeBtn--gray">
                    <i class="fa-solid fa-money-bill-wave me-1"></i> Pay due
                </a>
            @endif
            <button type="button" class="themeBtn" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
            <a href="{{ route('purchases.index') }}" class="back-arrow" title="Back to purchases"><i class="fa-regular fa-arrow-left-long"></i></a>
        </div>
    </div>

    <div class="app-card" id="printArea">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1">{{ $globalSettings->company_name ?? config('app.name') }}</h4>
                <div class="text-muted">{{ $purchase->store->name ?? '' }}</div>
                @if($purchase->store?->address)<div class="text-muted small">{{ $purchase->store->address }}</div>@endif
            </div>
            <div class="text-md-end">
                <h5 class="mb-1">PURCHASE</h5>
                <div><strong>{{ $purchase->purchase_no }}</strong></div>
                <div>Date: {{ \Carbon\Carbon::parse($purchase->date)->format('d M Y') }}</div>
                @if($purchase->vendor_invoice_no)<div>Vendor invoice: {{ $purchase->vendor_invoice_no }}</div>@endif
                <div class="mt-1">{!! \App\Http\Controllers\PurchaseController::statusBadge($purchase->payment_status) !!}</div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <div class="text-muted small text-uppercase">Vendor</div>
                <div class="fw-semibold">{{ $purchase->vendor->name ?? '-' }}</div>
                @if($purchase->vendor?->phone)<div>{{ $purchase->vendor->phone }}</div>@endif
                @if($purchase->vendor?->address)<div class="small text-muted">{{ $purchase->vendor->address }}</div>@endif
            </div>
            <div class="col-sm-6 text-sm-end">
                <div class="text-muted small text-uppercase">Recorded by</div>
                <div>{{ $purchase->user->name ?? '-' }}</div>
                <div class="small text-muted">{{ $purchase->created_at?->format('d M Y h:i A') }}</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Code</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit cost</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $item->product->name ?? 'Product #' . $item->product_id }}</td>
                            <td>{{ $item->product->code ?? '' }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }}</td>
                            <td class="text-end">{{ $money($item->unit_cost) }}</td>
                            <td class="text-end">{{ $money($item->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end">
            <div class="col-md-5">
                <table class="table table-sm">
                    <tr><th>Subtotal</th><td class="text-end">{{ $money($purchase->subtotal) }}</td></tr>
                    <tr><th>Discount</th><td class="text-end">- {{ $money($purchase->discount) }}</td></tr>
                    <tr><th>Tax</th><td class="text-end">+ {{ $money($purchase->tax) }}</td></tr>
                    <tr class="fs-5"><th>Grand total</th><td class="text-end fw-bold">{{ $money($purchase->grand_total) }}</td></tr>
                    <tr><th>Paid</th><td class="text-end text-success">{{ $money($purchase->paid_amount) }}</td></tr>
                    <tr><th>Due</th><td class="text-end text-danger fw-semibold">{{ $money($purchase->due_amount) }}</td></tr>
                </table>
            </div>
        </div>

        <h6 class="mt-3">Payments</h6>
        @if($purchase->payments->isEmpty())
            <p class="text-muted">No payments recorded.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Date</th><th>Method</th><th>Bank</th><th>Note</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->payments as $pay)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($pay->date)->format('d M Y') }}</td>
                                <td>{{ $methods[$pay->method] ?? $pay->method }}</td>
                                <td>{{ $pay->bank_id ? ($banks[$pay->bank_id] ?? '-') : '-' }}</td>
                                <td>{{ $pay->note }}</td>
                                <td class="text-end">{{ $money($pay->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($purchase->note)
            <div class="mt-3"><strong>Note:</strong> {{ $purchase->note }}</div>
        @endif
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: 0; }
        .no-print { display: none !important; }
    }
</style>

@endsection
