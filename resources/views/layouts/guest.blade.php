<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $globalSettings->company_name ?? 'Koder360' }} | Sign in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v=6">
    <script>
        try { var t = localStorage.getItem('pos-theme'); if (t) { document.documentElement.setAttribute('data-theme', t); } } catch (e) {}
    </script>
    <style>
        .auth { min-height: 100vh; display: grid; grid-template-columns: 1.1fr 1fr; position: relative; z-index: 1; }
        .auth__art { background: var(--grad); background-size: 200% 200%; animation: grad-move 10s ease infinite; color: #fff; padding: 3rem; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; }
        .auth__art::before, .auth__art::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, .12); animation: float-y 7s ease-in-out infinite; }
        .auth__art::before { width: 320px; height: 320px; right: -80px; top: 12%; }
        .auth__art::after { width: 200px; height: 200px; left: 10%; bottom: -60px; animation-delay: -3s; }
        .auth__brand { display: flex; align-items: center; gap: .75rem; font-weight: 800; font-size: 1.25rem; position: relative; z-index: 1; }
        .auth__brand span { width: 44px; height: 44px; border-radius: 14px; background: rgba(255, 255, 255, .2); display: grid; place-items: center; }
        .auth__hero { position: relative; z-index: 1; max-width: 460px; }
        .auth__hero h1 { font-size: 2.5rem; font-weight: 800; letter-spacing: -.03em; line-height: 1.1; }
        .auth__hero p { opacity: .9; font-size: 1.0625rem; }
        .auth__chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.5rem; }
        .auth__chip { background: rgba(255, 255, 255, .18); border: 1px solid rgba(255, 255, 255, .25); border-radius: 999px; padding: .4rem .85rem; font-size: .8125rem; font-weight: 600; animation: pop .5s var(--spring) both; }
        .auth__chip:nth-child(2) { animation-delay: .08s; } .auth__chip:nth-child(3) { animation-delay: .16s; } .auth__chip:nth-child(4) { animation-delay: .24s; }
        .auth__form { display: grid; place-items: center; padding: 2rem; }
        .auth__card { width: 100%; max-width: 420px; animation: page-in .5s var(--ease) both; }
        .auth__card h2 { font-weight: 800; color: var(--theme-color); letter-spacing: -.02em; }
        .auth__field { position: relative; }
        .auth__field i { position: absolute; left: .95rem; top: 50%; transform: translateY(-50%); color: var(--muted); transition: color .2s; }
        .auth__field .form-control { padding-left: 2.6rem; height: 50px; }
        .auth__field:focus-within i { color: var(--primary); }
        .auth__eye { position: absolute; right: .5rem; top: 50%; transform: translateY(-50%); border: 0; background: none; color: var(--muted); padding: .5rem; }
        .auth__submit { width: 100%; justify-content: center; height: 50px; font-size: 1rem; }
        @keyframes float-y { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-18px); } }
        @media (max-width: 991px) { .auth { grid-template-columns: 1fr; } .auth__art { display: none; } }
    </style>
</head>
<body>
    <div class="auth">
        <div class="auth__art">
            <div class="auth__brand"><span><i class="fa-solid fa-bolt"></i></span>{{ $globalSettings->company_name ?? 'Koder360' }}</div>
            <div class="auth__hero">
                <h1>Sell faster.<br>Track every store.</h1>
                <p>Billing, stock, shifts and credit for all your branches — in one place.</p>
                <div class="auth__chips">
                    <span class="auth__chip"><i class="fa-solid fa-barcode me-1"></i> Scan & sell</span>
                    <span class="auth__chip"><i class="fa-solid fa-store me-1"></i> Multi-store</span>
                    <span class="auth__chip"><i class="fa-solid fa-boxes-stacked me-1"></i> Live stock</span>
                    <span class="auth__chip"><i class="fa-solid fa-chart-line me-1"></i> Reports</span>
                </div>
            </div>
            <small class="opacity-75 position-relative">&copy; {{ date('Y') }} {{ $globalSettings->company_name ?? 'Koder360' }}</small>
        </div>
        <div class="auth__form">
            <div class="auth__card">
                @yield('content')
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.auth__eye').forEach(function (b) {
            b.addEventListener('click', function () {
                var i = b.parentElement.querySelector('input');
                i.type = i.type === 'password' ? 'text' : 'password';
                b.firstElementChild.className = i.type === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
            });
        });
    </script>
</body>
</html>
