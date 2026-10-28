@extends('layouts.app')

@section('content')
@php
    $currency = $globalSettings->currency ?? 'Rs';
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');
@endphp

<div class="wareHouse-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Stock Levels
            <small class="text-muted fs-6 fw-normal">{{ $perStore ? 'All stores' : ($currentStore->name ?? 'No store assigned') }}</small>
        </h2>
        <div class="d-flex gap-2">
            @permission('stock_transfers', 'add')
                <a href="{{ route('stock-transfers.create') }}" class="themeBtn themeBtn--gray"><i class="fa-solid fa-right-left me-1"></i> Transfer</a>
            @endpermission
            @permission('stock_adjustments', 'add')
                <a href="{{ route('stock-adjustments.create') }}" class="themeBtn"><i class="fa-solid fa-sliders me-1"></i> Adjust</a>
            @endpermission
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="app-card"><div class="text-muted small">Products</div><h4 class="fw-bold mb-0">{{ number_format($summary['products']) }}</h4></div></div>
        <div class="col-6 col-md-3"><div class="app-card"><div class="text-muted small">Units in stock</div><h4 class="fw-bold mb-0">{{ $fmt($summary['units']) }}</h4></div></div>
        <div class="col-6 col-md-3"><div class="app-card"><div class="text-muted small">Stock value (cost)</div><h4 class="fw-bold mb-0">{{ $currency }} {{ number_format($summary['value'], 2) }}</h4></div></div>
        <div class="col-6 col-md-3"><div class="app-card"><div class="text-muted small">Low / Out of stock</div><h4 class="fw-bold mb-0"><span class="text-warning">{{ $summary['low'] }}</span> / <span class="text-danger">{{ $summary['out'] }}</span></h4></div></div>
    </div>

    <div class="transaction-ledger app-card">
        <form method="GET" action="{{ route('stock.index') }}" class="row g-2 align-items-end mb-3">
            @if(auth()->user()->canViewAllStores())
                <div class="col-md-3">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All stores (per-store columns)</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Level</label>
                <select name="level" class="form-select" onchange="this.form.submit()">
                    <option value="all" {{ $level == 'all' ? 'selected' : '' }}>All</option>
                    <option value="low" {{ $level == 'low' ? 'selected' : '' }}>Low stock</option>
                    <option value="out" {{ $level == 'out' ? 'selected' : '' }}>Out of stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, code, barcode">
            </div>
            <div class="col-md-1">
                <button class="themeBtn w-100" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <div class="table-responsive">
            <table id="stockLevelsTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Product</th>
                        <th>Category</th>
                        @if($perStore)
                            @foreach($stores as $s)
                                <th class="text-end">{{ $s->name }}</th>
                            @endforeach
                        @endif
                        <th class="text-end">{{ $perStore ? 'Total' : 'Quantity' }}</th>
                        <th class="text-end">Min</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        @php
                            $min = $r->min_stock_level ?? 10;
                            $state = $r->total_qty <= 0 ? 'out' : ($r->total_qty <= $min ? 'low' : 'ok');
                        @endphp
                        <tr class="{{ $state == 'out' ? 'table-danger' : ($state == 'low' ? 'table-warning' : '') }}">
                            <td>{{ $r->code }}</td>
                            <td>
                                {{ $r->name }}
                                @if($r->status != 'Active')<span class="badge bg-secondary ms-1">Inactive</span>@endif
                                @if($r->barcode)<br><small class="text-muted">{{ $r->barcode }}</small>@endif
                            </td>
                            <td>{{ $r->category_name ?? '-' }}</td>
                            @if($perStore)
                                @foreach($stores as $s)
                                    @php $q = $r->{'s_' . $s->id}; @endphp
                                    <td class="text-end {{ $q <= 0 ? 'text-muted' : '' }}" data-order="{{ $q }}">{{ $fmt($q) }}</td>
                                @endforeach
                            @endif
                            <td class="text-end fw-semibold" data-order="{{ $r->total_qty }}">{{ $fmt($r->total_qty) }} <small class="text-muted">{{ $r->unit_name }}</small></td>
                            <td class="text-end">{{ $min }}</td>
                            <td data-order="{{ $state == 'out' ? 0 : ($state == 'low' ? 1 : 2) }}">
                                @if($state == 'out')
                                    <span class="badge bg-danger">Out of stock</span>
                                @elseif($state == 'low')
                                    <span class="badge bg-warning">Low stock</span>
                                @else
                                    <span class="badge bg-success">In stock</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#stockLevelsTable').DataTable({ order: [[1, 'asc']] });
    });
</script>
@endpush
