@extends('layouts.app')

@section('content')

<!-- Bank Dashboard -->
<div class="bank-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Edit Bank</h2>   
        <a href="{{ route('bank.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ route('bank.update', $bank->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Meezan Bank" value="{{ old('name', $bank->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('account_title') is-invalid @enderror" name="account_title" placeholder="My Business Account" value="{{ old('account_title', $bank->account_title) }}" required>
                    @error('account_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('account_number') is-invalid @enderror" name="account_number" placeholder="0101010101" value="{{ old('account_number', $bank->account_number) }}" required>
                    @error('account_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 2 -->
                <div class="col-md-4">
                    <label class="form-label">Branch Code</label>
                    <input type="text" class="form-control @error('branch_code') is-invalid @enderror" name="branch_code" placeholder="0101" value="{{ old('branch_code', $bank->branch_code) }}">
                    @error('branch_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Branch Name</label>
                    <input type="text" class="form-control @error('branch_name') is-invalid @enderror" name="branch_name" placeholder="Main Branch" value="{{ old('branch_name', $bank->branch_name) }}">
                    @error('branch_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">IBAN</label>
                    <input type="text" class="form-control @error('iban') is-invalid @enderror" name="iban" placeholder="PK00MEZN..." value="{{ old('iban', $bank->iban) }}">
                    @error('iban')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 3 -->
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status">
                        <option value="Active" {{ old('status', $bank->status) == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $bank->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-actions mt-4 text-end">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

@endsection
