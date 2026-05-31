<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pools Ice Admin</title>
    @php($faviconPath = public_path('storage/poolsice.png'))
    @php($faviconVersion = file_exists($faviconPath) ? filemtime($faviconPath) : time())
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('storage/poolsice.png') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" href="{{ asset('storage/poolsice.png') }}?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" href="{{ asset('storage/poolsice.png') }}?v={{ $faviconVersion }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-font-smoothing: antialiased;
            padding: 16px;
        }
        .login-container {
            display: flex;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            border: 1px solid #E2E8F0;
            max-width: 820px;
            width: 100%;
            overflow: hidden;
            animation: fadeUp 0.5s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-form {
            flex: 1;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-title {
            font-size: 27px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .login-subtitle {
            font-size: 15px;
            color: #64748B;
            margin-bottom: 32px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 11px 16px;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.2s;
            font-family: inherit;
            color: #0F172A;
            background: #FAFBFC;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
            background: white;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #0F172A;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn-login:hover {
            background: #1E293B;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15,23,42,0.15);
        }
        .btn-login:disabled {
            cursor: wait;
            opacity: 0.78;
            transform: none;
        }
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            z-index: 10000;
            overflow: hidden;
        }
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }
        .loading-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 86px;
            min-height: 104px;
            gap: 10px;
        }
        .loading-bars {
            position: relative;
            width: 70px;
            height: 64px;
        }
        .loading-bars span {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.14);
            animation: loadingDotOrbit 1.1s ease-in-out infinite;
            animation-delay: var(--bar-delay);
        }
        .loading-subtitle {
            z-index: 1;
            color: rgba(255, 255, 255, 0.86);
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.01em;
            line-height: 1.2;
            white-space: nowrap;
        }
        @keyframes loadingDotOrbit {
            0%, 100% {
                transform: translate(-50%, -50%) translate(-24px, 12px) scale(1);
            }
            33.333% {
                transform: translate(-50%, -50%) translate(0, -18px) scale(1.08);
            }
            66.666% {
                transform: translate(-50%, -50%) translate(24px, 12px) scale(1);
            }
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        .login-visual {
            flex: 1;
            background-color: #FFFFFF;
            background-image: url('/storage/poolsice.png');
            background-position: center;
            background-size: contain;
            background-repeat: no-repeat;
            min-height: 420px;
        }
        @media (max-width: 768px) {
            .login-container { flex-direction: column-reverse; }
            .login-form { padding: 24px 24px 36px; }
            .login-visual { min-height: 130px; flex: none; background-size: auto 100px; margin-top: 32px; }
        }
        @media (max-width: 480px) {
            .login-form { padding: 20px 20px 28px; }
            .login-visual { min-height: 110px; background-size: auto 80px; margin-top: 24px; }
            .login-title { font-size: 22px; }
            .login-subtitle { font-size: 13px; margin-bottom: 20px; }
        }
        @media (max-width: 640px) {
            .loading-panel { width: 78px; min-height: 98px; }
            .loading-subtitle { font-size: 13px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .loading-panel,
            .loading-bars span {
                animation: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="login-title">Selamat Datang</div>
            <div class="login-subtitle">Masuk ke dashboard admin Anda</div>
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <form id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@poolsice.com" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn-login">Masuk</button>
            </form>
        </div>
        <div class="login-visual"></div>
    </div>

    <div id="loginLoadingOverlay" class="loading-overlay" aria-hidden="true">
        <div class="loading-panel" role="status" aria-live="polite" aria-label="Memuat">
            <div class="loading-bars" aria-hidden="true">
                <span style="--bar-delay: 0ms;"></span>
                <span style="--bar-delay: -366ms;"></span>
                <span style="--bar-delay: -733ms;"></span>
            </div>
            <div class="loading-subtitle">Memuat...</div>
        </div>
    </div>

    <script>
        (function() {
            const form = document.getElementById('loginForm');
            const button = form ? form.querySelector('.btn-login') : null;
            const loadingOverlay = document.getElementById('loginLoadingOverlay');

            if (!form || !loadingOverlay) {
                return;
            }

            function showLoadingOverlay() {
                loadingOverlay.classList.add('show');
                loadingOverlay.setAttribute('aria-hidden', 'false');

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Memuat...';
                }
            }

            function hideLoadingOverlay() {
                loadingOverlay.classList.remove('show');
                loadingOverlay.setAttribute('aria-hidden', 'true');

                if (button) {
                    button.disabled = false;
                    button.textContent = 'Masuk';
                }
            }

            form.addEventListener('submit', showLoadingOverlay);
            window.addEventListener('pageshow', hideLoadingOverlay);
        })();
    </script>
</body>
</html>
