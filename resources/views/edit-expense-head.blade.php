@extends('layouts.app')

@section('content')

<!-- Expense Head Dashboard -->
<div class="discount-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Edit Expense Head</h2>   
        <a href="{{ route('expense-head.index') }}" class="back-arrow">
            <i class="fa-regular fa-arrow-left-long"></i>
        </a>
    </div>
    <div class="dashboard-card add-product-form">
        <form action="{{ route('expense-head.update', $expenseHead->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-md-4">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Expense Head Name" value="{{ old('name', $expenseHead->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="Code (Optional)" value="{{ old('code', $expenseHead->code) }}">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="Active" {{ old('status', $expenseHead->status) == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $expenseHead->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
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
