<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Slip #{{ $sale->id }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; width: 300px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { text-align: left; border-bottom: 1px dashed #000; padding: 5px 0; }
        .totals { text-align: right; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; }
        @media print {
            body { width: 100%; margin: 0; padding: 10px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 100;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;">Print Slip</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; background: #6c757d; color: white; border: none; border-radius: 4px;">Close</button>
    </div>

    <div class="header">
        @if(isset($setting) && $setting->company_logo)
            <img src="{{ asset($setting->company_logo) }}" alt="Logo" style="max-width: 150px; margin-bottom: 10px;">
        @endif
        <h2>{{ $setting->company_name ?? 'POS System' }}</h2>
        <p>{{ $setting->address ?? 'Address Not Set' }}</p>
        <p>Phone: {{ $setting->phone ?? 'N/A' }}</p>
    </div>

    <div class="details">
        <p style="font-size: 1.2em; border: 2px solid #000; padding: 5px; display: inline-block; margin-bottom: 10px;">
            <strong>Sale ID: {{ $sale->id }}</strong>
        </p>
        @if($sale->store)
        <p><strong>Store:</strong> {{ $sale->store->name }}@if($sale->store->phone) ({{ $sale->store->phone }})@endif</p>
        @endif
        @if($sale->user)
        <p><strong>Cashier:</strong> {{ $sale->user->name }}</p>
        @endif
        <p><strong>Date:</strong> {{ $sale->date }}</p>
        <p><strong>Customer:</strong> {{ $sale->customer->name }}</p>
        <p><strong>Phone:</strong> {{ $sale->customer->phone }}</p>
        @if($sale->reference_no)
        <p><strong>Ref No:</strong> {{ $sale->reference_no }}</p>
        @endif
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $setting->currency ?? '$' }}{{ number_format($item->price, 2) }}</td>
                <td>{{ $setting->currency ?? '$' }}{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>Subtotal: {{ $setting->currency ?? '$' }}{{ number_format($sale->subtotal, 2) }}</p>
        <p>Discount: -{{ $setting->currency ?? '$' }}{{ number_format($sale->discount, 2) }}</p>
        <p>Tax: +{{ $setting->currency ?? '$' }}{{ number_format($sale->tax, 2) }}</p>
        <h3>Grand Total: {{ $setting->currency ?? '$' }}{{ number_format($sale->grand_total, 2) }}</h3>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
    </div>
</body>
</html>
