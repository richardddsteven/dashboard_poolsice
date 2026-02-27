<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pools Ice - Premium Ice Cube Service</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3B82F6;
            --primary-dark: #2563EB;
            --secondary-purple: #8B5CF6;
            --bg-main: #F6F8FC;
            --text-main: #1C1C1A;
            --text-muted: #6C757D;
            --border-color: #E5E7EB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: white;
            color: var(--text-main);
            overflow-x: hidden;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 48px;
            max-width: 1280px;
            margin: 0 auto;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-purple) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .brand-text {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .btn-link {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: 14px;
            transition: color 0.2s;
        }

        .btn-link:hover {
            color: var(--primary-blue);
        }

        .btn-primary {
            padding: 10px 20px;
            background: var(--text-main);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .hero {
            max-width: 1280px;
            margin: 0 auto;
            padding: 80px 48px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero h1 {
            font-size: 64px;
            font-weight: 800;
            letter-spacing: -2px;
            line-height: 1.1;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #1C1C1A 0%, #4B5563 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            max-width: 800px;
        }

        .hero p {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 600px;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
        }

        .btn-lg {
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.2s;
        }

        .btn-blue {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .btn-blue:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px rgba(37, 99, 235, 0.3);
        }

        .btn-outline {
            background: white;
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .btn-outline:hover {
            background: var(--bg-main);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1280px;
            margin: 40px auto;
            padding: 0 48px;
        }

        .feature-card {
            padding: 32px;
            background: var(--bg-main);
            border-radius: 20px;
            text-align: left;
            transition: transform 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .feature-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .feature-card p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
        }
        
        .footer {
            border-top: 1px solid var(--border-color);
            padding: 40px 48px;
            text-align: center;
            margin-top: 80px;
            color: var(--text-muted);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .navbar { padding: 20px; }
            .hero { padding: 40px 20px; }
            .hero h1 { font-size: 40px; }
            .features-grid { grid-template-columns: 1fr; padding: 0 20px; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="/" class="brand-logo">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"></path></svg>
            </div>
            <div class="brand-text">Pools Ice</div>
        </a>
        <div class="nav-links">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-link">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-primary">Daftar</a>
                @endauth
            @endif
        </div>
    </nav>

    <div class="hero">
        <h1>Manajemen Produksi Es<br>Yang Lebih Efisien</h1>
        <p>Solusi lengkap untuk mengelola pesanan, stok, pelanggan, dan keuangan bisnis es batu kristal Anda dalam satu dashboard terintegrasi.</p>
        
        <div class="cta-buttons">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-lg btn-blue">Buka Dashboard</a>
            @else
                <a href="{{ route('register') }}" class="btn-lg btn-blue">Mulai Sekarang</a>
                <a href="{{ route('login') }}" class="btn-lg btn-outline">Masuk Akun</a>
            @endauth
        </div>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon" style="color: #3B82F6;">📊</div>
            <h3>Real-time Tracking</h3>
            <p>Pantau penjualan dan stok es batu secara realtime dengan grafik yang interaktif dan detail.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="color: #10B981;">👥</div>
            <h3>Manajemen Pelanggan</h3>
            <p>Kelola data pelanggan dan riwayat pesanan mereka dengan mudah dan terstruktur.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="color: #F59E0B;">💰</div>
            <h3>Laporan Keuangan</h3>
            <p>Rekap pendapatan harian, mingguan, dan bulanan secara otomatis tanpa ribet.</p>
        </div>
    </div>

    <footer class="footer">
        &copy; {{ date('Y') }} Pools Ice System. All rights reserved.
    </footer>

</body>
</html>
