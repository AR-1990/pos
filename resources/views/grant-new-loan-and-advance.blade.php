@extends('layouts.app')

@section('content')

<!-- Grant New Loan / Advance -->
<div class="payroll-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">{{ isset($loan) ? 'Edit Loan / Advance' : 'Grant New Loan / Advance' }}</h2>
        <a href="{{ route('loan-and-advances.index') }}" class="themeBtn">
            <i class="fa-regular fa-arrow-left-long"></i>
            Back To Loans
        </a>
    </div>
    <div class="dashboard-card">
        <div class="cashbook-transaction-form">
            <form action="{{ isset($loan) ? route('loan-and-advances.update', $loan->id) : route('loan-and-advances.store') }}" method="POST">
                @csrf
                @if(isset($loan))
                    @method('PUT')
                @endif
                <div class="row g-3">
                    <!-- Employee -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select class="form-select @error('user_id') is-invalid @enderror" name="user_id">
                                <option value="" selected>Select Employee...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (isset($loan) && $loan->user_id == $user->id) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->designation ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Type -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type">
                                <option value="" selected>Select Loan Type...</option>
                                @foreach($loanTypes ?? [] as $lt)
                                    <option value="{{ $lt->name }}" {{ (isset($loan) && $loan->type == $lt->name) ? 'selected' : '' }}>
                                        {{ $lt->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount', $loan->amount ?? '') }}" step="0.01" min="0" placeholder="0.00">
                             @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Disbursement Date -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Disbursement Date</label>
                            <div class="input-group">
                                <input type="date" class="form-control @error('date') is-invalid @enderror" name="date" value="{{ old('date', $loan->date ?? date('Y-m-d')) }}">
                                <span class="input-group-text">
                                    <i class="fa-regular fa-calendar"></i>
                                </span>
                            </div>
                             @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Repayment Plan -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Repayment Plan</label>
                            <select class="form-select @error('repayment_plan') is-invalid @enderror" name="repayment_plan">
                                <option value="Monthly Installments" {{ (isset($loan) && $loan->repayment_plan == 'Monthly Installments') ? 'selected' : '' }}>Monthly Installments</option>
                                <option value="Weekly Installments" {{ (isset($loan) && $loan->repayment_plan == 'Weekly Installments') ? 'selected' : '' }}>Weekly Installments</option>
                                <option value="Bi-weekly Installments" {{ (isset($loan) && $loan->repayment_plan == 'Bi-weekly Installments') ? 'selected' : '' }}>Bi-weekly Installments</option>
                                <option value="Quarterly Installments" {{ (isset($loan) && $loan->repayment_plan == 'Quarterly Installments') ? 'selected' : '' }}>Quarterly Installments</option>
                                <option value="Lump Sum" {{ (isset($loan) && $loan->repayment_plan == 'Lump Sum') ? 'selected' : '' }}>Lump Sum</option>
                            </select>
                             @error('repayment_plan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Number of Installments (if applicable) -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Number of Installments (if applicable)</label>
                            <input type="number" class="form-control @error('installments') is-invalid @enderror" name="installments" value="{{ old('installments', $loan->installments ?? '') }}" placeholder="e.g. 6" min="1" step="1">
                             @error('installments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" name="status">
                                <option value="Active" {{ (isset($loan) && $loan->status == 'Active') ? 'selected' : '' }}>Active</option>
                                <option value="Pending" {{ (isset($loan) && $loan->status == 'Pending') ? 'selected' : '' }}>Pending</option>
                                <option value="Paid" {{ (isset($loan) && $loan->status == 'Paid') ? 'selected' : '' }}>Paid</option>
                                <option value="Rejected" {{ (isset($loan) && $loan->status == 'Rejected') ? 'selected' : '' }}>Rejected</option>
                            </select>
                             @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Reason / Notes -->
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Reason / Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="4" placeholder="Additional notes...">{{ old('notes', $loan->notes ?? '') }}</textarea>
                             @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Grant Loan Button -->
                <button type="submit" class="themeBtn w-100">
                    {{ isset($loan) ? 'Update Loan' : 'Grant Loan' }}
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
