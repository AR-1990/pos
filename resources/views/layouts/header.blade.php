<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $globalSettings->company_name ?? 'Koder360' }} | {{ $pageTitle ?? Str::headline(request()->segment(1) ?: 'Dashboard') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v=6">
    <script>
        // Apply saved palette + collapsed sidebar before first paint
        (function () {
            try {
                var t = localStorage.getItem('pos-theme');
                if (t) { document.documentElement.setAttribute('data-theme', t); }
                if (localStorage.getItem('pos-nav-collapsed') === '1') { document.documentElement.classList.add('nav-collapsed'); }
            } catch (e) {}
        })();
    </script>
</head>

@php
    $authUser = auth()->user();
    $can = fn ($module, $action = 'view') => $authUser->hasPermission($module, $action);
    $canAny = fn (array $modules) => collect($modules)->contains(fn ($m) => $authUser->hasPermission($m, 'view'));

    // Sidebar: [label, icon, url, permission module]
    $navGroups = [
        'Inventory' => ['fa-boxes-stacked', [
            ['Products', 'fa-box', url('all-products'), 'products'],
            ['Stock Levels', 'fa-layer-group', url('stock'), 'stock_ledger'],
            ['Stock Ledger', 'fa-list-ol', url('stock/movements'), 'stock_ledger'],
            ['Purchases', 'fa-truck-ramp-box', url('purchases'), 'purchases'],
            ['Transfers', 'fa-right-left', url('stock-transfers'), 'stock_transfers'],
            ['Adjustments', 'fa-sliders', url('stock-adjustments'), 'stock_adjustments'],
            ['Barcode Labels', 'fa-barcode', url('products/labels'), 'products'],
        ]],
        'Parties' => ['fa-users', [
            ['Customers', 'fa-user', url('customer'), 'customers'],
            ['Customer Credit', 'fa-hand-holding-dollar', url('customer-ledger'), 'customer_credit'],
            ['Vendors', 'fa-industry', url('vendor'), 'vendors'],
            ['Vendor Payments', 'fa-money-check-dollar', url('vendor-payments'), 'vendor_payments'],
        ]],
        'Cash & Accounts' => ['fa-wallet', [
            ['Shifts / Register', 'fa-cash-register', url('shifts'), 'shifts'],
            ['Cashbook', 'fa-book', url('cashbook'), 'cashbook'],
            ['Expenses', 'fa-receipt', url('expense'), 'expenses'],
            ['Payroll', 'fa-file-invoice-dollar', url('payroll'), 'payroll'],
            ['Loan & Advances', 'fa-handshake', url('loan-and-advances'), 'loans'],
        ]],
        'Reports' => ['fa-chart-pie', [
            ['Sales Report', 'fa-chart-line', route('reports.sales'), 'reports_sales'],
            ['Return Report', 'fa-rotate-left', route('reports.returns'), 'reports_returns'],
            ['Stock Report', 'fa-warehouse', route('reports.stock'), 'reports_stock'],
            ['Top Products', 'fa-trophy', route('reports.top-products'), 'reports_top_products'],
            ['Profit / Loss', 'fa-scale-balanced', url('profit-report'), 'reports_profit'],
            ['Revenue', 'fa-sack-dollar', url('revenue-report'), 'reports_revenue'],
        ]],
        'Administration' => ['fa-shield-halved', [
            ['Stores', 'fa-store', url('stores'), 'stores'],
            ['Users', 'fa-user-gear', route('users.index'), 'users'],
            ['Roles & Permissions', 'fa-user-shield', route('roles.index'), 'roles'],
            ['Audit Log', 'fa-clock-rotate-left', url('audit-logs'), 'audit_logs'],
            ['System Settings', 'fa-gear', url('system-settings'), 'settings_system'],
            ['Taxes & Masters', 'fa-percent', url('taxes'), 'taxes'],
        ]],
    ];
@endphp

<body>

    <div class="dashboard-wrapper">
        <div class="sidebar-overlay"></div>
        <aside class="dashboard-sidebar app-sidebar">
            <div class="app-sidebar__top">
                <a href="{{ url('/') }}" class="app-brand">
                    <span class="app-brand__mark"><i class="fa-solid fa-bolt"></i></span>
                    <span class="app-brand__name">{{ $globalSettings->company_name ?? 'Koder360' }}<small>Point of Sale</small></span>
                </a>
                <button type="button" class="app-collapse-btn" id="navCollapseBtn" title="Collapse menu" aria-label="Collapse menu"><i class="fa-solid fa-angles-left"></i></button>
            </div>

            <nav class="app-nav">
                @if($can('dashboard'))
                    <a href="{{ url('/') }}" class="app-nav__link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                @endif
                @if($can('sales'))
                    <a href="{{ url('sales') }}" class="app-nav__link app-nav__link--pos"><i class="fa-solid fa-cash-register"></i><span>POS / New Sale</span><kbd>F1</kbd></a>
                @endif

                @foreach($navGroups as $groupName => [$groupIcon, $items])
                    @php $visible = array_filter($items, fn ($i) => $can($i[3])); @endphp
                    @if(count($visible))
                        @php $gid = 'nav-' . Str::slug($groupName); @endphp
                        <div class="app-nav__group">
                            <button class="app-nav__group-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $gid }}" aria-expanded="false">
                                <i class="fa-solid {{ $groupIcon }}"></i><span>{{ $groupName }}</span><i class="fa-solid fa-chevron-down app-nav__chev"></i>
                            </button>
                            <div class="collapse" id="{{ $gid }}">
                                @foreach($visible as [$label, $icon, $href])
                                    <a href="{{ $href }}" class="app-nav__sublink"><i class="fa-solid {{ $icon }}"></i><span>{{ $label }}</span></a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="app-sidebar__user">
                <span class="app-avatar">{{ strtoupper(Str::substr($authUser->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <div class="app-sidebar__user-name text-truncate">{{ $authUser->name }}</div>
                    <div class="app-sidebar__user-role text-truncate">{{ $authUser->role->name ?? 'User' }}@if($authUser->store) · {{ $authUser->store->name }}@endif</div>
                </div>
            </div>
        </aside>

        <div class="dashboard-content">
            <header class="header app-topbar">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <a href="#" class="app-icon-btn sidebar-toggle d-xl-none" aria-label="Open menu"><i class="fa-solid fa-bars"></i></a>
                    <h1 class="secHeading text-truncate">{{ $pageTitle ?? Str::headline(request()->segment(1) ?: 'Dashboard') }}</h1>
                </div>
                <div class="header-links">
                    @if($authUser->canViewAllStores())
                        @php $headerStores = \App\Models\Store::where('status', 'Active')->orderBy('name')->get(['id', 'name']); @endphp
                        <form action="{{ route('stores.switch') }}" method="POST" class="header-store-switch" title="Show data for">
                            @csrf
                            <i class="fa-solid fa-store"></i>
                            <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Show data for store">
                                <option value="">All Stores</option>
                                @foreach($headerStores as $hs)
                                    <option value="{{ $hs->id }}" {{ session('store_filter') == $hs->id ? 'selected' : '' }}>{{ $hs->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @elseif($authUser->store)
                        <span class="header-store-switch header-store-switch--static" title="Your store">
                            <i class="fa-solid fa-store"></i> {{ $authUser->store->name }}
                        </span>
                    @endif

                    <div class="dropdown">
                        <a href="#" class="app-icon-btn" id="themeDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Change colours" title="Change colours">
                            <i class="fa-solid fa-palette"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end app-dropdown" aria-labelledby="themeDropdown">
                            <div class="app-dropdown__head">Colour theme</div>
                            <div class="theme-swatches">
                                @foreach(['ocean' => 'Ocean', 'sunset' => 'Sunset', 'mint' => 'Mint', 'berry' => 'Berry', 'sky' => 'Sky'] as $key => $label)
                                    <div class="text-center">
                                        <button type="button" class="theme-swatch" data-t="{{ $key }}" aria-label="{{ $label }} theme"></button>
                                        <div class="theme-swatch-label">{{ $label }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="dropdown">
                        <a href="#" class="app-icon-btn" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="fa-regular fa-bell"></i>
                            @if(isset($notifications) && count($notifications) > 0)
                                <span class="app-icon-btn__badge">{{ count($notifications) }}</span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end app-dropdown app-notify" aria-labelledby="notificationDropdown">
                            <div class="app-dropdown__head">Notifications</div>
                            <div class="app-notify__list">
                                @forelse($notifications ?? [] as $notification)
                                    <div class="app-notify__item">
                                        <span class="app-notify__icon app-notify__icon--{{ $notification['type'] }}"><i class="fa-solid {{ $notification['icon'] }}"></i></span>
                                        <div class="min-w-0">
                                            <div class="app-notify__title">{{ $notification['title'] }}</div>
                                            <div class="app-notify__msg">{{ $notification['message'] }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-4 small"><i class="fa-regular fa-circle-check d-block fs-4 mb-2"></i>You're all caught up</div>
                                @endforelse
                            </div>
                            @if($can('reports_stock'))
                                <a href="{{ route('reports.stock') }}" class="app-dropdown__foot">View stock report</a>
                            @endif
                        </div>
                    </div>

                    <div class="dropdown">
                        <a href="#" class="app-user-btn" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="app-avatar app-avatar--sm">{{ strtoupper(Str::substr($authUser->name, 0, 1)) }}</span>
                            <i class="fa-solid fa-chevron-down small text-muted"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end app-dropdown" aria-labelledby="profileDropdown">
                            <li class="app-dropdown__head">{{ $authUser->name }}<small class="d-block text-muted fw-normal">{{ $authUser->email }}</small></li>
                            <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="fa-solid fa-key me-2"></i>Change Password</a></li>
                            @if($can('settings_system'))
                                <li><a class="dropdown-item" href="{{ route('system-settings.index') }}"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            <main class="app-main">
