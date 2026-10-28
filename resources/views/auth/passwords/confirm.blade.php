@extends('layouts.guest')

@section('content')
    <h2 class="mb-1">Confirm your password</h2>
    <p class="text-muted mb-4">Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf
        <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <div class="auth__field">
                <i class="fa-solid fa-lock"></i>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" autofocus>
                <button type="button" class="auth__eye" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="themeBtn auth__submit">Confirm</button>
        @if (Route::has('password.request'))
            <a class="d-block text-center small mt-3" href="{{ route('password.request') }}">Forgot your password?</a>
        @endif
    </form>
@endsection
