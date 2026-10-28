@extends('layouts.app')

@section('content')
@php $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.'); @endphp
<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Adjustment {{ $adjustment->adjustment_no }}</h2>
        <div class="d-flex gap-2">
            <button type="button" class="themeBtn themeBtn--gray" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
            <a href="{{ route('stock-adjustments.index') }}" class="back-arrow"><i class="fa-regular fa-arrow-left-long"></i></a>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="row g-3">
            <div class="col-6 col-md-3"><div class="text-muted small">Store</div><div class="fw-semibold">{{ $adjustment->store->name ?? '-' }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small">Reason</div><div><span class="badge bg-secondary">{{ $reasons[$adjustment->reason] ?? $adjustment->reason }}</span></div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Date</div><div class="fw-semibold">{{ \Carbon\Carbon::parse($adjustment->date)->format('d M Y') }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">By</div><div class="fw-semibold">{{ $adjustment->user->name ?? '-' }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Created</div><div class="fw-semibold">{{ $adjustment->created_at?->format('d M Y, h:i A') }}</div></div>
            @if($adjustment->note)
                <div class="col-12"><div class="text-muted small">Note</div><div>{{ $adjustment->note }}</div></div>
            @endif
        </div>
    </div>

    <div class="app-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>#</th><th>Code</th><th>Product</th><th class="text-end">Change</th></tr>
                </thead>
                <tbody>
                    @foreach($adjustment->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $item->product->code ?? '-' }}</td>
                            <td>{{ $item->product->name ?? 'Deleted product' }}</td>
                            <td class="text-end fw-semibold {{ $item->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $item->quantity > 0 ? '+' : '' }}{{ $fmt($item->quantity) }}
                                <small class="text-muted fw-normal">{{ $item->product->unit->name ?? '' }}</small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
