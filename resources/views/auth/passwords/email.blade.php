@extends('layouts.guest')

@section('content')
    <a href="{{ route('login') }}" class="small fw-semibold text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Back to sign in</a>
    <h2 class="mt-3 mb-1">Forgot your password?</h2>
    <p class="text-muted mb-4">Enter your email and we'll send you a reset link.</p>

    @if (session('status'))
        <div class="alert alert-success small"><i class="fa-solid fa-circle-check me-1"></i>{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="mb-4">
            <label class="form-label" for="email">Email</label>
            <div class="auth__field">
                <i class="fa-regular fa-envelope"></i>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="themeBtn auth__submit"><i class="fa-solid fa-paper-plane"></i> Send reset link</button>
    </form>
@endsection
