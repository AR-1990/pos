@extends('layouts.app')

@section('content')
<div class="cashbook-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">Change Password</h2>
    </div>
    <div class="dashboard-card">
        <div class="cashbook-transaction-form">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('profile.update-password') }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                            id="current_password" name="current_password" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                            id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" 
                            id="password_confirmation" name="password_confirmation" required>
                    </div>

                    <div class="col-12">
                        <div class="add-product-form__actions">
                            <button type="submit" class="themeBtn">
                                Update Password
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
