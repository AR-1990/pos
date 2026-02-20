<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Koder360 | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
</head>

<body>

    <div class="dashboard-wrapper">
        <div class="sidebar-overlay"></div>
        <div class="dashboard-sidebar">
            <a href="{{ url('/') }}" class="dashboard-sidebar__logo">
                <img src="{{ isset($globalSettings->company_logo) && $globalSettings->company_logo ? asset($globalSettings->company_logo) : asset('assets/images/logo.png') }}" alt="image" class="img-fluid">
            </a>
            <ul class="dashboard-sidebar__list">
                @permission('dashboard', 'view')
                <li>
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('assets/images/sidebar-icon1.png') }}" alt="image" class="img-fluid">
                        Dashboard
                    </a>
                </li>
                @endpermission
                
                @permission('sales', 'view')
                <li>
                    <a href="{{ url('sales') }}">
                        <img src="{{ asset('assets/images/sidebar-icon2.png') }}" alt="image" class="img-fluid">
                        Sales
                    </a>
                </li>
                @endpermission

                @permission('products', 'view')
                <li>
                    <a href="{{ url('all-products') }}">
                        <img src="{{ asset('assets/images/sidebar-icon3.png') }}" alt="image" class="img-fluid">
                        Products
                    </a>
                </li>
                @endpermission

                @if(auth()->user()->hasPermission('cashbook', 'view') || 
                    auth()->user()->hasPermission('expenses', 'view') || 
                    auth()->user()->hasPermission('payroll', 'view') || 
                    auth()->user()->hasPermission('loans', 'view'))
                <li>
                    <div class="dashboard-sidebar__item">
                        <a href="{{ url('accounts') }}" class="dashboard-sidebar__link">
                            <img src="{{ asset('assets/images/sidebar-icon4.png') }}" alt="image" class="img-fluid">
                            Accounts
                        </a>
                        <button class="dashboard-sidebar__toggle" type="button" data-bs-toggle="collapse"
                            data-bs-target="#accountsSubmenu" aria-expanded="false" aria-controls="accountsSubmenu">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>
                    <ul class="collapse dashboard-sidebar__submenu" id="accountsSubmenu">
                        @permission('cashbook', 'view')
                        <li>
                            <a href="{{ url('cashbook') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Cashbook
                            </a>
                        </li>
                        @endpermission
                        @permission('expenses', 'view')
                        <li>
                            <a href="{{ url('expense') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Expenses
                            </a>
                        </li>
                        @endpermission
                        @permission('payroll', 'view')
                        <li>
                            <a href="{{ url('payroll') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Payroll
                            </a>
                        </li>
                        @endpermission
                        @permission('loans', 'view')
                        <li>
                            <a href="{{ url('loan-and-advances') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Loan & Adv
                            </a>
                        </li>
                        @endpermission
                    </ul>
                </li>
                @endif

                @if(auth()->user()->hasPermission('users', 'view') || auth()->user()->hasPermission('roles', 'view'))
                <li>
                    <div class="dashboard-sidebar__item">
                        <a href="javascript:void(0)" class="dashboard-sidebar__link">
                            <img src="{{ asset('assets/images/sidebar-icon5.png') }}" alt="image" class="img-fluid">
                            Role Management
                        </a>
                        <button class="dashboard-sidebar__toggle" type="button" data-bs-toggle="collapse"
                            data-bs-target="#roleSubmenu" aria-expanded="false" aria-controls="roleSubmenu">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>
                    <ul class="collapse dashboard-sidebar__submenu" id="roleSubmenu">
                        @permission('users', 'view')
                        <li>
                            <a href="{{ route('users.index') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Users
                            </a>
                        </li>
                        @endpermission
                        @permission('roles', 'view')
                        <li>
                            <a href="{{ route('roles.index') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Roles
                            </a>
                        </li>
                        @endpermission
                    </ul>
                </li>
                @endif

                @if(auth()->user()->hasPermission('reports_sales', 'view') || 
                    auth()->user()->hasPermission('reports_returns', 'view') || 
                    auth()->user()->hasPermission('reports_stock', 'view') || 
                    auth()->user()->hasPermission('reports_top_products', 'view') || 
                    auth()->user()->hasPermission('reports_profit', 'view') || 
                    auth()->user()->hasPermission('reports_loss', 'view') || 
                    auth()->user()->hasPermission('reports_revenue', 'view'))
                <li>
                    <div class="dashboard-sidebar__item">
                        <a href="javascript:void(0)" class="dashboard-sidebar__link">
                            <img src="{{ asset('assets/images/sidebar-icon6.png') }}" alt="image" class="img-fluid">
                            Reports
                        </a>
                        <button class="dashboard-sidebar__toggle" type="button" data-bs-toggle="collapse"
                            data-bs-target="#reportsSubmenu" aria-expanded="false" aria-controls="reportsSubmenu">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>
                    <ul class="collapse dashboard-sidebar__submenu" id="reportsSubmenu">
                        @permission('reports_sales', 'view')
                        <li>
                            <a href="{{ route('reports.sales') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Sales Report
                            </a>
                        </li>
                        @endpermission
                        @permission('reports_returns', 'view')
                        <li>
                            <a href="{{ route('reports.returns') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Return Report
                            </a>
                        </li>
                        @endpermission
                        @permission('reports_stock', 'view')
                        <li>
                            <a href="{{ route('reports.stock') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Stock Report
                            </a>
                        </li>
                        @endpermission
                        @permission('reports_top_products', 'view')
                        <li>
                            <a href="{{ route('reports.top-products') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Top Products
                            </a>
                        </li>
                        @endpermission
                        @permission('reports_profit', 'view')
                        <li>
                            <a href="{{ url('profit-report') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Profit Report
                            </a>
                        </li>
                        @endpermission
                        @permission('reports_loss', 'view')
                        <li>
                            <a href="{{ url('loss-report') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Loss Report
                            </a>
                        </li>
                        @endpermission
                        @permission('reports_revenue', 'view')
                        <li>
                            <a href="{{ url('revenue-report') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                Revenue
                            </a>
                        </li>
                        @endpermission
                    </ul>
                </li>
                @endif

                @if(auth()->user()->hasPermission('settings_system', 'view') || auth()->user()->hasPermission('taxes', 'view'))
                <li>
                    <div class="dashboard-sidebar__item">
                        <a href="javascript:void(0)" class="dashboard-sidebar__link settings-link-toggle">
                            <img src="{{ asset('assets/images/sidebar-icon7.png') }}" alt="image" class="img-fluid">
                            Settings
                        </a>
                        <button class="dashboard-sidebar__toggle" type="button" data-bs-toggle="collapse"
                            data-bs-target="#settingsSubmenu" aria-expanded="false" aria-controls="settingsSubmenu">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>
                    <ul class="collapse dashboard-sidebar__submenu" id="settingsSubmenu">
                        @permission('settings_system', 'view')
                        <li>
                            <a href="{{ url('system-settings') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                System Settings
                            </a>
                        </li>
                        @endpermission
                        @permission('taxes', 'view')
                        <li>
                            <a href="{{ url('taxes') }}" class="dashboard-sidebar__submenu-link">
                                <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                                General Settings
                            </a>
                        </li>
                        @endpermission
                    </ul>
                </li>
                @endif
            </ul>
        </div>
        <div class="dashboard-content">
            <header class="header">
                <h1 class="secHeading">{{ $pageTitle ?? 'Dashboard' }}</h1>
                <div class="header-links">
                    <a href="" class="header-links__icon sidebar-toggle d-xl-none">
                        <i class="fa-solid fa-bars"></i>
                    </a>
                    <div class="dropdown">
                        <a href="#" class="header-links__icon dropdown-toggle" type="button" id="notificationDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            @if(isset($notifications) && count($notifications) > 0)
                                <span class="notification-badge">{{ count($notifications) }}</span>
                            @endif
                            <i class="fa-regular fa-bell"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown"
                            aria-labelledby="notificationDropdown">
                            <li class="notification-dropdown__header">
                                <h6>Notifications</h6>
                                <a href="#" class="text-primary">Mark all as read</a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            
                            @if(isset($notifications) && count($notifications) > 0)
                                @foreach($notifications as $notification)
                                    <li>
                                        <a class="dropdown-item notification-item" href="#">
                                            @php
                                                $bgClass = '';
                                                $iconColor = '';
                                                switch($notification['type']) {
                                                    case 'danger':
                                                        $bgClass = 'bg-danger-subtle'; // Bootstrap 5
                                                        $iconColor = 'text-danger';
                                                        break;
                                                    case 'warning':
                                                        $bgClass = 'bg-warning-subtle';
                                                        $iconColor = 'text-warning';
                                                        break;
                                                    case 'info':
                                                        $bgClass = 'bg-info-subtle';
                                                        $iconColor = 'text-info';
                                                        break;
                                                    default:
                                                        $bgClass = 'bg-light';
                                                        $iconColor = 'text-secondary';
                                                }
                                            @endphp
                                            <div class="notification-item__icon {{ $bgClass }} {{ $iconColor }}">
                                                <i class="fa-solid {{ $notification['icon'] }}"></i>
                                            </div>
                                            <div class="notification-item__content">
                                                <h6>{{ $notification['title'] }}</h6>
                                                <p>{{ $notification['message'] }}</p>
                                                <span class="notification-item__time">{{ $notification['time'] }}</span>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            @else
                                <li>
                                    <a class="dropdown-item notification-item" href="#">
                                        <div class="notification-item__content text-center w-100">
                                            <p class="mb-0">No new notifications</p>
                                        </div>
                                    </a>
                                </li>
                            @endif

                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li class="notification-dropdown__footer">
                                <a href="{{ route('reports.stock') }}" class="text-center d-block">View Stock Report</a>
                            </li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="header-links__profile dropdown-toggle" type="button" id="profileDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-gear" style="font-size: 24px; color: #ff6b00;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end profile-dropdown" aria-labelledby="profileDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.show') }}">
                                    <i class="fa-solid fa-user me-2"></i>
                                    Change Password
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('system-settings.index') }}">
                                    <i class="fa-solid fa-gear me-2"></i>
                                    Settings
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>
                                    Logout
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
