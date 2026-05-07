<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Pools Ice Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/poolsice.png') }}">
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
        }
        .register-container {
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
        .register-form {
            flex: 1;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-title {
            font-size: 27px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .register-subtitle {
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
        .btn-register {
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
        .btn-register:hover {
            background: #1E293B;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15,23,42,0.15);
        }
        .login-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #64748B;
        }
        .login-link a {
            color: #0F172A;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }
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

        .alert-danger ul {
            margin: 0;
            padding-left: 18px;
        }

        .register-visual {
            flex: 1;
            background-color: #FFFFFF;
            background-image: url('/storage/poolsice.png');
            background-position: center;
            background-size: contain;
            background-repeat: no-repeat;
            min-height: 420px;
        }

        @media (max-width: 768px) {
            .register-container { flex-direction: column; margin: 16px; }
            .register-visual { min-height: 180px; }
            .register-form { padding: 32px 24px; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form">
            <div class="register-title">Buat Akun</div>
            <div class="register-subtitle">Daftarkan akun admin baru</div>
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" placeholder="Masukkan nama lengkap" value="{{ old('name') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@poolsice.com" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password" required>
                </div>
                <button type="submit" class="btn-register">Daftar</button>
            </form>
            <div class="login-link">
                Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
            </div>
        </div>
        <div class="register-visual"></div>
    </div>
</body>
</html>
