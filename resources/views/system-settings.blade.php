@extends('layouts.app')

@section('content')

<!-- Settings Dashboard -->
<div class="expense-form-dashboard">
    <div class="dashboard-content__head">
        <h2 class="heading">System Settings</h2>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="dashboard-card">
        <div class="cashbook-transaction-form">
            <form id="settingsForm" action="{{ route('system-settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">

                    <div class="col-12">
                        <!-- Upload Company Logo -->
                        <div class="mb-4">
                            <label class="form-label">Upload Company Logo</label>
                            <input type="file" class="file-upload-input" id="logoUpload" name="company_logo" accept="image/*" hidden>
                            <label for="logoUpload" class="file-upload-area">
                                <div class="file-upload-icon">
                                    <i class="fa-solid fa-folder-arrow-up"></i>
                                </div>
                                <p class="file-upload-text">
                                    Drag and drop files, or
                                    <strong class="text-primary">Browse</strong>
                                </p>
                                <p class="file-upload-hint">Support image files</p>
                            </label>
                            @if(isset($settings->company_logo))
                                <div class="mt-2">
                                    <p>Current Logo:</p>
                                    <img src="{{ asset($settings->company_logo) }}" alt="Company Logo" style="max-height: 100px;">
                                </div>
                            @endif
                            @error('company_logo')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" placeholder="Company Name" value="{{ old('company_name', $settings->company_name ?? '') }}">
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" placeholder="Address" value="{{ old('address', $settings->address ?? '') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="Phone Number" value="{{ old('phone', $settings->phone ?? '') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="Email Address" value="{{ old('email', $settings->email ?? '') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Country</label>
                            <select class="form-select @error('country') is-invalid @enderror" name="country" id="countrySelect">
                                <option value="">Select Country</option>
                                @foreach($countries as $country => $timezone)
                                    <option value="{{ $country }}" data-timezone="{{ $timezone }}" {{ (old('country', $settings->country ?? '') == $country) ? 'selected' : '' }}>{{ $country }}</option>
                                @endforeach
                            </select>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Currency</label>
                            <select class="form-select @error('currency') is-invalid @enderror" name="currency">
                                <option value="">Select Currency</option>
                                @foreach($currencies as $code => $name)
                                    <option value="{{ $code }}" {{ (old('currency', $settings->currency ?? '') == $code) ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
                                @endforeach
                            </select>
                            @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Timezone</label>
                            <select class="form-select" name="timezone" id="timezoneSelect">
                                <option value="">Select Timezone</option>
                                @foreach($timezones as $timezone)
                                    <option value="{{ $timezone }}" {{ (old('timezone', $settings->timezone ?? '') == $timezone) ? 'selected' : '' }}>{{ $timezone }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                </div>

                <!-- Form Actions -->
                <div class="mt-4 d-flex gap-3">
                    <button type="submit" class="themeBtn">Save</button>
                    <a href="{{ route('system-settings.index') }}" class="themeBtn themeBtn--white">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const countrySelect = document.getElementById('countrySelect');
        const timezoneSelect = document.getElementById('timezoneSelect');

        countrySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const timezone = selectedOption.getAttribute('data-timezone');
            
            if (timezone) {
                timezoneSelect.value = timezone;
            }
        });
    });
</script>

@endsection
