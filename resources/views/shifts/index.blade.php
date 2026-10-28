@extends('layouts.app')

@section('content')
@php $cur = $currency ?? ($globalSettings->currency ?? 'Rs'); @endphp

<div class="wareHouse-dashboard">
    <div class="row g-3 mb-3">
        @if($shift)
            <div class="col-12">
                <div class="app-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="chart-card__title mb-1">
                                <span class="badge bg-success me-2">Open</span>
                                Your shift #{{ $shift->id }} at {{ $shift->store->name ?? 'Store #' . $shift->store_id }}
                            </h2>
                            <div class="small text-muted">Opened {{ $shift->opened_at->format('d M Y h:i A') }} ({{ $shift->opened_at->diffForHumans() }})</div>
                        </div>
                        <a href="{{ route('shifts.show', $shift->id) }}" class="themeBtn themeBtn--gray">
                            <i class="fa-solid fa-file-invoice me-1"></i> Live report
                        </a>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-sm-6">
                            <div class="metric-card">
                                <div class="metric-card__icon bg-primary-subtle text-primary"><i class="fa-solid fa-cash-register"></i></div>
                                <div class="metric-card__content">
                                    <h3 class="metric-card__title">Opening cash</h3>
                                    <p class="metric-card__value">{{ $cur }} {{ number_format($shift->opening_cash, 2) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="metric-card">
                                <div class="metric-card__icon bg-success-subtle text-success"><i class="fa-solid fa-money-bill-wave"></i></div>
                                <div class="metric-card__content">
                                    <h3 class="metric-card__title">Cash sales</h3>
                                    <p class="metric-card__value">{{ $cur }} {{ number_format($summary['cash_sales'], 2) }}</p>
                                    @if($summary['cash_credit'] != 0)
                                        <div class="small text-muted">+ {{ $cur }} {{ number_format($summary['cash_credit'], 2) }} credit collected</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="metric-card">
                                <div class="metric-card__icon bg-info-subtle text-info"><i class="fa-solid fa-receipt"></i></div>
                                <div class="metric-card__content">
                                    <h3 class="metric-card__title">Sales ({{ $summary['sales_count'] }})</h3>
                                    <p class="metric-card__value">{{ $cur }} {{ number_format($summary['sales_total'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="metric-card">
                                <div class="metric-card__icon bg-warning-subtle text-warning"><i class="fa-solid fa-scale-balanced"></i></div>
                                <div class="metric-card__content">
                                    <h3 class="metric-card__title">Expected cash in drawer</h3>
                                    <p class="metric-card__value" id="expectedCash" data-value="{{ $summary['expected'] }}">{{ $cur }} {{ number_format($summary['expected'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @permission('shifts', 'add')
                    <form id="closeShiftForm" action="{{ route('shifts.close', $shift->id) }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label" for="closing_cash">Counted cash <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="closing_cash" name="closing_cash" required>
                            <div class="small mt-1" id="diffPreview"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="close_note">Note</label>
                            <input type="text" class="form-control" id="close_note" name="note" maxlength="500" placeholder="Optional, e.g. reason for a shortage">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="themeBtn themeBtn--red w-100"><i class="fa-solid fa-lock me-1"></i> Close shift</button>
                        </div>
                    </form>
                    @endpermission
                </div>
            </div>
        @else
            @permission('shifts', 'add')
            <div class="col-12">
                <div class="app-card">
                    <h2 class="chart-card__title mb-1">You have no open shift</h2>
                    <p class="small text-muted mb-3">Count the cash in the drawer and open a shift before you start selling.</p>
                    <form id="openShiftForm" action="{{ route('shifts.open') }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="store_id">Store</label>
                            @if(auth()->user()->canViewAllStores())
                                <select class="form-select" id="store_id" name="store_id" required>
                                    <option value="">Select store</option>
                                    @foreach($stores as $s)
                                        <option value="{{ $s->id }}" {{ (int) $storeId === (int) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ auth()->user()->store->name ?? 'No store assigned' }}" disabled>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="opening_cash">Opening cash <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="opening_cash" name="opening_cash" value="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="open_note">Note</label>
                            <input type="text" class="form-control" id="open_note" name="note" maxlength="500">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="themeBtn w-100"><i class="fa-solid fa-unlock me-1"></i> Open shift</button>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <div class="col-12">
                <div class="app-card"><p class="mb-0 text-muted">You have no open shift.</p></div>
            </div>
            @endpermission
        @endif
    </div>

    <div class="dashboard-card">
        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="shiftFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-filter me-2"></i> Filter
                    </button>
                    <ul class="dropdown-menu shifts-filter-menu" aria-labelledby="shiftFilterDropdown">
                        <li><h6 class="dropdown-header">Filter by Status</h6></li>
                        <li><a class="dropdown-item active" href="javascript:void(0)" data-status="all">All</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" data-status="open">Open</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" data-status="closed">Closed</a></li>
                    </ul>
                </div>
            </div>
            <div class="table-responsive">
                <table id="shiftsTable" class="table table-hover tables w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Store</th>
                            <th>Cashier</th>
                            <th>Opened</th>
                            <th>Closed</th>
                            <th>Opening</th>
                            <th>Expected</th>
                            <th>Counted</th>
                            <th>Difference</th>
                            <th>Status</th>
                            <th>Report</th>
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
        var currency = @json($cur);
        var status = 'all';
        var fmt = function (n) { return currency + ' ' + Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); };

        var table = $('#shiftsTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[3, 'desc']],
            ajax: {
                url: @json(route('shifts.index')),
                data: function (d) { d.status = status; }
            },
            columns: [
                {data: 'id', name: 'shifts.id'},
                {data: 'store_name', name: 'store_name', orderable: false},
                {data: 'cashier', name: 'cashier', orderable: false},
                {data: 'opened_at', name: 'shifts.opened_at', searchable: false},
                {data: 'closed_at', name: 'shifts.closed_at', searchable: false},
                {data: 'opening_cash', name: 'shifts.opening_cash', searchable: false},
                {data: 'expected_cash', name: 'shifts.expected_cash', searchable: false},
                {data: 'closing_cash', name: 'shifts.closing_cash', searchable: false},
                {data: 'difference', name: 'shifts.difference', searchable: false},
                {data: 'status', name: 'shifts.status', searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('.shifts-filter-menu .dropdown-item').on('click', function () {
            status = $(this).data('status');
            $('.shifts-filter-menu .dropdown-item').removeClass('active');
            $(this).addClass('active');
            table.draw();
        });

        function errorMessage(xhr) {
            var r = xhr.responseJSON || {};
            if (r.errors) { return Object.values(r.errors)[0][0]; }
            return r.message || r.error || 'Something went wrong.';
        }

        $('#openShiftForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $(this).find('button[type=submit]').prop('disabled', true);
            $.ajax({url: this.action, type: 'POST', data: $(this).serialize(), headers: {Accept: 'application/json'}})
                .done(function (r) { toast(r.message, 'success'); setTimeout(function () { location.reload(); }, 600); })
                .fail(function (xhr) { toast(errorMessage(xhr), 'error'); $btn.prop('disabled', false); });
        });

        var expected = parseFloat($('#expectedCash').data('value') || 0);
        $('#closing_cash').on('input', function () {
            var v = $(this).val();
            if (v === '') { $('#diffPreview').text(''); return; }
            var diff = Math.round((parseFloat(v) - expected) * 100) / 100;
            $('#diffPreview')
                .removeClass('text-danger text-success text-muted')
                .addClass(diff < 0 ? 'text-danger' : (diff > 0 ? 'text-success' : 'text-muted'))
                .text((diff < 0 ? 'Short ' : (diff > 0 ? 'Over ' : 'Balanced ')) + fmt(Math.abs(diff)));
        });

        $('#closeShiftForm').on('submit', function (e) {
            e.preventDefault();
            var form = this;
            var counted = $('#closing_cash').val();
            if (counted === '' || parseFloat(counted) < 0) { toast('Enter the counted cash.', 'error'); return; }
            var diff = Math.round((parseFloat(counted) - expected) * 100) / 100;
            Swal.fire({
                title: 'Close this shift?',
                html: 'Expected: <b>' + fmt(expected) + '</b><br>Counted: <b>' + fmt(counted) + '</b><br>Difference: <b class="' + (diff < 0 ? 'text-danger' : '') + '">' + fmt(diff) + '</b><br><small class="text-muted">Expected cash is recalculated when the shift closes.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, close shift'
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                $.ajax({url: form.action, type: 'POST', data: $(form).serialize(), headers: {Accept: 'application/json'}})
                    .done(function (r) {
                        Swal.fire('Shift closed', r.message, 'success').then(function () { window.location = r.report_url; });
                    })
                    .fail(function (xhr) { toast(errorMessage(xhr), 'error'); });
            });
        });
    });
</script>
@endpush
