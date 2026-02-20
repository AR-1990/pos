@extends('layouts.app')

@section('content')

<!-- Accounts Dashboard -->
<div class="accounts-dashboard">
    <p>Manage your financial records and reports here.</p>
    <div class="dashboard-card">
        <!-- Summary Cards Section -->
        <div class="row g-3 mb-4">
            <!-- Cashbook -->
            <div class="col-lg-4 col-sm-6">
                <div class="account-card account-card--orange">
                    <div class="account-card__icon">
                        <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                    </div>
                    <div class="account-card__content">
                        <h3 class="account-card__title">Cashbook</h3>
                    </div>
                    <a href="{{ url('cashbook') }}" class="account-card__link">View Details</a>
                </div>
            </div>

            <!-- Expenses -->
            <div class="col-lg-4 col-sm-6">
                <div class="account-card account-card--red">
                    <div class="account-card__icon">
                        <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                    </div>
                    <div class="account-card__content">
                        <h3 class="account-card__title">Expenses</h3>
                    </div>
                    <a href="{{ url('expense') }}" class="account-card__link">View Details</a>
                </div>
            </div>

            <!-- Payroll -->
            <div class="col-lg-4 col-sm-6">
                <div class="account-card account-card--orange">
                    <div class="account-card__icon">
                        <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                    </div>
                    <div class="account-card__content">
                        <h3 class="account-card__title">Payroll</h3>
                    </div>
                    <a href="{{ url('payroll') }}" class="account-card__link">View Details</a>
                </div>
            </div>

            <!-- Loan & Adv -->
            <div class="col-lg-4 col-sm-6">
                <div class="account-card account-card--orange">
                    <div class="account-card__icon">
                        <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                    </div>
                    <div class="account-card__content">
                        <h3 class="account-card__title">Loan & Adv</h3>
                    </div>
                    <a href="{{ url('loan-and-advances') }}" class="account-card__link">View Details</a>
                </div>
            </div>

            <!-- Profit Report -->
            <div class="col-lg-4 col-sm-6">
                <div class="account-card account-card--teal">
                    <div class="account-card__icon">
                        <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                    </div>
                    <div class="account-card__content">
                        <h3 class="account-card__title">Profit Report</h3>
                    </div>
                    <a href="{{ url('profit-report') }}" class="account-card__link">View Details</a>
                </div>
            </div>

            <!-- Loss Report -->
            <div class="col-lg-4 col-sm-6">
                <div class="account-card account-card--red">
                    <div class="account-card__icon">
                        <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                    </div>
                    <div class="account-card__content">
                        <h3 class="account-card__title">Loss Report</h3>
                    </div>
                    <a href="{{ url('loss-report') }}" class="account-card__link">View Details</a>
                </div>
            </div>

            <!-- Revenue -->
            <div class="col-lg-4 col-sm-6">
                <div class="account-card account-card--yellow">
                    <div class="account-card__icon">
                        <img src="{{ asset('assets/images/double-check.png') }}" alt="image" class="img-fluid">
                    </div>
                    <div class="account-card__content">
                        <h3 class="account-card__title">Revenue</h3>
                    </div>
                    <a href="{{ url('revenue-report') }}" class="account-card__link">View Details</a>
                </div>
            </div>
        </div>

        <!-- Financial Overview Table Section -->
        <div class="financial-overview">
            <h3 class="financial-overview__title">Financial Overview</h3>
            <div class="table-responsive">
                <table id="financialTable" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Metric</th>
                            <th>This Month</th>
                            <th>Last Month</th>
                            <th>Change</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Total Revenue</td>
                            <td>{{ $globalSettings->currency ?? '$' }}50,000</td>
                            <td>{{ $globalSettings->currency ?? '$' }}45,000</td>
                            <td><span class="change-badge change-badge--increase">+11%</span></td>
                            <td>
                                <button class="btn btn-sm">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Total Expenses</td>
                            <td>{{ $globalSettings->currency ?? '$' }}30,000</td>
                            <td>{{ $globalSettings->currency ?? '$' }}28,000</td>
                            <td><span class="change-badge change-badge--increase">+7%</span></td>
                            <td>
                                <button class="btn btn-sm">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Net Profit</td>
                            <td>{{ $globalSettings->currency ?? '$' }}20,000</td>
                            <td>{{ $globalSettings->currency ?? '$' }}17,000</td>
                            <td><span class="change-badge change-badge--decrease">+17%</span></td>
                            <td>
                                <button class="btn btn-sm">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Net Profit</td>
                            <td>{{ $globalSettings->currency ?? '$' }}20,000</td>
                            <td>{{ $globalSettings->currency ?? '$' }}17,000</td>
                            <td><span class="change-badge change-badge--increase">+17%</span></td>
                            <td>
                                <button class="btn btn-sm">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection