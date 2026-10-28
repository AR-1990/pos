<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt RCPT-{{ $payment->id }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 80mm; margin: 0 auto; padding: 10px; color: #000; font-size: 12px; }
        .center { text-align: center; }
        .header h2 { margin: 0 0 4px; font-size: 16px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        td.r { text-align: right; white-space: nowrap; }
        .big { font-size: 15px; font-weight: bold; }
        .no-print button { padding: 8px 16px; cursor: pointer; border: 0; border-radius: 4px; color: #fff; }
        @media print {
            body { width: 100%; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="position: fixed; top: 10px; right: 10px;">
        <button onclick="window.print()" style="background:#007bff">Print</button>
        <button onclick="window.close()" style="background:#6c757d">Close</button>
    </div>

    <div class="header center">
        @if($setting && $setting->company_logo)
            <img src="{{ asset($setting->company_logo) }}" alt="" style="max-width:120px;max-height:60px"><br>
        @endif
        <h2>{{ $setting->company_name ?? config('app.name') }}</h2>
        @if($payment->store)<div>{{ $payment->store->name }}</div>@endif
        @if($payment->store?->phone)<div>{{ $payment->store->phone }}</div>@endif
        <div class="divider"></div>
        <div class="big">PAYMENT RECEIPT</div>
    </div>

    <table>
        <tr><td>Receipt</td><td class="r">RCPT-{{ $payment->id }}</td></tr>
        <tr><td>Date</td><td class="r">{{ $payment->created_at->format('d M Y h:i A') }}</td></tr>
        <tr><td>Customer</td><td class="r">{{ $payment->customer->name ?? '-' }}</td></tr>
        @if($payment->customer?->phone)<tr><td>Phone</td><td class="r">{{ $payment->customer->phone }}</td></tr>@endif
        <tr><td>Method</td><td class="r">{{ $methods[$payment->method] ?? $payment->method }}{{ $bank ? ' - ' . $bank->name : '' }}</td></tr>
        <tr><td>Received by</td><td class="r">{{ $payment->user->name ?? '-' }}</td></tr>
    </table>

    <div class="divider"></div>
    <table>
        <tr><td><b>Applied to</b></td><td class="r"><b>Amount</b></td></tr>
        @foreach($payment->allocations as $a)
            <tr><td>{{ $a->sale?->number ?? '#' . $a->sale_id }}</td><td class="r">{{ number_format($a->amount, 2) }}</td></tr>
        @endforeach
    </table>
    <div class="divider"></div>
    <table>
        <tr class="big"><td>Received</td><td class="r">{{ $currency }} {{ number_format($payment->amount, 2) }}</td></tr>
        <tr><td>Balance due now</td><td class="r">{{ $currency }} {{ number_format($balance, 2) }}</td></tr>
    </table>
    @if($payment->note)
        <div class="divider"></div>
        <div>Note: {{ $payment->note }}</div>
    @endif
    <div class="divider"></div>
    <div class="center">Thank you!</div>
</body>
</html>
