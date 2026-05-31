<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Admin')</title>
    @php($faviconPath = public_path('storage/poolsice.png'))
    @php($faviconVersion = file_exists($faviconPath) ? filemtime($faviconPath) : time())
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('storage/poolsice.png') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" href="{{ asset('storage/poolsice.png') }}?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" href="{{ asset('storage/poolsice.png') }}?v={{ $faviconVersion }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255,255,255,0.1);
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .card-header h2 {
            font-size: 1.5rem;
            color: #2c3e50;
        }
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #1E293B;
            border: 1px solid #1E293B;
            border-color: #1E293B;
            color: #fff;
        }
        .btn-primary:hover {
            background: #334155;
            border-color: #334155;
            color: #fff;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background: #229954;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background: #e67e22;
        }
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
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
        @media (max-width: 640px) {
            .loading-panel { width: 78px; min-height: 98px; }
            .loading-subtitle { font-size: 13px; }
            .navbar { padding: 1rem; }
            .navbar-content { flex-direction: column; gap: 1rem; }
            .nav-links { gap: 1rem; flex-wrap: wrap; justify-content: center; }
            .container { padding: 0 1rem; margin: 1rem auto; }
            .card { padding: 1rem; }
            .card-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .btn { width: 100%; text-align: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            .loading-panel,
            .loading-bars span {
                animation: none;
            }
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th,
        table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        table tr:hover {
            background: #f8f9fa;
        }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .text-danger {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        .badge-approved {
            background: #d4edda;
            color: #155724;
        }
        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>Dashboard Admin</h1>
            <div class="nav-links">
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">Customers</a>
                <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">Orders</a>
            </div>
        </div>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>

    <div id="globalLoadingOverlay" class="loading-overlay" aria-hidden="true">
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
            const loadingOverlay = document.getElementById('globalLoadingOverlay');

            if (!loadingOverlay) {
                return;
            }

            let autoHideTimer = null;

            function showLoadingOverlay() {
                if (autoHideTimer) {
                    clearTimeout(autoHideTimer);
                    autoHideTimer = null;
                }
                loadingOverlay.classList.add('show');
                loadingOverlay.setAttribute('aria-hidden', 'false');
            }

            function hideLoadingOverlay() {
                loadingOverlay.classList.remove('show');
                loadingOverlay.setAttribute('aria-hidden', 'true');
            }

            function shouldSkipLoading(target) {
                if (!target) {
                    return true;
                }

                if (target.closest('[data-no-loading]')) {
                    return true;
                }

                if (target.matches('.toast-close')) {
                    return true;
                }

                const onclick = String(target.getAttribute('onclick') || '').toLowerCase();
                if (onclick.includes('window.print') || onclick.includes('confirm') || onclick.includes('deletemodal') || onclick.includes('opendeletemodal') || onclick.includes('showdeletemodal') || onclick.includes('hapus')) {
                    return true;
                }

                const form = target.closest('form');
                if (!form) {
                    return false;
                }

                const onsubmit = String(form.getAttribute('onsubmit') || '').toLowerCase();
                const hasDeleteMethod = form.querySelector('input[name="_method"][value="DELETE"]') !== null;
                const hasDeferredConfirmFlow = onsubmit.includes('preventdefault') || onsubmit.includes('deletemodal') || onsubmit.includes('modal');
                const hasNativeConfirmFlow = onsubmit.includes('confirm');

                return hasDeleteMethod && (hasDeferredConfirmFlow || hasNativeConfirmFlow);
            }

            document.addEventListener('click', function(event) {
                const target = event.target.closest('button, a, input[type="submit"], input[type="button"]');

                if (!target || shouldSkipLoading(target)) {
                    return;
                }

                if (target.tagName === 'A' && (!target.getAttribute('href') || target.getAttribute('href').startsWith('#'))) {
                    return;
                }

                if (target.disabled) {
                    return;
                }

                const shouldShow = target.matches('button:not([type="button"]), input[type="submit"], a.btn, button.btn');

                if (shouldShow) {
                    showLoadingOverlay();
                    autoHideTimer = setTimeout(function() {
                        hideLoadingOverlay();
                    }, 1000);
                }
            }, true);

            document.addEventListener('submit', function(event) {
                const form = event.target;

                if (!form) {
                    return;
                }

                const onsubmit = String(form.getAttribute('onsubmit') || '').toLowerCase();
                const hasDeleteMethod = form.querySelector('input[name="_method"][value="DELETE"]') !== null;
                const hasDeferredConfirmFlow = onsubmit.includes('preventdefault') || onsubmit.includes('deletemodal') || onsubmit.includes('modal');

                if (form.closest('[data-no-loading]') || (hasDeleteMethod && hasDeferredConfirmFlow)) {
                    return;
                }

                showLoadingOverlay();
            }, true);

            window.addEventListener('beforeunload', showLoadingOverlay);
            window.addEventListener('beforeprint', hideLoadingOverlay);
            window.addEventListener('afterprint', hideLoadingOverlay);
            window.addEventListener('pageshow', hideLoadingOverlay);
        })();
    </script>

    @stack('scripts')
</body>
</html>
