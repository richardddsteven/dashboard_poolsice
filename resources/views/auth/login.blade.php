<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #F8F9FA;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container-login {
            display: flex;
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.08);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .login-left {
            flex: 1;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 32px;
        }
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        input[type="email"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102,126,234,0.08);
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            background: #222;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: box-shadow 0.2s;
        }
        .btn-login:hover {
            box-shadow: 0 2px 8px rgba(102,126,234,0.08);
        }
        .register-link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: #222;
        }
        .register-link a {
            color: #000000ff;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover { text-decoration: underline; }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .login-right {
            flex: 1;
            background-image: url('/storage/poolsice.png');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
            min-height: 400px;
            position: relative;
        }
        @media (max-width: 900px) {
            .container-login { flex-direction: column; }
            .login-right { min-height: 220px; }
        }
    </style>
</head>
<body>
    <div class="container-login">
        <div class="login-left">
            <div class="logo-section" style="margin-bottom: 0;">
                <span style="font-size:22px;font-weight:700;color:#222;margin-bottom:18px;display:block;">Pools Ice</span>
            </div>
            <div class="login-title">Selamat Datang</div>
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" placeholder="Email address" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-login">Sign in</button>
            </form>
            <div class="register-link">
                Don't have an account? <a href="{{ route('register') }}">Sign up</a>
            </div>
        </div>
        <div class="login-right"></div>
    </div>
</body>
</html>
