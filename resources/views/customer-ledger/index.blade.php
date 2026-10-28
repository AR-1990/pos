@extends('layouts.app')

@section('content')
@php $cur = $currency ?? ($globalSettings->currency ?? 'Rs'); @endphp

<div class="wareHouse-dashboard">
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
                <div class="metric-card__icon bg-danger-subtle text-danger"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="metric-card__content">
                    <h3 class="metric-card__title">Total receivable</h3>
                    <p class="metric-card__value">{{ $cur }} {{ number_format($totals->receivable, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
                <div class="metric-card__icon bg-primary-subtle text-primary"><i class="fa-solid fa-users"></i></div>
                <div class="metric-card__content">
                    <h3 class="metric-card__title">Customers with dues</h3>
                    <p class="metric-card__value">{{ number_format($totals->customers) }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
                <div class="metric-card__icon bg-warning-subtle text-warning"><i class="fa-solid fa-file-invoice"></i></div>
                <div class="metric-card__content">
                    <h3 class="metric-card__title">Unpaid invoices</h3>
                    <p class="metric-card__value">{{ number_format($totals->invoices) }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="metric-card">
                <div class="metric-card__icon bg-info-subtle text-info"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="metric-card__content">
                    <h3 class="metric-card__title">Oldest due</h3>
                    <p class="metric-card__value">{{ $totals->oldest ? \Illuminate\Support\Carbon::parse($totals->oldest)->format('d M Y') : '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <h2 class="chart-card__title mb-0">Customers with outstanding credit</h2>
            </div>
            <div class="table-responsive">
                <table id="ledgerTable" class="table table-hover tables w-100">
                    <thead>
                        <tr>
                            <th>S. No</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Total due</th>
                            <th>Invoices due</th>
                            <th>Oldest due</th>
                            <th>Last payment</th>
                            <th>Ledger</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#ledgerTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[3, 'desc']],
            language: {search: 'Search name / phone:'},
            ajax: @json(route('customer-ledger.index')),
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'phone', name: 'phone'},
                {data: 'total_due', name: 'total_due', searchable: false},
                {data: 'invoices_due', name: 'invoices_due', searchable: false},
                {data: 'oldest_due', name: 'oldest_due', searchable: false},
                {data: 'last_payment', name: 'last_payment', searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
    });
</script>
@endpush
