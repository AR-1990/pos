@extends('layouts.guest')

@section('content')
    <h2 class="mb-1">Set a new password</h2>
    <p class="text-muted mb-4">Choose a password you don't use anywhere else.</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <div class="auth__field">
                <i class="fa-regular fa-envelope"></i>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email">
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            <div class="auth__field">
                <i class="fa-solid fa-lock"></i>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" autofocus>
                <button type="button" class="auth__eye" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="form-label" for="password-confirm">Confirm password</label>
            <div class="auth__field">
                <i class="fa-solid fa-lock"></i>
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="themeBtn auth__submit"><i class="fa-solid fa-key"></i> Reset password</button>
    </form>
@endsection
