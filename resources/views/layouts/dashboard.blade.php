<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Admin')</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/poolsice.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0F172A;
            --primary-light: #1E293B;
            --accent: #3B82F6;
            --accent-hover: #2563EB;
            --accent-glow: rgba(59, 130, 246, 0.15);
            --bg-body: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #0F172A;
            --text-secondary: #334155;
            --text-muted: #64748B;
            --text-light: #94A3B8;
            --border-color: #E2E8F0;
            --border-light: #F1F5F9;
            --sidebar-width: 264px;
            --header-height: 124px;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
            --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
            font-size: 15px;
            line-height: 1.6;
        }

        /* ========== Layout ========== */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ========== Sidebar ========== */
        .sidebar {
            width: var(--sidebar-width);
            background: #FFFFFF;
            color: var(--text-main);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 50;
            border-right: 1px solid var(--border-color);
            transition: transform 0.3s var(--transition);
        }

        .sidebar-header {
            height: var(--header-height);
            padding: 0 24px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-light);
            flex-shrink: 0;
        }

        .sidebar-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-bottom: 10px;
        }

        .sidebar-logo img {
            height: 92px;
            width: auto;
            display: block;
            object-fit: contain;
        }

        .sidebar-nav {
            flex: 1;
            padding: 24px 12px 24px;
            overflow-y: auto;
        }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 4px; }

        .nav-section { margin-bottom: 28px; }
        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-light);
            margin: 0 0 12px 16px;
            font-weight: 600;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            margin-bottom: 2px;
            transition: all var(--transition);
            font-weight: 500;
            font-size: 14px;
            position: relative;
        }
        .nav-item:hover {
            color: var(--text-main);
            background: var(--bg-body);
        }
        .nav-item.active {
            background: #EFF6FF;
            color: var(--accent);
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 3px;
            background: var(--accent);
            border-radius: 0 3px 3px 0;
        }

        .nav-icon {
            margin-right: 12px;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.85;
            flex-shrink: 0;
        }
        .nav-item:hover .nav-icon,
        .nav-item.active .nav-icon { opacity: 1; }
        .nav-icon svg { width: 100%; height: 100%; fill: currentColor; }

        .nav-badge {
            margin-left: auto;
            background: #EF4444;
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            line-height: 20px;
        }

        .nav-submenu { display: none; padding-left: 8px; margin-bottom: 4px; }
        .nav-submenu.open { display: block; }
        .nav-submenu .nav-item { font-size: 13px; padding: 10px 16px; margin-bottom: 1px; }
        .nav-submenu .nav-item.active::before { display: none; }
        .nav-submenu .nav-icon { width: 16px; height: 16px; margin-right: 10px; }

        .nav-arrow {
            margin-left: auto;
            transition: transform 0.2s ease;
            width: 14px; height: 14px;
            opacity: 0.55;
        }
        .nav-item[aria-expanded="true"] .nav-arrow { transform: rotate(180deg); }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border-light);
            flex-shrink: 0;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 12px;
            border-radius: var(--radius-sm);
            transition: background var(--transition);
        }
        .user-profile:hover { background: var(--bg-body); }

        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, #334155 0%, #1E293B 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 14px; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 11px; color: var(--text-muted); font-weight: 500; }

        .logout-icon { width: 18px; height: 18px; opacity: 0.45; color: var(--text-muted); transition: all var(--transition); }
        .user-profile:hover .logout-icon { opacity: 0.9; color: #EF4444; }
        .logout-icon svg { width: 100%; height: 100%; fill: currentColor; }

        /* ========== Main Content ========== */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s var(--transition);
            min-height: 100vh;
            min-width: 0; /* Prevents flex children from overflowing */
        }

        /* ========== Top Bar ========== */
        .topbar {
            height: 64px;
            background: #fff;
            padding: 0 24px;
            display: none; /* Mobile only */
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 40;
            border-bottom: 1px solid var(--border-color);
        }


        /* ========== Content Area ========== */
        .content {
            padding: 32px;
            flex: 1;
            min-width: 0;
        }

        /* ========== Page Header ========== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 25px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }
        .page-subtitle {
            font-size: 15px;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* ========== Card ========== */
        .card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            transition: box-shadow var(--transition);
        }
        .card:hover {
            box-shadow: var(--shadow-md);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-light);
        }
        .card-title {
            font-size: 17px;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: -0.2px;
        }

        /* ========== Buttons ========== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all var(--transition);
            font-family: inherit;
            line-height: 1.5;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }
        .btn-success {
            background: #10B981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        .btn-danger:hover {
            background: #DC2626;
            transform: translateY(-1px);
        }
        .btn-warning {
            background: #F59E0B;
            color: white;
        }
        .btn-secondary {
            background: white;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover {
            background: var(--bg-body);
            border-color: #CBD5E1;
        }

        /* ========== Loading Overlay ========== */
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
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 14px;
            overflow: hidden;
            transform: translateY(0);
        }
        .loading-bars {
            display: flex;
            align-items: flex-end;
            gap: 5px;
            height: 58px;
            margin-bottom: 0;
            z-index: 1;
        }
        .loading-bars span {
            width: 5px;
            height: var(--bar-height);
            border-radius: 999px;
            background: linear-gradient(180deg, #2563eb 100%, #2563eb 100%);
            box-shadow: 0 0 18px rgba(37, 99, 235, 0.45);
            transform-origin: center bottom;
            animation: loadingBarPulse 1.05s ease-in-out infinite;
            animation-delay: var(--bar-delay);
        }
        .loading-subtitle {
            z-index: 1;
            color: rgba(255, 255, 255, 0.82);
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.01em;
            line-height: 1.2;
        }
        @keyframes loadingBarPulse {
            0%, 100% {
                transform: scaleY(0.72);
                opacity: 0.74;
            }
            50% {
                transform: scaleY(1.1);
                opacity: 1;
            }
        }
        @media (max-width: 640px) {
            .loading-panel { gap: 10px; }
            .loading-subtitle { font-size: 13px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .loading-panel,
            .loading-bars span {
                animation: none;
            }
        }

        /* ========== Forms ========== */
        .form-control, .form-select, .search-input {
            padding: 11px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--text-main);
            background-color: white;
            transition: all var(--transition);
            outline: none;
            width: 100%;
            font-family: inherit;
            line-height: 1.5;
        }
        .form-control:focus, .form-select:focus, .search-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        /* ========== Table ========== */
        .table-responsive { overflow-x: auto; }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table th {
            text-align: left;
            padding: 12px 18px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-body);
            white-space: nowrap;
        }
        .table td {
            padding: 14px 18px;
            font-size: 14px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }
        .table tr:last-child td { border-bottom: none; }
        .table tr:hover td { background-color: var(--bg-body); }

        /* ========== Status Badges ========== */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.08);
            color: #D97706;
        }
        .status-approved, .status-completed, .status-paid {
            background: rgba(16, 185, 129, 0.08);
            color: #059669;
        }
        .status-rejected, .status-canceled, .status-unpaid {
            background: rgba(239, 68, 68, 0.08);
            color: #DC2626;
        }

        /* ========== Badge ========== */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        /* ========== Pagination ========== */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .pagination-btn, .page-link {
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: white;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all var(--transition);
            cursor: pointer;
        }
        .pagination-btn.active, .page-item.active .page-link {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        .pagination-btn:hover:not(.active):not(.disabled), .page-link:hover {
            background: var(--bg-body);
            border-color: #CBD5E1;
        }

        /* ========== Filter Container ========== */
        .filter-container {
            background: var(--bg-body);
            padding: 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            margin-bottom: 24px;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* ========== Alert ========== */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-success { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
        .alert-danger { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

        /* ========== Custom Select ========== */
        .custom-select-wrapper { position: relative; width: 100%; user-select: none; }
        .custom-select-trigger {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 11px 16px;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            color: var(--text-secondary);
            transition: all var(--transition);
        }
        .custom-select-wrapper.open .custom-select-trigger {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .custom-select-wrapper.open .select-icon { transform: rotate(180deg); }
        .select-icon { transition: transform 0.2s ease; }
        .text-placeholder { color: var(--text-light); }
        .custom-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            margin-top: 6px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-8px);
            transition: all 0.15s ease;
            z-index: 1050;
            max-height: 160px;
            overflow-y: auto;
        }
        .custom-select-wrapper.open .custom-options {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
            transform: translateY(0);
        }
        .custom-option {
            padding: 11px 16px;
            font-size: 14px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: background 0.1s ease;
        }
        .custom-option:hover { background: var(--bg-body); }
        .custom-option.selected { background: #EFF6FF; color: var(--accent); font-weight: 500; }

        .custom-select-disabled {
            background: var(--bg-body);
            cursor: not-allowed;
            color: var(--text-muted);
        }

        /* ========== Hamburger ========== */
        .hamburger {
            display: none;
            width: 36px;
            height: 36px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            cursor: pointer;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .hamburger span { width: 18px; height: 2px; background: var(--text-main); border-radius: 2px; }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 49;
        }

        /* ========== Responsive ========== */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); box-shadow: 8px 0 32px rgba(0,0,0,0.15); }
            .main-content { margin-left: 0; }
            .hamburger { display: flex; }
            .sidebar-overlay.active { display: block; }
            .topbar { display: flex; padding: 0 16px; justify-content: space-between; }
            .content { padding: 16px; }
            
            /* Responsive Utilities */
            .page-header { flex-direction: column; gap: 16px; align-items: stretch; }
            .page-header > div:last-child, .page-header > a, .page-header > button { width: 100%; justify-content: center; }
            
            .card-header { flex-direction: column; align-items: stretch; gap: 16px; }
            .card-header form { flex-direction: column; width: 100%; }
            .card-header form > div, .card-header form > input, .card-header form > button { width: 100%; }
            .card-header form > div > input { width: 100% !important; }
            
            .filter-container { flex-direction: column; }
            .filter-container > div { width: 100%; min-width: 100%; }
            
            /* Reports filter form responsive */
            #financeFilterSelectWrapper, form[action*="reports"] > div { width: 100% !important; min-width: 100% !important; }
            form[action*="reports"] { flex-direction: column; align-items: stretch !important; }
            form[action*="reports"] > div:last-child { display: flex; flex-direction: column; width: 100%; }
            form[action*="reports"] > div:last-child > button, form[action*="reports"] > div:last-child > a { width: 100%; justify-content: center; }
            
            /* Report Print Summary Grid */
            #printableReport > div:nth-child(2) > div, #printableReport > div:nth-child(3) { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .page-title { font-size: 22px; }
            .card { padding: 16px; }
            .content { padding: 12px; }
            
            /* Stack tables on mobile if needed, or allow horizontal scroll */
            .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 0; border: 1px solid var(--border-light); border-radius: var(--radius-sm); }
            
            /* Dashboard Grid */
            .grid-cols-4 { grid-template-columns: 1fr 1fr !important; }
            .grid-cols-3 { grid-template-columns: 1fr !important; }
            .grid-cols-2 { grid-template-columns: 1fr !important; }
        }
        
        @media (max-width: 480px) {
            .grid-cols-4 { grid-template-columns: 1fr !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="layout">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/storage/poolsice.png" alt="Pools Ice">
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Ringkasan</div>
                    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                            </svg>
                        </span>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Manajemen</div>
                    <a href="javascript:void(0)" class="nav-item {{ request()->routeIs('stocks.*') || request()->routeIs('ice-types.*') ? 'active' : '' }}" onclick="toggleSubmenu('stock-submenu', this)" aria-expanded="{{ request()->routeIs('stocks.*') || request()->routeIs('ice-types.*') ? 'true' : 'false' }}">
                        <span class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M20 6h-2V5a3 3 0 0 0-6 0v1h-2V5a3 3 0 0 0-6 0v1H2v16h20V6zM6 5a1 1 0 0 1 2 0v1H6V5zm8 0a1 1 0 0 1 2 0v1h-2V5zM4 8h16v12H4V8zm3 2h10v2H7v-2zm0 4h6v2H7v-2z"/>
                            </svg>
                        </span>
                        <span>Stok</span>
                        <svg class="nav-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                        </svg>
                    </a>
                    <div id="stock-submenu" class="nav-submenu {{ request()->routeIs('stocks.*') || request()->routeIs('ice-types.*') ? 'open' : '' }}">
                        <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                            <!-- <span class="nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/>
                                </svg>
                            </span> -->
                            <span>Stok</span>
                        </a>
                        <a href="{{ route('ice-types.index') }}" class="nav-item {{ request()->routeIs('ice-types.*') ? 'active' : '' }}">
                            <!-- <span class="nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
                                </svg>
                            </span> -->
                            <span>Jenis Es</span>
                        </a>
                    </div>
                    <a href="{{ route('orders.index') }}" class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                            </svg>
                        </span>
                        <span>Pesanan</span>
                        @php $initialPendingCount = isset($pendingOrdersCount) ? (int) $pendingOrdersCount : 0; @endphp
                        <span id="sidebarPendingBadge" class="nav-badge" style="{{ $initialPendingCount > 0 ? '' : 'display: none;' }}">{{ $initialPendingCount }}</span>
                    </a>
                    <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                        </span>
                        <span>Pelanggan</span>
                    </a>
                    <a href="{{ route('drivers.index') }}" class="nav-item {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                        </span>
                        <span>Supir</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Keuangan</div>
                    <a href="javascript:void(0)" class="nav-item {{ request()->routeIs('finance.*') || request()->routeIs('expenses.*') ? 'active' : '' }}" onclick="toggleSubmenu('finance-submenu', this)" aria-expanded="{{ request()->routeIs('finance.*') || request()->routeIs('expenses.*') ? 'true' : 'false' }}">
                        <span class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                            </svg>
                        </span>
                        <span>Keuangan</span>
                        <svg class="nav-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                        </svg>
                    </a>
                    <div id="finance-submenu" class="nav-submenu {{ request()->routeIs('finance.*') || request()->routeIs('expenses.*') ? 'open' : '' }}">
                        <a href="{{ route('finance.index') }}" class="nav-item {{ request()->routeIs('finance.*') ? 'active' : '' }}">
                            <span>Rekapan</span>
                        </a>
                        <a href="{{ route('expenses.index') }}" class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                            <span>Pengeluaran</span>
                        </a>
                    </div>
                </div>
            </nav>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                    @csrf
                    <div class="user-profile" onclick="document.getElementById('logout-form').submit();">
                        <div class="avatar-circle">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ explode(' ', auth()->user()->name ?? 'Admin')[0] }}</div>
                            <div class="user-role">Administrator</div>
                        </div>
                        <div class="logout-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                            </svg>
                        </div>
                    </div>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="topbar">
                <button class="hamburger" onclick="toggleSidebar()">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div style="font-weight: 600; font-size: 14px; margin-left: 16px;">PoolsIce Dashboard</div>
            </div>

            <!-- Content -->
            <div class="content">
                @yield('content')
            </div>
        </main>
    </div>

    <div id="globalLoadingOverlay" class="loading-overlay" aria-hidden="true">
        <div class="loading-panel" role="status" aria-live="polite">
            <div class="loading-bars" aria-hidden="true">
                <span style="--bar-height: 26px; --bar-delay: 0ms;"></span>
                <span style="--bar-height: 36px; --bar-delay: 90ms;"></span>
                <span style="--bar-height: 22px; --bar-delay: 180ms;"></span>
                <span style="--bar-height: 42px; --bar-delay: 270ms;"></span>
                <span style="--bar-height: 28px; --bar-delay: 360ms;"></span>
                <span style="--bar-height: 20px; --bar-delay: 450ms;"></span>
            </div>
            <div class="loading-subtitle">Memuat...</div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function toggleSubmenu(id, element) {
            const submenu = document.getElementById(id);
            submenu.classList.toggle('open');
            const isExpanded = element.getAttribute('aria-expanded') === 'true';
            element.setAttribute('aria-expanded', !isExpanded);
        }

        window.getRealtimeAuthHeaders = function(extraHeaders = {}) {
            const token = @json(session('sanctum_token'));
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...extraHeaders,
            };
            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }
            return headers;
        };

        let globalRealtimeLastOrderId = Number(@json($latestOrderIdGlobal ?? 0));
        let globalRealtimeLastUpdateToken = String(@json($latestUpdateTokenGlobal ?? ''));

        function renderPendingBadge(pendingCount) {
            const badge = document.getElementById('sidebarPendingBadge');
            if (!badge) return;
            badge.textContent = String(pendingCount);
            badge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
        }

        async function pollNewOrdersOnly() {
            const badge = document.getElementById('sidebarPendingBadge');
            if (!badge) return;
            try {
                const statusParams = new URLSearchParams({
                    last_id: String(globalRealtimeLastOrderId),
                    _rt: String(Date.now()),
                });
                const statusUrl = `{{ route('orders.realtime.status') }}?${statusParams.toString()}`;
                const response = await fetch(statusUrl, {
                    headers: window.getRealtimeAuthHeaders(),
                    cache: 'no-store',
                });
                if (!response.ok) return;
                const result = await response.json();
                const latestOrderId = Number(result.latestOrderId || 0);
                const latestUpdateToken = String(result.latestUpdateToken || '');
                const hasStatusUpdate = latestUpdateToken !== globalRealtimeLastUpdateToken;
                const hasOrderIdReset = latestOrderId < globalRealtimeLastOrderId;

                if (latestOrderId > globalRealtimeLastOrderId) {
                    globalRealtimeLastOrderId = latestOrderId;
                } else if (hasOrderIdReset) {
                    globalRealtimeLastOrderId = latestOrderId;
                }
                globalRealtimeLastUpdateToken = latestUpdateToken;
                const pendingCount = Number(result.pendingCount || 0);
                renderPendingBadge(pendingCount);

                if (result.newOrder) {
                    window.dispatchEvent(new CustomEvent('realtime:new-order', { detail: result }));
                }
                if (hasStatusUpdate || hasOrderIdReset) {
                    window.dispatchEvent(new CustomEvent('realtime:orders-changed', { detail: result }));
                }
            } catch (error) {
                console.error(error);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            pollNewOrdersOnly();
            setInterval(pollNewOrdersOnly, 5000);
        });
    </script>

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

                if (target.matches('.toast-close, .hamburger, [onclick*="toggleSidebar"], [onclick*="toggleSubmenu"]')) {
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
    
    <!-- Toast Notification -->
    @if(session('success') || session('error'))
    @php
        $toastType = session('error') ? 'error' : 'success';
        $toastTitle = session('error') ? 'Gagal!' : 'Berhasil!';
        $toastMessage = session('error') ?? session('success');
    @endphp
    <div id="globalToast" class="toast-notification {{ $toastType === 'error' ? 'toast-error' : 'toast-success' }}">
        <div class="toast-icon">
            @if($toastType === 'error')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            @endif
        </div>
        <div class="toast-content">
            <h4>{{ $toastTitle }}</h4>
            <p>{{ $toastMessage }}</p>
        </div>
        <button class="toast-close" onclick="closeToast()">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <style>
        .toast-notification {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #fff;
            border-radius: var(--radius-md);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
            padding: 14px 16px;
            gap: 12px;
            width: 340px;
            z-index: 9999;
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .toast-notification.show { transform: translateX(0); opacity: 1; }
        .toast-icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .toast-success .toast-icon { background: #F0FDF4; color: #22c55e; }
        .toast-error .toast-icon { background: #FEF2F2; color: #ef4444; }
        .toast-icon svg { width: 16px; height: 16px; }
        .toast-content { flex: 1; }
        .toast-content h4 { margin: 0 0 3px 0; font-size: 15px; color: var(--text-main); font-weight: 600; }
        .toast-content p { margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.4; }
        .toast-close { background: none; border: none; color: #CBD5E1; cursor: pointer; padding: 2px; border-radius: 4px; display: flex; align-items: center; justify-content: center; transition: all var(--transition); }
        .toast-close:hover { color: var(--text-muted); background: var(--bg-body); }
        @media (max-width: 768px) {
            .toast-notification { top: 12px; right: 12px; left: 12px; width: auto; }
        }
    </style>

    <script>
        const toast = document.getElementById('globalToast');
        function closeToast() {
            if (!toast) return;
            toast.classList.remove('show');
            setTimeout(() => { toast.style.display = 'none'; }, 350);
        }
        document.addEventListener('DOMContentLoaded', () => {
            if (!toast) return;
            setTimeout(() => { toast.classList.add('show'); }, 100);
            setTimeout(() => { closeToast(); }, 4000);
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
