@extends('layouts.app')

@section('content')

<!-- Inventory Dashboard -->
<div class="inventory-dashboard">
    <div class="dashboard-content__head mb-4">
        <h2 class="heading">Dashboard Overview</h2>
        <p class="text-muted">Welcome back! Here's what's happening with your store today.</p>
    </div>

    <div class="dashboard-card">
        <!-- Section 1: Inventory Stats -->
        <h4 class="mb-3 text-secondary"><i class="fa-solid fa-boxes-stacked me-2"></i>Inventory Status</h4>
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon bg-primary-subtle text-primary">
                        <i class="fa-solid fa-box"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Products</h3>
                        <p class="metric-card__value">{{ number_format($totalProducts) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon bg-danger-subtle text-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Out of Stock</h3>
                        <p class="metric-card__value text-danger">{{ number_format($outOfStock) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon bg-warning-subtle text-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Low Stock</h3>
                        <p class="metric-card__value text-warning">{{ number_format($lowStock) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon bg-info-subtle text-info">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Expiring Soon</h3>
                        <p class="metric-card__value text-info">{{ number_format($expiringCount) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Financial Stats -->
        <h4 class="mb-3 text-secondary"><i class="fa-solid fa-chart-line me-2"></i>Financial Overview</h4>
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon bg-success-subtle text-success">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Total Revenue</h3>
                        <p class="metric-card__value text-success">{{ $globalSettings->currency ?? '$' }}{{ number_format($totalRevenue, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon bg-danger-subtle text-danger">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Expenses (This Month)</h3>
                        <p class="metric-card__value text-danger">{{ $globalSettings->currency ?? '$' }}{{ number_format($currentMonthExpense, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon bg-primary-subtle text-primary">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Salaries (This Month)</h3>
                        <p class="metric-card__value text-primary">{{ $globalSettings->currency ?? '$' }}{{ number_format($currentMonthSalary, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="metric-card__icon bg-secondary-subtle text-secondary">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div class="metric-card__content">
                        <h3 class="metric-card__title">Outstanding Loans</h3>
                        <p class="metric-card__value text-secondary">{{ $globalSettings->currency ?? '$' }}{{ number_format($totalLoan, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Middle Row - Charts -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="chart-card h-100">
                    <h3 class="chart-card__title">Sales Trend (Last 7 Days)</h3>
                    <canvas id="salesTrendChart" style="height: 300px;"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card h-100">
                    <h3 class="chart-card__title">Top Categories</h3>
                    <div style="height: 250px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row - Tables -->
        <div class="row g-3">
            <!-- Recent Sales -->
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="chart-card__title mb-0">Recent Sales</h3>
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($sale->date)->format('d M') }}</td>
                                    <td>{{ $sale->customer ? Str::limit($sale->customer->name, 15) : 'Walk-in' }}</td>
                                    <td>{{ $globalSettings->currency ?? '$' }}{{ number_format($sale->grand_total, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($sale->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No recent sales.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Expiring Products -->
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="chart-card__title mb-0 text-danger">Expiring Soon (Next 30 Days)</h3>
                        <span class="badge bg-danger">{{ $expiringCount }} Items</span>
                    </div>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Expiry Date</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiringProducts as $product)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">{{ Str::limit($product->name, 20) }}</span>
                                            <small class="text-muted">{{ $product->code }}</small>
                                        </div>
                                    </td>
                                    <td class="text-danger fw-bold">
                                        {{ \Carbon\Carbon::parse($product->expiry_date)->format('d M Y') }}
                                        <br>
                                        <small class="text-muted">
                                            ({{ \Carbon\Carbon::parse($product->expiry_date)->diffForHumans() }})
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $product->stock_quantity > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $product->stock_quantity }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        <i class="fa-solid fa-check-circle text-success fs-4 mb-2"></i>
                                        <p class="mb-0">No products expiring soon.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sales Trend Chart
        const salesCtx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: @json($salesLabels),
                datasets: [{
                    label: 'Sales',
                    data: @json($salesValues),
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "rgba(0, 0, 0, 0.05)"
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Category Chart
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: @json($categoryLabels),
                datasets: [{
                    data: @json($categoryValues),
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                cutout: '70%',
            }
        });
    });
</script>
@endpush

@endsection
