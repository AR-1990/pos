@extends('layouts.app')

@section('content')

<div class="dashboard-card">
    <div class="mb-3">
        <h2 class="fw-bold">Loan Details</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('loan-and-advances.index') }}">Loan & Advances</a></li>
                <li class="breadcrumb-item active" aria-current="page">Details</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <!-- Loan Info -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">Loan Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold">{{ $loan->user->name }}</h4>
                        <p class="text-muted mb-0">{{ $loan->user->designation ?? 'Employee' }}</p>
                        <span
                            class="badge {{ $loan->status === 'Active' ? 'bg-primary' : ($loan->status === 'Paid' ? 'bg-success' : 'bg-secondary') }} mt-2">
                            {{ $loan->status }}
                        </span>
                    </div>

                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Loan ID</span>
                            <span class="fw-bold">#{{ $loan->id }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total Amount</span>
                            <span class="fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($loan->amount, 2) }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Paid Amount</span>
                            <span class="fw-bold text-success">{{ $globalSettings->currency ?? '$' }}{{ number_format($loan->paid_amount, 2) }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Balance</span>
                            <span
                                class="fw-bold text-danger">{{ $globalSettings->currency ?? '$' }}{{ number_format($loan->amount - $loan->paid_amount, 2) }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Date Taken</span>
                            <span>{{ \Carbon\Carbon::parse($loan->date)->format('d M, Y') }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Repayment Plan</span>
                            <span>{{ $loan->repayment_plan }}</span>
                        </div>
                        @if($loan->installments)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Installments</span>
                            <span>{{ $loan->installments }}</span>
                        </div>
                        @endif
                    </div>

                    @if($loan->notes)
                    <div class="mt-3">
                        <label class="form-label text-muted">Notes</label>
                        <p class="small bg-light p-2 rounded">{{ $loan->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Repayment History -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Repayment History</h5>
                    <div class="text-muted small">
                        Total Repayments: {{ $loan->repayments->count() }}
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Amount</th>
                                    <th>Total Paid</th>
                                    <th>Balance</th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loan->repayments as $repayment)
                                <tr>
                                    <td class="ps-4">
                                        {{ \Carbon\Carbon::parse($repayment->payment_date)->format('d M, Y') }}</td>
                                    <td class="fw-bold text-success">{{ $globalSettings->currency ?? '$' }}{{ number_format($repayment->amount, 2) }}</td>
                                    <td class="text-primary">{{ $globalSettings->currency ?? '$' }}{{ number_format($repayment->running_paid, 2) }}</td>
                                    <td class="text-danger fw-bold">{{ $globalSettings->currency ?? '$' }}{{ number_format($repayment->running_balance, 2) }}
                                    </td>
                                    <td class="small text-muted">{{ $repayment->ref_id ?? '-' }}</td>
                                    <td class="small text-muted" style="max-width: 200px;">
                                        {{ $repayment->notes ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-file-invoice-dollar fs-1 mb-3 d-block opacity-25"></i>
                                        No repayments found yet.
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
@endsection
