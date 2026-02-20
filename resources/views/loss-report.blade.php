@extends('layouts.app')

@section('content')


<!-- Loss Report Dashboard -->
<div class="loss-dashboard">
    <div class="dashboard-card">
        <!-- Top Row - Key Metrics -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--red">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Loss (Damaged Goods)</h3>
                        <p class="metric-card__value text-danger">{{ $globalSettings->currency ?? '$' }}2,500</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--red">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Written Off Stock</h3>
                        <p class="metric-card__value text-danger">{{ $globalSettings->currency ?? '$' }}1,200</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Filter Section -->
        <div class="cashbook-filter-section">
            <div class="cashbook-filter">
                <div class="cashbook-filter__dates">
                    <input type="date" class="form-control" value="2025-12-26" id="startDate">
                    <span class="cashbook-filter__to">to</span>
                    <input type="date" class="form-control" value="2025-12-26" id="endDate">
                    <button type="button" class="themeBtn">
                        <i class="fa-solid fa-refresh"></i> Apply
                    </button>
                </div>
            </div>
        </div>

        <!-- Monthly Profit Analysis Table -->
        <div class="transaction-ledger">
            <div class="transaction-ledger__header">
                <h3 class="transaction-ledger__title">Monthly Profit Analysis</h3>
            </div>
            <div class="table-responsive">
                <table id="cashbookTable" class="table table-hover tables">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Reason</th>
                            <th>Quantity</th>
                            <th>Estimated Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2023-10-20</td>
                            <td>Glass Vase</td>
                            <td>Broken during transit</td>
                            <td>5</td>
                            <td class="text-danger"><strong>{{ $globalSettings->currency ?? '$' }}150</strong></td>
                        </tr>
                        <tr>
                            <td>2023-10-15</td>
                            <td>Electronics Component</td>
                            <td>Water Damage</td>
                            <td>10</td>
                            <td class="text-danger"><strong>{{ $globalSettings->currency ?? '$' }}500</strong></td>
                        </tr>
                        <tr>
                            <td>2023-10-05</td>
                            <td>Perishable Goods</td>
                            <td>Expired</td>
                            <td>20</td>
                            <td class="text-danger"><strong>{{ $globalSettings->currency ?? '$' }}200</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection