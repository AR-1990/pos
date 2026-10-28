@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.'); @endphp
<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Transfer {{ $transfer->transfer_no }}</h2>
        <div class="d-flex gap-2">
            <button type="button" class="themeBtn themeBtn--gray" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
            <a href="{{ route('stock-transfers.index') }}" class="back-arrow"><i class="fa-regular fa-arrow-left-long"></i></a>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="row g-3">
            <div class="col-6 col-md-3"><div class="text-muted small">From</div><div class="fw-semibold">{{ $transfer->fromStore->name ?? '-' }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">To</div><div class="fw-semibold">{{ $transfer->toStore->name ?? '-' }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Date</div><div class="fw-semibold">{{ \Carbon\Carbon::parse($transfer->date)->format('d M Y') }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">By</div><div class="fw-semibold">{{ $transfer->user->name ?? '-' }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Created</div><div class="fw-semibold">{{ $transfer->created_at?->format('d M Y, h:i A') }}</div></div>
            @if($transfer->note)
                <div class="col-12"><div class="text-muted small">Note</div><div>{{ $transfer->note }}</div></div>
            @endif
        </div>
    </div>

    <div class="app-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>#</th><th>Code</th><th>Product</th><th class="text-end">Quantity</th></tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $item->product->code ?? '-' }}</td>
                            <td>{{ $item->product->name ?? 'Deleted product' }}</td>
                            <td class="text-end">{{ $fmt($item->quantity) }} <small class="text-muted">{{ $item->product->unit->name ?? '' }}</small></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-end">Total</th><th class="text-end">{{ $fmt($transfer->items->sum('quantity')) }}</th></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
