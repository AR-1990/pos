@extends('layouts.app')

@section('content')
<style>
    @media print {
        .dashboard-sidebar,
        .header,
        .sidebar-overlay,
        .dashboard-content__head,
        .card-header button {
            display: none !important;
        }
        
        .dashboard-content {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        
        .dashboard-wrapper {
            display: block !important;
        }

        body {
            background-color: white !important;
            visibility: visible !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .container {
            max-width: 100% !important;
            padding: 0 !important;
        }
    }
</style>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Salary Slip</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> Print
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="mb-1">Employee</h6>
                        <p class="mb-0">
                            <strong>{{ $payroll->user->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted">{{ $payroll->user->designation ?? 'N/A' }}</small>
                        </p>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6>Earnings</h6>
                                <p class="mb-1">Basic Salary: <strong>{{ $globalSettings->currency ?? '$' }}{{ number_format($payroll->basic_salary, 2) }}</strong></p>
                                <p class="mb-0">Allowances: <strong>{{ $globalSettings->currency ?? '$' }}{{ number_format($payroll->allowances, 2) }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6>Deductions</h6>
                                <p class="mb-1">Income Tax: <strong>{{ $globalSettings->currency ?? '$' }}{{ number_format($payroll->income_tax, 2) }}</strong></p>
                                <p class="mb-1">PF: <strong>{{ $globalSettings->currency ?? '$' }}{{ number_format($payroll->fund_deduction, 2) }}</strong></p>
                                <p class="mb-1">Loan Deduction: <strong>{{ $globalSettings->currency ?? '$' }}{{ number_format($payroll->loan_deduction, 2) }}</strong></p>
                                <p class="mb-0">Other Deductions: <strong>{{ $globalSettings->currency ?? '$' }}{{ number_format($payroll->other_deductions, 2) }}</strong></p>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <h6 class="mb-2">Summary</h6>
                        <p class="mb-1">Salary Month: <strong>{{ \Carbon\Carbon::parse($payroll->salary_month)->format('M, Y') }}</strong></p>
                        <p class="mb-1">Net Salary: <strong>{{ $globalSettings->currency ?? '$' }}{{ number_format($payroll->net_salary, 2) }}</strong></p>
                        <p class="mb-1">Payment Mode: <strong>{{ $payroll->payment_mode ?? '-' }}</strong></p>
                        <p class="mb-0">Status: 
                            <span class="badge {{ $payroll->status === 'Paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $payroll->status }}
                            </span>
                        </p>
                    </div>

                    @if(!empty($payroll->notes))
                        <div class="border rounded p-3">
                            <h6 class="mb-2">Notes</h6>
                            <p class="mb-0">{{ $payroll->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
