@extends('layouts.app')

@section('content')
@php
    $cur = $globalSettings->currency ?? 'Rs';
    $canPickStore = auth()->user()->canViewAllStores();
    $canVoid = auth()->user()->hasPermission('sales', 'delete');
    $isResume = isset($sale);
@endphp

<style>
    .pos { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 1.25rem; align-items: start; }
    .pos-side { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 1rem; }
    .pos-search { position: relative; }
    .pos-search__box { display: flex; align-items: center; gap: .75rem; padding: .35rem .5rem .35rem 1rem; border: 2px solid #E3E5F2; border-radius: 16px; background: #fff; transition: border-color .2s, box-shadow .25s; }
    .pos-search__box:focus-within { border-color: #A5B4FC; box-shadow: 0 0 0 5px rgba(99, 102, 241, .12); }
    .pos-search__box > i { color: #8B5CF6; font-size: 1.125rem; }
    .pos-search__box input { border: 0; outline: 0; flex: 1; font-size: 1rem; padding: .6rem 0; background: transparent; min-width: 0; }
    .pos-search__box kbd { background: #F1F2FA; color: #5B6285; border-radius: 7px; font-size: .6875rem; padding: 3px 7px; }
    .pos-results { position: absolute; left: 0; right: 0; top: calc(100% + 8px); z-index: 30; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); max-height: 380px; overflow-y: auto; padding: .375rem; display: none; animation: drop-in .25s var(--spring) both; }
    .pos-results.show { display: block; }
    .pos-result { display: flex; align-items: center; gap: .75rem; padding: .6rem .75rem; border-radius: 12px; cursor: pointer; transition: background .15s, transform .2s var(--ease); }
    .pos-result:hover, .pos-result.is-active { background: var(--grad-soft); transform: translateX(3px); }
    .pos-result__icon { width: 38px; height: 38px; border-radius: 11px; background: var(--primary-soft); color: var(--primary); display: grid; place-items: center; flex: 0 0 38px; }
    .pos-result__name { font-weight: 700; color: var(--theme-color); font-size: .875rem; }
    .pos-result__meta { font-size: .75rem; color: var(--muted); }
    .pos-result__price { margin-left: auto; text-align: right; font-weight: 800; color: var(--theme-color); white-space: nowrap; }
    .stock-pill { display: inline-block; font-size: .6875rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #D1FAE5; color: #047857; }
    .stock-pill--low { background: #FEF3C7; color: #B45309; }
    .stock-pill--out { background: #FFE4E6; color: #BE123C; }

    .cart-head { display: flex; align-items: center; justify-content: space-between; margin: 1.25rem 0 .75rem; }
    .cart-head h3 { font-size: 1rem; font-weight: 800; color: var(--theme-color); margin: 0; }
    .cart-count { display: inline-grid; place-items: center; min-width: 26px; height: 26px; padding: 0 8px; border-radius: 9px; background: var(--grad); color: #fff; font-size: .75rem; font-weight: 800; margin-left: .5rem; }
    .cart-list { display: flex; flex-direction: column; gap: .5rem; min-height: 220px; }
    .cart-row { display: grid; grid-template-columns: minmax(0, 1fr) 128px 104px 110px 34px; gap: .75rem; align-items: center; padding: .7rem .85rem; border: 1px solid var(--line); border-radius: 14px; background: #fff; animation: row-in .35s var(--spring) both; transition: border-color .2s, box-shadow .2s, transform .25s var(--ease); }
    .cart-row:hover { border-color: #DDD6FE; box-shadow: var(--shadow-sm); }
    .cart-row.is-leaving { animation: none; transform: translateX(40px); opacity: 0; transition: transform .25s var(--ease), opacity .25s; }
    .cart-row.flash { animation: flash-ok .8s ease; }
    .cart-row__name { font-weight: 700; color: var(--theme-color); font-size: .875rem; }
    .cart-row__meta { font-size: .75rem; color: var(--muted); }
    .qty { display: flex; align-items: center; border: 1.5px solid #E3E5F2; border-radius: 12px; overflow: hidden; background: #FCFCFF; }
    .qty button { width: 34px; height: 36px; border: 0; background: transparent; color: var(--primary); font-weight: 800; transition: background .15s, transform .2s var(--spring); }
    .qty button:hover { background: var(--primary-soft); }
    .qty button:active { transform: scale(.85); }
    .qty input { width: 100%; min-width: 0; border: 0; text-align: center; font-weight: 700; background: transparent; outline: 0; }
    .line-disc { font-size: .8125rem; padding: .45rem .6rem; }
    .cart-row__total { text-align: right; font-weight: 800; color: var(--theme-color); font-variant-numeric: tabular-nums; }
    .cart-row__remove { width: 34px; height: 34px; border-radius: 10px; border: 0; background: #FFF1F2; color: #F43F5E; transition: transform .25s var(--spring), background .2s; }
    .cart-row__remove:hover { background: #FFE4E6; transform: rotate(90deg); }
    .cart-empty { border: 2px dashed #E3E5F2; border-radius: 18px; padding: 2.5rem 1rem; text-align: center; color: var(--muted); }
    .cart-empty__icon { width: 74px; height: 74px; margin: 0 auto 1rem; border-radius: 22px; background: var(--grad-soft); display: grid; place-items: center; font-size: 1.75rem; color: #8B5CF6; animation: float 3s ease-in-out infinite; }

    .side-card { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 1rem 1.1rem; box-shadow: var(--shadow-sm); }
    .side-card__title { font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); margin-bottom: .75rem; display: flex; align-items: center; gap: .5rem; }
    .shift-pill { display: flex; align-items: center; gap: .6rem; padding: .55rem .75rem; border-radius: 12px; font-size: .8125rem; font-weight: 700; margin-top: .75rem; }
    .shift-pill--open { background: #ECFDF5; color: #047857; }
    .shift-pill--closed { background: #FFF7ED; color: #C2410C; }
    .shift-dot { width: 9px; height: 9px; border-radius: 50%; background: currentColor; box-shadow: 0 0 0 0 currentColor; animation: dot-pulse 1.8s infinite; }
    .store-lock { display: flex; align-items: center; gap: .6rem; font-weight: 800; color: var(--theme-color); }
    .store-lock i { width: 34px; height: 34px; border-radius: 10px; background: var(--grad-soft); color: #8B5CF6; display: grid; place-items: center; }

    .sum-row { display: flex; justify-content: space-between; align-items: center; font-size: .875rem; padding: .3rem 0; color: #5B6285; }
    .sum-row strong { color: var(--theme-color); font-variant-numeric: tabular-nums; }
    .grand { margin-top: .6rem; padding: .9rem 1rem; border-radius: 16px; background: var(--grad); background-size: 200% 200%; animation: grad-move 8s ease infinite; color: #fff; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 16px 30px -16px #8B5CF6; }
    .grand span { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; opacity: .9; }
    .grand strong { font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
    .disc-toggle { display: inline-flex; border: 1.5px solid #E3E5F2; border-radius: 10px; overflow: hidden; }
    .disc-toggle button { border: 0; background: #fff; padding: .3rem .6rem; font-size: .75rem; font-weight: 800; color: var(--muted); transition: background .2s, color .2s; }
    .disc-toggle button.active { background: var(--primary); color: #fff; }

    .pay-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
    .pay-chip { border: 1.5px solid #E3E5F2; background: #fff; border-radius: 13px; padding: .6rem .25rem; font-size: .75rem; font-weight: 800; color: #4B5272; display: flex; flex-direction: column; align-items: center; gap: .3rem; transition: transform .3s var(--spring), border-color .2s, background .2s, color .2s; }
    .pay-chip i { font-size: 1.125rem; color: var(--primary); transition: transform .35s var(--spring); }
    .pay-chip:hover { transform: translateY(-3px); border-color: #C4B5FD; background: var(--grad-soft); }
    .pay-chip:hover i { transform: scale(1.15) rotate(-6deg); }
    .pay-rows { display: flex; flex-direction: column; gap: .5rem; margin-top: .75rem; }
    .pay-row { display: grid; grid-template-columns: 96px 1fr 30px; gap: .5rem; align-items: center; animation: row-in .3s var(--spring) both; }
    .pay-row .badge { justify-self: start; }
    .pay-row__bank { grid-column: 1 / -1; }
    .quick-cash { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
    .quick-cash button { border: 1px dashed #C4B5FD; background: #FAF8FF; color: var(--primary-600); border-radius: 9px; padding: .25rem .6rem; font-size: .75rem; font-weight: 700; transition: transform .25s var(--spring), background .2s; }
    .quick-cash button:hover { transform: translateY(-2px); background: var(--primary-soft); }
    .balance { margin-top: .75rem; padding: .6rem .8rem; border-radius: 12px; font-weight: 800; font-size: .875rem; display: flex; justify-content: space-between; transition: background .3s, color .3s; }
    .balance--due { background: #FFF7ED; color: #C2410C; }
    .balance--change { background: #ECFDF5; color: #047857; }
    .balance--ok { background: #EEF0FF; color: var(--primary-600); }
    .pos-actions { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
    .pos-actions .themeBtn { width: 100%; justify-content: center; }
    .pos-actions .btn-complete { grid-column: 1 / -1; padding: .9rem; font-size: 1rem; }
    .pos-actions kbd { background: rgba(255, 255, 255, .25); color: inherit; font-size: .625rem; border-radius: 5px; padding: 1px 5px; margin-left: .35rem; }
    .themeBtn--gray kbd { background: #E3E5F2; }

    .pos-tabs { display: inline-flex; gap: .25rem; padding: .25rem; background: #F1F2FA; border-radius: 13px; }
    .pos-tabs button { border: 0; background: none; padding: .45rem .9rem; border-radius: 10px; font-size: .8125rem; font-weight: 700; color: #5B6285; transition: background .25s, color .2s, box-shadow .25s; }
    .pos-tabs button.active { background: #fff; color: var(--primary-600); box-shadow: var(--shadow-sm); }
    .pos-tab-pane { display: none; animation: page-in .35s var(--ease) backwards; }
    .pos-tab-pane.active { display: block; }
    .inv-no { font-weight: 800; color: var(--theme-color); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .icon-act { width: 32px; height: 32px; padding: 0; display: inline-grid; place-items: center; border-radius: 10px; }

    .success-check { width: 84px; height: 84px; margin: 0 auto 1rem; border-radius: 50%; background: linear-gradient(135deg, #10B981, #14B8A6); color: #fff; display: grid; place-items: center; font-size: 2.25rem; box-shadow: 0 18px 36px -16px #10B981; animation: pop .6s var(--spring) both; }

    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    @keyframes dot-pulse { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .5); } 70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

    @media (max-width: 1199px) { .pos { grid-template-columns: 1fr; } .pos-side { position: static; } }
    @media (max-width: 767px) {
        .cart-row { grid-template-columns: 1fr 1fr; }
        .cart-row > :first-child { grid-column: 1 / -1; }
        .cart-row__total { text-align: left; }
    }
</style>

<div class="pos">
    {{-- ============ Left: search + cart ============ --}}
    <div class="min-w-0">
        <div class="app-card">
            <div class="pos-search">
                <div class="pos-search__box">
                    <i class="fa-solid fa-barcode"></i>
                    <input type="text" id="productSearch" placeholder="Scan barcode or search product name / code…" autocomplete="off" autofocus aria-label="Search product">
                    <kbd>F2</kbd>
                </div>
                <div class="pos-results" id="productResults" role="listbox"></div>
            </div>

            <div class="cart-head">
                <h3>Cart <span class="cart-count" id="cartCount">0</span></h3>
                <button type="button" class="btn btn-sm btn-light text-danger" id="clearCart"><i class="fa-solid fa-broom me-1"></i>Clear <kbd class="ms-1">Esc</kbd></button>
            </div>
            <div class="cart-list" id="cartList"></div>
        </div>

        {{-- Recent / held --}}
        <div class="app-card mt-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="pos-tabs" role="tablist">
                    <button type="button" class="active" data-tab="recent"><i class="fa-solid fa-clock-rotate-left me-1"></i>Recent sales</button>
                    <button type="button" data-tab="held"><i class="fa-solid fa-pause me-1"></i>Held orders <span class="badge bg-warning ms-1">{{ $draftSales->count() }}</span></button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#returnModal"><i class="fa-solid fa-rotate-left me-1"></i>Return / Refund</button>
            </div>

            <div class="pos-tab-pane active" id="tab-recent">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Invoice</th><th>Date</th><th>Store</th><th>Customer</th><th class="text-end">Total</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        @forelse($recentSales as $s)
                            <tr>
                                <td class="inv-no">{{ $s->number }}</td>
                                <td>{{ \Carbon\Carbon::parse($s->date)->format('d M') }}</td>
                                <td>{{ $s->store->name ?? '—' }}</td>
                                <td>{{ $s->customer->name ?? 'Walk-in' }}</td>
                                <td class="text-end fw-bold">{{ $cur }} {{ number_format($s->grand_total, 2) }}</td>
                                <td>
                                    @php $badge = ['completed' => 'success', 'returned' => 'danger', 'void' => 'secondary'][$s->status] ?? 'warning'; @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst($s->status) }}</span>
                                    @if($s->status === 'completed' && $s->due_amount > 0)<span class="badge bg-warning">Due {{ number_format($s->due_amount) }}</span>@endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('sales.slip', $s->id) }}" target="_blank" class="btn btn-sm btn-light icon-act" title="Print receipt"><i class="fa-solid fa-print"></i></a>
                                    <a href="{{ route('sales.details', $s->id) }}" class="btn btn-sm btn-light icon-act" title="Details"><i class="fa-solid fa-eye"></i></a>
                                    @if($s->status === 'completed')
                                        <button type="button" class="btn btn-sm btn-light icon-act" title="Return items" onclick="openReturn('{{ $s->id }}')"><i class="fa-solid fa-rotate-left"></i></button>
                                        @if($canVoid)
                                            <button type="button" class="btn btn-sm btn-light icon-act text-danger" title="Void sale" onclick="voidSale({{ $s->id }}, '{{ $s->number }}')"><i class="fa-solid fa-ban"></i></button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No sales yet today — your first one is a scan away.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pos-tab-pane" id="tab-held">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Date</th><th>Store</th><th>Customer</th><th class="text-end">Total</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        @forelse($draftSales as $d)
                            <tr id="held-{{ $d->id }}">
                                <td class="inv-no">#{{ $d->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($d->date)->format('d M') }}</td>
                                <td>{{ $d->store->name ?? '—' }}</td>
                                <td>{{ $d->customer->name ?? 'Walk-in' }}</td>
                                <td class="text-end fw-bold">{{ $cur }} {{ number_format($d->grand_total, 2) }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('sales.edit', $d->id) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-play me-1"></i>Resume</a>
                                    <button type="button" class="btn btn-sm btn-light icon-act text-danger" title="Delete" onclick="deleteHeld({{ $d->id }})"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No held orders.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Right: store, customer, totals, payment ============ --}}
    <div class="pos-side">
        <div class="side-card">
            <div class="side-card__title"><i class="fa-solid fa-store"></i> Store & shift</div>
            @if($canPickStore)
                <select class="form-select" id="storeId" aria-label="Store">
                    <option value="">Select store…</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $defaultStoreId == $st->id ? 'selected' : '' }}>{{ $st->name }} ({{ $st->code }})</option>
                    @endforeach
                </select>
            @elseif($stores->first())
                <input type="hidden" id="storeId" value="{{ $stores->first()->id }}">
                <div class="store-lock"><i class="fa-solid fa-lock"></i>{{ $stores->first()->name }} <small class="text-muted fw-normal">{{ $stores->first()->code }}</small></div>
            @else
                <input type="hidden" id="storeId" value="">
                <div class="text-danger fw-semibold small">You are not assigned to any store. Ask an admin to assign one.</div>
            @endif
            <div id="shiftBox"></div>
        </div>

        <div class="side-card">
            <div class="side-card__title"><i class="fa-solid fa-user"></i> Customer</div>
            <select class="form-select" id="customerId" aria-label="Customer">
                <option value="walk-in">Walk-in customer</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ $isResume && $sale->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }} · {{ $c->phone }}</option>
                @endforeach
            </select>
            <div class="row g-2 mt-1" id="walkInFields">
                <div class="col-6"><input type="text" class="form-control" id="customerName" placeholder="Name"></div>
                <div class="col-6"><input type="text" class="form-control" id="customerPhone" placeholder="Phone"></div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-6"><input type="date" class="form-control" id="saleDate" value="{{ $isResume ? $sale->date : date('Y-m-d') }}" aria-label="Date"></div>
                <div class="col-6"><input type="text" class="form-control" id="referenceNo" placeholder="Reference (optional)" value="{{ $sale->reference_no ?? '' }}"></div>
            </div>
        </div>

        <div class="side-card">
            <div class="side-card__title"><i class="fa-solid fa-calculator"></i> Bill</div>
            <div class="sum-row"><span>Subtotal</span><strong id="sumSubtotal">0.00</strong></div>
            <div class="sum-row"><span>Item discounts</span><strong id="sumLineDisc">0.00</strong></div>
            <div class="sum-row">
                <span class="d-flex align-items-center gap-2">Discount
                    <span class="disc-toggle" role="group" aria-label="Discount type">
                        <button type="button" data-type="fixed" class="active">{{ $cur }}</button>
                        <button type="button" data-type="percent">%</button>
                    </span>
                </span>
                <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" id="discountValue" style="width: 96px" value="{{ $isResume ? (float) $sale->discount_value : 0 }}" aria-label="Discount">
            </div>
            <div class="sum-row"><span>Tax</span><strong id="sumTax">0.00</strong></div>
            <div class="grand"><span>Total</span><strong id="sumGrand">{{ $cur }} 0.00</strong></div>
        </div>

        <div class="side-card">
            <div class="side-card__title"><i class="fa-solid fa-wallet"></i> Payment</div>
            <div class="pay-methods">
                <button type="button" class="pay-chip" data-method="cash"><i class="fa-solid fa-money-bill-wave"></i>Cash</button>
                <button type="button" class="pay-chip" data-method="card"><i class="fa-solid fa-credit-card"></i>Card</button>
                <button type="button" class="pay-chip" data-method="bank_transfer"><i class="fa-solid fa-building-columns"></i>Bank</button>
            </div>
            <div class="pay-rows" id="payRows"></div>
            <div class="quick-cash" id="quickCash"></div>
            <div class="balance balance--ok" id="balanceBox"><span>Add items to start</span><span></span></div>
        </div>

        <div class="pos-actions">
            <button type="button" class="themeBtn themeBtn--green btn-complete" id="btnComplete"><i class="fa-solid fa-circle-check"></i> Complete sale <kbd>F9</kbd></button>
            <button type="button" class="themeBtn themeBtn--gray" id="btnHold"><i class="fa-solid fa-pause"></i> Hold <kbd>F8</kbd></button>
            <a href="{{ route('sales.index') }}" class="themeBtn themeBtn--gray"><i class="fa-solid fa-plus"></i> New</a>
        </div>
    </div>
</div>

{{-- Return modal --}}
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnModalTitle"><i class="fa-solid fa-rotate-left me-2 text-primary"></i>Return / Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="returnLookup" placeholder="Invoice number (e.g. ST-001-000012) or sale ID">
                    <button class="themeBtn" type="button" id="returnFind"><i class="fa-solid fa-magnifying-glass"></i> Find</button>
                </div>
                <div id="returnBody" class="text-muted small">Look up the original invoice. You can return some or all of its items.</div>
            </div>
            <div class="modal-footer d-none" id="returnFooter">
                <div class="me-auto fw-bold">Refund: <span id="returnTotal" class="text-primary">0.00</span></div>
                <button type="button" class="themeBtn themeBtn--gray" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="themeBtn themeBtn--red" id="returnSubmit"><i class="fa-solid fa-check"></i> Process return</button>
            </div>
        </div>
    </div>
</div>

{{-- Success modal --}}
<div class="modal fade" id="doneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-4">
            <div class="success-check"><i class="fa-solid fa-check"></i></div>
            <h5 class="fw-bold mb-1" id="doneTitle">Sale completed</h5>
            <p class="text-muted small mb-1" id="doneInvoice"></p>
            <div class="fs-4 fw-bold text-success mb-3" id="doneChange"></div>
            <div class="d-grid gap-2">
                <a href="#" target="_blank" class="themeBtn" id="donePrint"><i class="fa-solid fa-print"></i> Print receipt</a>
                <a href="{{ route('sales.index') }}" class="themeBtn themeBtn--gray">New sale <kbd>Enter</kbd></a>
            </div>
        </div>
    </div>
</div>

{{-- Open shift modal --}}
<div class="modal fade" id="shiftModal" tabindex="-1" aria-labelledby="shiftModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="shiftModalTitle"><i class="fa-solid fa-cash-register me-2 text-primary"></i>Open shift</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <label class="form-label" for="openingCash">Opening cash in drawer</label>
                <input type="number" min="0" step="0.01" class="form-control" id="openingCash" value="0">
            </div>
            <div class="modal-footer"><button type="button" class="themeBtn w-100 justify-content-center" id="shiftOpenBtn">Open shift</button></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const CUR = @json($cur);
    const URLS = {
        search: @json(route('sales.search-products')),
        store: @json(route('sales.store')),
        update: @json($isResume ? route('sales.update', $sale->id) : null),
        saleJson: @json(url('sales')) + '/',
        shiftsCurrent: @json(url('shifts/current')),
        shiftsOpen: @json(url('shifts/open')),
    };
    const BANKS = @json($banks->map(fn ($b) => ['id' => $b->id, 'name' => $b->name . ' (' . $b->account_number . ')']));
    const METHOD_LABEL = { cash: 'Cash', card: 'Card', bank_transfer: 'Bank' };

    let cart = @json($resumeCart ?? []);
    let payments = [];
    let discountType = @json($isResume ? ($sale->discount_type ?? 'fixed') : 'fixed');
    let shift = @json($currentShift);
    let totals = { grand: 0 };

    const money = v => (Math.round(v * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const storeId = () => $('#storeId').val();
    const r2 = v => Math.round(v * 100) / 100;

    // ---------------- Product search ----------------
    let searchTimer, results = [], activeIdx = -1, lastQuery = '';
    const $results = $('#productResults');

    function search(q, thenAddExact) {
        lastQuery = q;
        $.get(URLS.search, { q: q, store_id: storeId() }, function (data) {
            if (q !== lastQuery) { return; }
            results = data;
            activeIdx = data.length ? 0 : -1;
            if (thenAddExact) {
                const pick = data.find(p => p.exact) || (data.length === 1 ? data[0] : null);
                if (pick) { addToCart(pick); closeResults(); $('#productSearch').val(''); return; }
            }
            renderResults();
        });
    }

    function renderResults() {
        if (!results.length) {
            $results.html('<div class="text-center text-muted small py-3"><i class="fa-regular fa-face-meh me-1"></i>No product found</div>').addClass('show');
            return;
        }
        $results.html(results.map((p, i) => {
            const pill = p.stock <= 0 ? 'stock-pill--out' : (p.stock <= 10 ? 'stock-pill--low' : '');
            return `<div class="pos-result ${i === activeIdx ? 'is-active' : ''}" data-i="${i}" role="option">
                <span class="pos-result__icon"><i class="fa-solid fa-box"></i></span>
                <div class="min-w-0"><div class="pos-result__name text-truncate">${esc(p.name)}</div>
                <div class="pos-result__meta">${esc(p.code || '')} ${p.barcode ? '· ' + esc(p.barcode) : ''} · <span class="stock-pill ${pill}">${p.stock} in stock</span></div></div>
                <div class="pos-result__price">${CUR} ${money(p.price)}</div></div>`;
        }).join('')).addClass('show');
    }
    const closeResults = () => { $results.removeClass('show'); activeIdx = -1; };

    $('#productSearch').on('input', function () {
        const q = this.value.trim();
        clearTimeout(searchTimer);
        if (q.length < 1) { closeResults(); return; }
        searchTimer = setTimeout(() => search(q, false), 180);
    }).on('keydown', function (e) {
        if (e.key === 'ArrowDown' && results.length) { e.preventDefault(); activeIdx = (activeIdx + 1) % results.length; renderResults(); }
        else if (e.key === 'ArrowUp' && results.length) { e.preventDefault(); activeIdx = (activeIdx - 1 + results.length) % results.length; renderResults(); }
        else if (e.key === 'Enter') {
            e.preventDefault();
            const q = this.value.trim();
            if (!q) { return; }
            if ($results.hasClass('show') && results[activeIdx] && lastQuery === q) {
                addToCart(results[activeIdx]); closeResults(); this.value = '';
            } else {
                clearTimeout(searchTimer);
                search(q, true); // scanner: exact match goes straight in
            }
        } else if (e.key === 'Escape') { closeResults(); }
    });
    $results.on('mousedown', '.pos-result', function (e) {
        e.preventDefault();
        addToCart(results[$(this).data('i')]); closeResults(); $('#productSearch').val('').focus();
    });
    $(document).on('click', e => { if (!$(e.target).closest('.pos-search').length) { closeResults(); } });

    // ---------------- Cart ----------------
    function addToCart(p) {
        if (!storeId()) { toast('Select a store first.', 'error'); return; }
        const existing = cart.find(i => i.id === p.id);
        const inCart = existing ? existing.quantity : 0;
        if (inCart + 1 > p.stock) { toast(`Only ${p.stock} of ${p.name} in stock at this store.`, 'error'); return; }
        if (existing) { existing.quantity += 1; existing.stock = p.stock; flashId = p.id; }
        else { cart.push({ id: p.id, name: p.name, code: p.code, price: p.price, tax_rate: p.tax_rate, stock: p.stock, quantity: 1, discount: 0 }); flashId = null; }
        renderCart();
    }
    let flashId = null;

    function renderCart() {
        const $list = $('#cartList');
        if (!cart.length) {
            $list.html(`<div class="cart-empty"><div class="cart-empty__icon"><i class="fa-solid fa-basket-shopping"></i></div>
                <div class="fw-bold text-dark mb-1">Cart is empty</div><div class="small">Scan a barcode or search a product to begin. Press <kbd>F2</kbd> to jump to search.</div></div>`);
        } else {
            $list.html(cart.map((i, idx) => {
                const gross = i.price * i.quantity, net = gross - Math.min(i.discount, gross);
                const total = net + net * i.tax_rate / 100;
                return `<div class="cart-row ${flashId === i.id ? 'flash' : ''}" data-idx="${idx}" style="animation-delay:${flashId ? 0 : idx * 25}ms">
                    <div class="min-w-0"><div class="cart-row__name text-truncate">${esc(i.name)}</div>
                        <div class="cart-row__meta">${CUR} ${money(i.price)} ${i.tax_rate ? '· tax ' + i.tax_rate + '%' : ''} · ${i.stock} in stock</div></div>
                    <div class="qty"><button type="button" class="q-minus" aria-label="Decrease">−</button><input type="number" class="q-input" min="0.001" step="any" value="${i.quantity}" aria-label="Quantity"><button type="button" class="q-plus" aria-label="Increase">+</button></div>
                    <input type="number" class="form-control line-disc" min="0" step="0.01" value="${i.discount || ''}" placeholder="Disc." aria-label="Item discount">
                    <div class="cart-row__total">${CUR} ${money(total)}</div>
                    <button type="button" class="cart-row__remove" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button></div>`;
            }).join(''));
        }
        flashId = null;
        $('#cartCount').text(cart.reduce((n, i) => n + 1, 0)).removeClass('bump').width();
        $('#cartCount').addClass('bump');
        recalc();
    }

    $('#cartList')
        .on('click', '.q-plus, .q-minus', function () {
            const idx = $(this).closest('.cart-row').data('idx'), item = cart[idx];
            const next = item.quantity + ($(this).hasClass('q-plus') ? 1 : -1);
            if (next > item.stock) { toast(`Only ${item.stock} in stock.`, 'error'); return; }
            if (next <= 0) { removeAt(idx); return; }
            item.quantity = next; renderCart();
        })
        .on('change', '.q-input', function () {
            const idx = $(this).closest('.cart-row').data('idx'), item = cart[idx];
            let v = parseFloat(this.value);
            if (!(v > 0)) { removeAt(idx); return; }
            if (v > item.stock) { toast(`Only ${item.stock} in stock.`, 'error'); v = item.stock; }
            item.quantity = v; renderCart();
        })
        .on('change', '.line-disc', function () {
            const idx = $(this).closest('.cart-row').data('idx'), item = cart[idx];
            const gross = item.price * item.quantity;
            item.discount = Math.min(Math.max(parseFloat(this.value) || 0, 0), gross);
            renderCart();
        })
        .on('click', '.cart-row__remove', function () { removeAt($(this).closest('.cart-row').data('idx')); });

    function removeAt(idx) {
        const $row = $(`#cartList .cart-row[data-idx="${idx}"]`).addClass('is-leaving');
        setTimeout(() => { cart.splice(idx, 1); renderCart(); }, 220);
    }

    $('#clearCart').on('click', clearAll);
    function clearAll() {
        if (!cart.length) { return; }
        Swal.fire({ title: 'Clear the cart?', icon: 'question', showCancelButton: true, confirmButtonText: 'Clear' })
            .then(r => { if (r.isConfirmed) { cart = []; payments = []; renderPayments(); renderCart(); $('#productSearch').focus(); } });
    }

    // ---------------- Totals (mirror of the server calculation; the server is the source of truth) ----------------
    function recalc() {
        let sub = 0, lineDisc = 0, tax = 0;
        cart.forEach(i => {
            const gross = r2(i.price * i.quantity), d = r2(Math.min(i.discount, gross));
            sub += gross; lineDisc += d; tax += r2((gross - d) * i.tax_rate / 100);
        });
        const base = sub - lineDisc;
        let dv = parseFloat($('#discountValue').val()) || 0;
        if (discountType === 'percent' && dv > 100) { dv = 100; $('#discountValue').val(100); }
        let inv = r2(discountType === 'percent' ? base * dv / 100 : dv);
        if (inv > base) { inv = base; $('#discountValue').val(r2(base)); }
        totals = { sub: r2(sub), lineDisc: r2(lineDisc), inv, tax: r2(tax), grand: r2(base - inv + tax) };

        $('#sumSubtotal').text(money(totals.sub));
        $('#sumLineDisc').text(money(totals.lineDisc + totals.inv));
        $('#sumTax').text(money(totals.tax));
        const $g = $('#sumGrand'); const txt = CUR + ' ' + money(totals.grand);
        if ($g.text() !== txt) { $g.text(txt).removeClass('bump').width(); $g.addClass('bump'); }
        renderQuickCash();
        renderBalance();
    }
    $('#discountValue').on('input', recalc);
    $('.disc-toggle button').on('click', function () {
        discountType = $(this).data('type');
        $('.disc-toggle button').removeClass('active'); $(this).addClass('active');
        recalc();
    });
    $(`.disc-toggle button[data-type="${discountType}"]`).trigger('click');

    // ---------------- Payments ----------------
    const paidTotal = () => r2(payments.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0));

    $('.pay-chip').on('click', function () {
        const method = $(this).data('method');
        const remaining = Math.max(0, r2(totals.grand - paidTotal()));
        payments.push({ method, amount: remaining || '', bank_id: method === 'bank_transfer' && BANKS.length === 1 ? BANKS[0].id : '' });
        renderPayments();
        $('#payRows .pay-amount').last().trigger('focus').trigger('select');
    });

    function renderPayments() {
        $('#payRows').html(payments.map((p, i) => `
            <div class="pay-row" data-i="${i}">
                <span class="badge bg-primary">${METHOD_LABEL[p.method]}</span>
                <input type="number" min="0" step="0.01" class="form-control form-control-sm pay-amount" value="${p.amount}" placeholder="Amount" aria-label="${METHOD_LABEL[p.method]} amount">
                <button type="button" class="cart-row__remove pay-remove" style="width:30px;height:30px" aria-label="Remove payment"><i class="fa-solid fa-xmark"></i></button>
                ${p.method === 'bank_transfer' ? `<select class="form-select form-select-sm pay-row__bank pay-bank" aria-label="Bank"><option value="">Select bank…</option>${BANKS.map(b => `<option value="${b.id}" ${String(p.bank_id) === String(b.id) ? 'selected' : ''}>${esc(b.name)}</option>`).join('')}</select>` : ''}
            </div>`).join(''));
        renderBalance();
    }
    $('#payRows')
        .on('input', '.pay-amount', function () { payments[$(this).closest('.pay-row').data('i')].amount = this.value; renderBalance(); })
        .on('change', '.pay-bank', function () { payments[$(this).closest('.pay-row').data('i')].bank_id = this.value; })
        .on('click', '.pay-remove', function () { payments.splice($(this).closest('.pay-row').data('i'), 1); renderPayments(); });

    function renderQuickCash() {
        const g = totals.grand;
        if (!(g > 0)) { $('#quickCash').empty(); return; }
        const notes = [...new Set([g, Math.ceil(g / 100) * 100, Math.ceil(g / 500) * 500, Math.ceil(g / 1000) * 1000, Math.ceil(g / 5000) * 5000])].slice(0, 4);
        $('#quickCash').html(notes.map((n, i) => `<button type="button" data-v="${n}">${i === 0 ? 'Exact' : CUR + ' ' + n.toLocaleString()}</button>`).join(''));
    }
    $('#quickCash').on('click', 'button', function () {
        const v = parseFloat($(this).data('v'));
        const cash = payments.find(p => p.method === 'cash');
        const others = r2(payments.filter(p => p !== cash).reduce((s, p) => s + (parseFloat(p.amount) || 0), 0));
        if (cash) { cash.amount = r2(v - others > 0 ? v - others : v); } else { payments.push({ method: 'cash', amount: v, bank_id: '' }); }
        renderPayments();
    });

    function renderBalance() {
        const $b = $('#balanceBox').removeClass('balance--due balance--change balance--ok');
        if (!cart.length) { $b.addClass('balance--ok').html('<span>Add items to start</span><span></span>'); return; }
        const diff = r2(paidTotal() - totals.grand);
        if (diff < -0.009) { $b.addClass('balance--due').html(`<span><i class="fa-solid fa-hourglass-half me-1"></i>${payments.length ? 'Remaining (on credit)' : 'To pay'}</span><span>${CUR} ${money(-diff)}</span>`); }
        else if (diff > 0.009) { $b.addClass('balance--change').html(`<span><i class="fa-solid fa-coins me-1"></i>Change to give</span><span>${CUR} ${money(diff)}</span>`); }
        else { $b.addClass('balance--ok').html('<span><i class="fa-solid fa-circle-check me-1"></i>Fully paid</span><span></span>'); }
    }

    /** Amounts applied to the bill: any excess is change and comes off the cash line. */
    function appliedPayments() {
        let excess = r2(paidTotal() - totals.grand);
        const list = payments.filter(p => parseFloat(p.amount) > 0).map(p => ({ method: p.method, amount: r2(parseFloat(p.amount)), bank_id: p.bank_id || null }));
        if (excess > 0) {
            const cash = list.find(p => p.method === 'cash');
            if (!cash || cash.amount < excess) { return { error: 'Only cash can be more than the bill (to give change). Fix the card/bank amount.' }; }
            cash.amount = r2(cash.amount - excess);
        }
        return { list: list.filter(p => p.amount > 0), change: Math.max(0, excess) };
    }

    // ---------------- Customer ----------------
    $('#customerId').on('change', function () { $('#walkInFields').toggle(this.value === 'walk-in'); }).trigger('change');

    // ---------------- Shift ----------------
    function renderShift() {
        const $box = $('#shiftBox');
        if (!storeId()) { $box.empty(); return; }
        if (shift) {
            $box.html(`<div class="shift-pill shift-pill--open"><span class="shift-dot"></span>Shift open since ${esc(shift.opened_at)}</div>`);
        } else {
            $box.html(`<div class="shift-pill shift-pill--closed"><i class="fa-solid fa-triangle-exclamation"></i>No open shift
                <button type="button" class="btn btn-sm btn-outline-primary ms-auto py-0" data-bs-toggle="modal" data-bs-target="#shiftModal">Open</button></div>`);
        }
    }
    function loadShift() {
        if (!storeId()) { shift = null; renderShift(); return; }
        $.getJSON(URLS.shiftsCurrent, { store_id: storeId() })
            .done(r => { shift = r.shift ? { id: r.shift.id, opened_at: new Date(r.shift.opened_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) } : null; renderShift(); })
            .fail(() => renderShift());
    }
    $('#storeId').on('change', function () {
        if (cart.length) { toast('Stock shown in the cart is for the previous store; it is re-checked when you complete the sale.', 'warning'); }
        loadShift();
    });
    $('#shiftOpenBtn').on('click', function () {
        const $btn = $(this).prop('disabled', true);
        $.ajax({ url: URLS.shiftsOpen, method: 'POST', headers: { Accept: 'application/json' }, data: { store_id: storeId(), opening_cash: $('#openingCash').val() || 0 } })
            .done(() => { bootstrap.Modal.getInstance(document.getElementById('shiftModal')).hide(); toast('Shift opened. Happy selling!', 'success'); loadShift(); })
            .fail(x => toast((x.responseJSON && (x.responseJSON.message || Object.values(x.responseJSON.errors || {})[0])) || 'Could not open shift.', 'error'))
            .always(() => $btn.prop('disabled', false));
    });

    // ---------------- Submit ----------------
    let submitting = false;
    function submit(status) {
        if (submitting) { return; }
        if (!cart.length) { toast('Add items to the cart first.', 'error'); $('#productSearch').focus(); return; }
        if (!storeId()) { toast('Select the store for this sale.', 'error'); return; }
        const walkIn = $('#customerId').val() === 'walk-in';
        if (walkIn && (!$('#customerName').val().trim() || !$('#customerPhone').val().trim())) {
            toast('Enter the walk-in customer name and phone.', 'error'); $('#customerName').focus(); return;
        }
        let pay = { list: [], change: 0 };
        if (status === 'completed') {
            pay = appliedPayments();
            if (pay.error) { toast(pay.error, 'error'); return; }
            if (payments.some(p => p.method === 'bank_transfer' && !p.bank_id && parseFloat(p.amount) > 0)) { toast('Select the bank for the bank payment.', 'error'); return; }
            const paid = pay.list.reduce((s, p) => s + p.amount, 0);
            if (paid < totals.grand - 0.009 && walkIn) { toast('Walk-in customers must pay in full. Select a saved customer to sell on credit.', 'error'); return; }
            if (!shift) { toast('Open a shift before completing sales.', 'error'); new bootstrap.Modal('#shiftModal').show(); return; }
        }

        submitting = true;
        const $btn = $(status === 'completed' ? '#btnComplete' : '#btnHold').prop('disabled', true);
        $.ajax({
            url: URLS.update || URLS.store,
            method: URLS.update ? 'PUT' : 'POST',
            headers: { Accept: 'application/json' },
            data: {
                status,
                store_id: storeId(),
                customer_id: $('#customerId').val(),
                customer_name: $('#customerName').val(),
                customer_phone: $('#customerPhone').val(),
                date: $('#saleDate').val(),
                reference_no: $('#referenceNo').val(),
                discount_type: discountType,
                discount_value: parseFloat($('#discountValue').val()) || 0,
                items: cart.map(i => ({ product_id: i.id, quantity: i.quantity, discount: i.discount || 0 })),
                payments: pay.list,
            },
        }).done(r => {
            if (status === 'draft') { toast(r.message, 'success'); setTimeout(() => location.href = @json(route('sales.index')), 600); return; }
            celebrate();
            $('#doneTitle').text(r.due > 0 ? 'Sale completed on credit' : 'Sale completed');
            $('#doneInvoice').text(r.invoice_no + (r.due > 0 ? ' · ' + CUR + ' ' + money(r.due) + ' due' : ''));
            $('#doneChange').text(pay.change > 0 ? 'Change: ' + CUR + ' ' + money(pay.change) : '');
            $('#donePrint').attr('href', URLS.saleJson + r.sale_id + '/slip');
            new bootstrap.Modal('#doneModal', { backdrop: 'static' }).show();
        }).fail(x => {
            const j = x.responseJSON || {};
            toast(j.message || Object.values(j.errors || {})[0] || 'Something went wrong.', 'error');
            if (j.code === 'no_shift') { shift = null; renderShift(); new bootstrap.Modal('#shiftModal').show(); }
        }).always(() => { submitting = false; $btn.prop('disabled', false); });
    }
    $('#btnComplete').on('click', () => submit('completed'));
    $('#btnHold').on('click', () => submit('draft'));

    // ---------------- Keyboard ----------------
    document.addEventListener('keydown', e => {
        if ($('.modal.show').length) {
            if (e.key === 'Enter' && $('#doneModal').hasClass('show')) { location.href = @json(route('sales.index')); }
            return;
        }
        if (e.key === 'F2') { e.preventDefault(); $('#productSearch').focus().select(); }
        else if (e.key === 'F9') { e.preventDefault(); submit('completed'); }
        else if (e.key === 'F8') { e.preventDefault(); submit('draft'); }
        else if (e.key === 'Escape' && !$results.hasClass('show') && document.activeElement.id !== 'productSearch') { clearAll(); }
    });

    // ---------------- Tabs ----------------
    $('.pos-tabs button').on('click', function () {
        $('.pos-tabs button').removeClass('active'); $(this).addClass('active');
        $('.pos-tab-pane').removeClass('active'); $('#tab-' + $(this).data('tab')).addClass('active');
    });

    // ---------------- Held orders, void ----------------
    window.deleteHeld = function (id) {
        Swal.fire({ title: 'Delete this held order?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' }).then(r => {
            if (!r.isConfirmed) { return; }
            $.ajax({ url: URLS.saleJson + id, method: 'DELETE', headers: { Accept: 'application/json' } })
                .done(res => { toast(res.message, 'success'); $('#held-' + id).fadeOut(250, function () { $(this).remove(); }); })
                .fail(x => toast((x.responseJSON || {}).message || 'Could not delete.', 'error'));
        });
    };
    window.voidSale = function (id, number) {
        Swal.fire({ title: 'Void ' + number + '?', text: 'Stock goes back and payments are reversed. This cannot be undone.', input: 'text', inputPlaceholder: 'Reason (required)', icon: 'warning', showCancelButton: true, confirmButtonText: 'Void sale',
            inputValidator: v => !v && 'A reason is required' }).then(r => {
            if (!r.isConfirmed) { return; }
            $.ajax({ url: URLS.saleJson + id + '/void', method: 'POST', headers: { Accept: 'application/json' }, data: { reason: r.value } })
                .done(res => { toast(res.message, 'success'); setTimeout(() => location.reload(), 700); })
                .fail(x => toast((x.responseJSON || {}).message || 'Could not void.', 'error'));
        });
    };

    // ---------------- Returns ----------------
    let returnSale = null;
    window.openReturn = function (id) { new bootstrap.Modal('#returnModal').show(); $('#returnLookup').val(id); findReturn(); };
    $('#returnFind').on('click', findReturn);
    $('#returnLookup').on('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); findReturn(); } });

    function findReturn() {
        const q = $('#returnLookup').val().trim();
        if (!q) { return; }
        $('#returnBody').html('<div class="app-spinner"></div>'); $('#returnFooter').addClass('d-none');
        $.getJSON(URLS.saleJson + encodeURIComponent(q) + '/json').done(r => {
            const s = r.sale; returnSale = s;
            if (s.status !== 'completed') { $('#returnBody').html(`<div class="alert alert-warning mb-0">This sale is <b>${esc(s.status)}</b>; only completed sales can be returned.</div>`); return; }
            const rows = s.items.map(i => `<tr data-id="${i.id}" data-total="${i.total}" data-qty="${i.quantity}">
                <td><b>${esc(i.product ? i.product.name : 'Product')}</b></td><td class="text-end">${parseFloat(i.quantity)}</td><td class="text-end">${parseFloat(i.returned_qty)}</td>
                <td style="width:120px"><input type="number" class="form-control form-control-sm ret-qty" min="0" max="${i.returnable}" step="any" value="0" ${i.returnable <= 0 ? 'disabled' : ''} aria-label="Return quantity"></td></tr>`).join('');
            $('#returnBody').html(`<div class="d-flex justify-content-between mb-2 small"><span><b>${esc(s.invoice_no || '#' + s.id)}</b> · ${esc(s.store ? s.store.name : '')} · ${esc(s.customer ? s.customer.name : 'Walk-in')}</span><span>Total ${CUR} ${money(s.grand_total)}${parseFloat(s.due_amount) > 0 ? ' · due ' + money(s.due_amount) : ''}</span></div>
                <table class="table table-sm align-middle"><thead><tr><th>Item</th><th class="text-end">Sold</th><th class="text-end">Returned</th><th>Return now</th></tr></thead><tbody>${rows}</tbody></table>
                <div class="row g-2"><div class="col-md-4"><label class="form-label">Refund by</label><select class="form-select" id="refundMethod"><option value="cash">Cash</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option></select></div>
                <div class="col-md-4 d-none" id="refundBankWrap"><label class="form-label">Bank</label><select class="form-select" id="refundBank"><option value="">Select…</option>${BANKS.map(b => `<option value="${b.id}">${esc(b.name)}</option>`).join('')}</select></div>
                <div class="col-md-4"><label class="form-label">Reason</label><input class="form-control" id="refundReason" placeholder="e.g. damaged"></div></div>`);
            $('#returnFooter').removeClass('d-none'); updateReturnTotal();
        }).fail(() => $('#returnBody').html('<div class="alert alert-danger mb-0">Sale not found.</div>'));
    }
    $('#returnBody').on('input', '.ret-qty', updateReturnTotal).on('change', '#refundMethod', function () { $('#refundBankWrap').toggleClass('d-none', this.value !== 'bank_transfer'); });
    function updateReturnTotal() {
        // Estimate only; the server applies the invoice discount share and any unpaid balance
        let t = 0;
        $('#returnBody tr[data-id]').each(function () { const q = parseFloat($(this).find('.ret-qty').val()) || 0; t += q / parseFloat($(this).data('qty')) * parseFloat($(this).data('total')); });
        $('#returnTotal').text(CUR + ' ' + money(t));
    }
    $('#returnSubmit').on('click', function () {
        const items = []; $('#returnBody tr[data-id]').each(function () { const q = parseFloat($(this).find('.ret-qty').val()) || 0; if (q > 0) { items.push({ sale_item_id: $(this).data('id'), quantity: q }); } });
        if (!items.length) { toast('Enter a quantity to return.', 'error'); return; }
        const $btn = $(this).prop('disabled', true);
        $.ajax({ url: URLS.saleJson + returnSale.id + '/return', method: 'POST', headers: { Accept: 'application/json' }, data: { items, refund_method: $('#refundMethod').val(), bank_id: $('#refundBank').val(), reason: $('#refundReason').val() } })
            .done(r => { toast(r.message, 'success'); bootstrap.Modal.getInstance(document.getElementById('returnModal')).hide(); window.open(URLS.saleJson + r.sale_id + '/slip', '_blank'); setTimeout(() => location.reload(), 800); })
            .fail(x => toast((x.responseJSON || {}).message || 'Could not process the return.', 'error'))
            .always(() => $btn.prop('disabled', false));
    });

    // ---------------- Init ----------------
    renderCart();
    renderPayments();
    renderShift();
    if (!shift && storeId()) { loadShift(); }
})();
</script>
@endpush
