@extends('layouts.guest')

@section('content')
    <h2 class="mb-1">Welcome back 👋</h2>
    <p class="text-muted mb-4">Sign in to open your till.</p>

    @if (session('status'))
        <div class="alert alert-success small">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <div class="auth__field">
                <i class="fa-regular fa-envelope"></i>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="you@store.com">
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label class="form-label" for="password">Password</label>
                @if (Route::has('password.request'))
                    <a class="small fw-semibold text-decoration-none" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <div class="auth__field">
                <i class="fa-solid fa-lock"></i>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                <button type="button" class="auth__eye" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label small" for="remember">Keep me signed in</label>
        </div>
        <button type="submit" class="themeBtn auth__submit"><i class="fa-solid fa-right-to-bracket"></i> Sign in</button>
    </form>
@endsection
