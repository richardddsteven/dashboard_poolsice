<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Admin')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-blue: #0F172A; /* Deep Blue / Slate 900 */
            --primary-light: #1E293B; /* Slate 800 */
            --accent-blue: #3B82F6; /* Bright Blue */
            --accent-glow: rgba(59, 130, 246, 0.25);
            --bg-body: #F1F5F9; /* Slate 100 */
            --bg-card: #FFFFFF;
            --text-main: #1E293B; /* Slate 800 */
            --text-muted: #64748B; /* Slate 500 */
            --border-color: #E2E8F0; /* Slate 200 */
            --sidebar-width: 280px;
            --header-height: 80px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Layout Structure */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-blue);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 50;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.05);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-header {
            height: var(--header-height);
            padding: 0 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
            flex-shrink: 0;
        }
        
        .logo-icon svg {
            width: 22px;
            height: 22px;
            fill: white;
        }

        .logo-text h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: white;
        }
        
        .logo-text span {
            color: var(--accent-blue);
        }

        .sidebar-nav {
            flex: 1;
            padding: 32px 16px;
            overflow-y: auto;
        }

        /* Custom Scrollbar */
        .sidebar-nav::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-section {
            margin-bottom: 32px;
        }

        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.4);
            margin: 0 0 16px 16px;
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            color: rgba(255, 255, 255, 0.6); /* Soft white text */
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 6px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 14px;
            position: relative;
        }

        .nav-item:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(4px);
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
            color: #60A5FA;
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 12px;
            bottom: 12px;
            width: 4px;
            background: #3B82F6;
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 8px var(--accent-blue);
        }

        .nav-icon {
            margin-right: 14px;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            transition: all 0.2s;
        }

        .nav-item:hover .nav-icon,
        .nav-item.active .nav-icon {
            opacity: 1;
            transform: scale(1.1);
            color: inherit;
        }
        
        .nav-icon svg {
            width: 100%;
            height: 100%;
            fill: currentColor;
        }

        .nav-badge {
            margin-left: auto;
            background: #EF4444;
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }

        /* Submenu Styling */
        .nav-submenu {
            display: none;
            padding-left: 12px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        
        .nav-submenu.open {
            display: block;
        }
        
        .nav-submenu .nav-item {
            font-size: 13px;
            padding: 10px 16px;
            margin-bottom: 4px;
            background: transparent;
        }
        
        .nav-submenu .nav-item:hover {
            background: rgba(255, 255, 255, 0.03);
            transform: translateX(4px);
        }
        
        .nav-submenu .nav-item.active {
            background: rgba(59, 130, 246, 0.1);
            color: #93C5FD;
        }
        
        .nav-submenu .nav-item.active::before {
            display: none; /* Remove left border from submenu items */
        }
        
        .nav-submenu .nav-icon {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            opacity: 0.7;
        }
        
        .nav-arrow {
            margin-left: auto;
            transition: transform 0.2s ease;
            width: 16px;
            height: 16px;
            opacity: 0.6;
        }
        
        .nav-item[aria-expanded="true"] .nav-arrow {
            transform: rotate(180deg);
        }

        /* Sidebar Footer / Profile */
        .sidebar-footer {
            padding: 24px;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            padding: 12px;
            border-radius: 12px;
            transition: background 0.2s;
        }
        
        .user-profile:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .avatar-circle {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #475569 0%, #334155 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-size: 14px;
            font-weight: 700;
            color: white;
            margin-bottom: 3px;
        }

        .user-role {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 500;
        }

        .logout-icon {
            width: 20px;
            height: 20px;
            opacity: 0.5;
            transition: opacity 0.2s;
        }
        
        .user-profile:hover .logout-icon {
            opacity: 1;
            color: #EF4444;
        }
        
        .logout-icon svg {
             width: 100%;
             height: 100%;
             fill: currentColor;
        }

        /* Main Content Wrapper */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--bg-body);
        }

        /* Top Bar */
        .topbar {
            height: var(--header-height);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .welcome-text h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-blue);
            letter-spacing: -0.5px;
        }

        .welcome-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 500;
        }

        /* Content Area */
        .content {
            padding: 40px;
            flex: 1;
        }

        /* Modern Card Styling */
        .card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(226, 232, 240, 0.6);
            margin-bottom: 32px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.3px;
        }

        /* Buttons & Forms */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
            filter: brightness(1.1);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }
        
        .btn-secondary {
            background: white;
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
            transform: translateY(-1px);
        }

        .form-control, .form-select, .search-input {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-size: 14px;
            color: var(--text-main);
            background-color: white;
            transition: all 0.2s;
            outline: none;
            width: 100%;
        }

        .form-control:focus, .form-select:focus, .search-input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        
        /* Table Styles */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table th {
            text-align: left;
            padding: 16px 20px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            background: #F8FAFC;
        }
        
        .table td {
            padding: 16px 20px;
            font-size: 14px;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover td {
            background-color: #F8FAFC;
        }

        /* Filter Controls Container specifically for our recent changes */
        .filter-container {
            background: #F8FAFC;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            margin-bottom: 32px;
            display: flex;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* Search Bar in Content (orders, customers) */
        .search-bar-container {
            position: relative;
            max-width: 400px;
        }
        
        .search-bar-container input {
            padding-left: 44px;
            border-radius: 99px;
        }
        
        .search-icon-inside {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        /* Responsive Mobile */
        .hamburger {
            display: none;
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        
        .hamburger span {
            width: 20px;
            height: 2px;
            background: var(--text-main);
            border-radius: 2px;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 49;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
                box-shadow: 8px 0 32px rgba(0,0,0,0.2);
            }
            .main-content {
                margin-left: 0;
            }
            .hamburger {
                display: flex;
            }
            .sidebar-overlay.active {
                display: block;
            }
            .topbar {
                padding: 0 20px;
            }
            .content {
                padding: 20px;
            }
            .welcome-text h2 {
                font-size: 18px;
            }
        }

        /* Global Badge Styles moved from individual pages */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #D97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        .status-approved, .status-completed, .status-paid {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .status-rejected, .status-canceled, .status-unpaid {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 32px;
            flex-wrap: wrap;
        }
        .pagination-btn, .page-link {
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: white;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .pagination-btn.active, .page-item.active .page-link {
            background: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
            box-shadow: 0 4px 12px var(--accent-glow);
        }
        .pagination-btn:hover:not(.active), .page-link:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
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
                <!-- <div class="logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 8.5l-2.5 1.25L12 11zm0 2.5l-5-2.5-5 2.5L12 22l10-8.5-5-2.5-5 2.5z"/>
                    </svg>
                </div> -->
                <div class="logo-text" style="margin-left: 12px;">
                    <h1>Pools<span>Ice</span></h1>
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
                    <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                        <span class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M20 6h-2V5a3 3 0 0 0-6 0v1h-2V5a3 3 0 0 0-6 0v1H2v16h20V6zM6 5a1 1 0 0 1 2 0v1H6V5zm8 0a1 1 0 0 1 2 0v1h-2V5zM4 8h16v12H4V8zm3 2h10v2H7v-2zm0 4h6v2H7v-2z"/>
                            </svg>
                        </span>
                        <span>Stok</span>
                    </a>
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
                    <div class="nav-section-title">Finance</div>
                    
                    <!-- Parent Menu Item -->
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

                    <!-- Submenu Items -->
                    <div id="finance-submenu" class="nav-submenu {{ request()->routeIs('finance.*') || request()->routeIs('expenses.*') ? 'open' : '' }}">
                        <a href="{{ route('finance.index') }}" class="nav-item {{ request()->routeIs('finance.*') ? 'active' : '' }}">
                            <!-- <span class="nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                                </svg>
                            </span> -->
                            <span>Rekapan</span>
                        </a>
                        <a href="{{ route('expenses.index') }}" class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                            <!-- <span class="nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                                </svg>
                            </span> -->
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
                <div class="topbar-left">
                    <button class="hamburger" onclick="toggleSidebar()">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <div class="welcome-text">
                        <h2>Halo, {{ explode(' ', auth()->user()->name ?? 'Admin')[0] }}!</h2>
                        <div class="welcome-subtitle">Selamat Datang di Pools Ice Dashboard</div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function toggleSubmenu(id, element) {
            // Toggle submenu visibility
            const submenu = document.getElementById(id);
            submenu.classList.toggle('open');
            
            // Toggle arrow rotation via aria-expanded functionality
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
        let globalRealtimeLastUpdateToken = '';

        function renderPendingBadge(pendingCount) {
            const badge = document.getElementById('sidebarPendingBadge');
            if (!badge) {
                return;
            }

            badge.textContent = String(pendingCount);
            badge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
        }

        async function pollNewOrdersOnly() {
            const badge = document.getElementById('sidebarPendingBadge');
            if (!badge) {
                return;
            }

            try {
                const statusUrl = `{{ route('orders.realtime.status') }}?last_id=${globalRealtimeLastOrderId}`;
                const response = await fetch(statusUrl, {
                    headers: window.getRealtimeAuthHeaders()
                });

                if (!response.ok) {
                    return;
                }

                const result = await response.json();
                const latestOrderId = Number(result.latestOrderId || 0);
                const latestUpdateToken = String(result.latestUpdateToken || '');
                const hasStatusUpdate = latestUpdateToken !== '' && latestUpdateToken !== globalRealtimeLastUpdateToken;

                if (latestOrderId > globalRealtimeLastOrderId) {
                    globalRealtimeLastOrderId = latestOrderId;
                }

                if (latestUpdateToken !== '') {
                    globalRealtimeLastUpdateToken = latestUpdateToken;
                }

                const pendingCount = Number(result.pendingCount || 0);
                renderPendingBadge(pendingCount);

                if (result.newOrder) {
                    window.dispatchEvent(new CustomEvent('realtime:new-order', {
                        detail: result,
                    }));
                }

                if (hasStatusUpdate) {
                    window.dispatchEvent(new CustomEvent('realtime:orders-changed', {
                        detail: result,
                    }));
                }
            } catch (error) {
                console.error(error);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            setInterval(pollNewOrdersOnly, 5000);
        });
    </script>
    
    <!-- Global Toast Notification -->
    @if(session('success') || session('error'))
    @php
        $toastType = session('error') ? 'error' : 'success';
        $toastTitle = session('error') ? 'Gagal!' : 'Berhasil!';
        $toastMessage = session('error') ?? session('success');
    @endphp
    <div id="globalToast" class="toast-notification {{ $toastType === 'error' ? 'toast-error' : 'toast-success' }} show">
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
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <style>
        .toast-notification {
            position: fixed;
            top: 32px;
            right: 32px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: flex-start;
            padding: 16px;
            gap: 16px;
            width: 340px;
            z-index: 9999;
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .toast-notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        .toast-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .toast-success .toast-icon {
            background: #dcfce7;
            color: #22c55e;
        }
        .toast-error .toast-icon {
            background: #fef2f2;
            color: #ef4444;
        }
        .toast-icon svg {
            width: 18px;
            height: 18px;
        }
        .toast-content {
            flex: 1;
        }
        .toast-content h4 {
            margin: 0 0 4px 0;
            font-size: 15px;
            color: #1e293b;
            font-weight: 700;
        }
        .toast-content p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
            line-height: 1.4;
        }
        .toast-close {
            background: none;
            border: none;
            color: #cbd5e1;
            cursor: pointer;
            padding: 4px;
            margin: -4px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .toast-close:hover {
            color: #64748b;
            background: #f1f5f9;
        }
        @media (max-width: 768px) {
            .toast-notification {
                top: 20px;
                right: 20px;
                left: 20px;
                width: auto;
            }
        }
    </style>

    <script>
        const toast = document.getElementById('globalToast');

        function closeToast() {
            if (!toast) {
                return;
            }

            toast.classList.remove('show');
            setTimeout(() => {
                toast.style.display = 'none';
            }, 400);
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (!toast) {
                return;
            }

            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            setTimeout(() => {
                closeToast();
            }, 4000);
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
