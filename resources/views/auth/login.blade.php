@extends('layouts.guest')

@push('styles')
<style>
    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        overflow: hidden;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .login-wrapper {
        height: 100vh;
        width: 100%;
        display: flex;
    }

    /* Left Side - Brand & Animation */
    .login-left {
        width: 55%;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .login-left::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 60%);
        animation: rotateBg 60s linear infinite;
    }

    @keyframes rotateBg {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .brand-content {
        z-index: 10;
        text-align: center;
        padding: 2rem;
    }

    .brand-logo {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        letter-spacing: 2px;
        color: #fff;
    }

    .brand-logo span {
        color: #3b82f6; /* Blue accent */
    }

    .brand-tagline {
        font-size: 1.2rem;
        opacity: 0.8;
        margin-bottom: 3rem;
    }

    /* POS Animation */
    .pos-animation {
        position: relative;
        width: 200px;
        height: 200px;
        margin: 0 auto;
    }

    .register-body {
        width: 160px;
        height: 120px;
        background: #334155;
        border-radius: 10px;
        position: absolute;
        bottom: 0;
        left: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        z-index: 2;
    }

    .register-screen {
        width: 140px;
        height: 80px;
        background: #0f172a;
        margin: 10px auto;
        border-radius: 4px;
        position: relative;
        overflow: hidden;
        border: 2px solid #475569;
    }

    .register-screen::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: #10b981; /* Green scan line */
        animation: scanLine 2s linear infinite;
        box-shadow: 0 0 10px #10b981;
    }

    .receipt-paper {
        width: 80px;
        height: 100px;
        background: #f8fafc;
        position: absolute;
        top: 20px; /* Start hidden behind body */
        left: 60px;
        z-index: 1;
        animation: printReceipt 4s ease-in-out infinite;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        padding: 5px;
        font-size: 6px;
        color: #94a3b8;
        font-family: monospace;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .receipt-line {
        height: 2px;
        background: #cbd5e1;
        width: 100%;
        margin-bottom: 2px;
    }
    .receipt-line.short { width: 60%; }

    @keyframes printReceipt {
        0% { top: 40px; height: 0; opacity: 0; }
        20% { opacity: 1; }
        50% { top: -40px; height: 80px; } /* Rise up */
        80% { top: -40px; height: 80px; opacity: 1; }
        100% { top: -60px; height: 80px; opacity: 0; } /* Fade out */
    }

    @keyframes scanLine {
        0% { top: 0; }
        100% { top: 100%; }
    }

    /* Right Side - Login Form */
    .login-right {
        width: 45%;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 4rem;
        position: relative;
    }

    .login-card {
        max-width: 450px;
        width: 100%;
        margin: 0 auto;
    }

    .login-header h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .login-header p {
        color: #64748b;
        margin-bottom: 2.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }

    .form-control {
        height: 50px;
        padding-left: 3rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .btn-login {
        width: 100%;
        height: 50px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
        margin-top: 1rem;
    }

    .btn-login:hover {
        background: #2563eb;
    }

    .forgot-link {
        color: #64748b;
        text-decoration: none;
        font-size: 0.9rem;
        margin-top: 1rem;
        display: block;
        text-align: center;
    }

    .forgot-link:hover {
        color: #3b82f6;
    }

    /* Responsive */
    @media (max-width: 991px) {
        .login-left { width: 40%; }
        .login-right { width: 60%; }
    }

    @media (max-width: 768px) {
        .login-wrapper { flex-direction: column; }
        .login-left {
            width: 100%;
            height: 30vh;
            padding: 1rem;
        }
        .login-right {
            width: 100%;
            height: 70vh;
            padding: 2rem;
            justify-content: flex-start;
        }
        .brand-logo { font-size: 2rem; margin-bottom: 0.5rem; }
        .brand-tagline { display: none; }
        .pos-animation { transform: scale(0.7); margin-bottom: 0; }
    }
</style>
@endpush

@section('content')
<div class="login-wrapper">
    <!-- Left Side: Branding & Animation -->
    <div class="login-left">
        <div class="brand-content">
            <h1 class="brand-logo">Koder<span>360</span></h1>
            <p class="brand-tagline">Professional Point of Sale System</p>
            
            <div class="pos-animation">
                <!-- Receipt Paper (Animates Up) -->
                <div class="receipt-paper">
                    <div class="receipt-line"></div>
                    <div class="receipt-line"></div>
                    <div class="receipt-line short"></div>
                    <div class="receipt-line"></div>
                    <div class="receipt-line"></div>
                    <div class="receipt-line short"></div>
                </div>
                <!-- Register Body -->
                <div class="register-body">
                    <div class="register-screen"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="login-right">
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please enter your details to sign in.</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                           name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                           placeholder="Email Address">
                    @error('email')
                        <span class="invalid-feedback d-block mt-1" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                           name="password" required autocomplete="current-password"
                           placeholder="Password">
                    @error('password')
                        <span class="invalid-feedback d-block mt-1" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label text-secondary" for="remember">
                            {{ __('Remember Me') }}
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Sign In <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>

                @if (Route::has('password.request'))
                    <a class="forgot-link" href="{{ route('password.request') }}">
                        {{ __('Forgot Your Password?') }}
                    </a>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
