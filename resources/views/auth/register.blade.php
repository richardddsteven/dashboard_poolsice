<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Admin Dashboard</title>
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
        .container-register {
            display: flex;
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.08);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        .register-left {
            flex: 1;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .register-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 32px;
        }
        .register-subtitle {
            color: #718096;
            font-size: 15px;
            margin-bottom: 28px;
        }
        .google-btn {
            width: 100%;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            font-size: 15px;
            font-weight: 500;
            color: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            margin-bottom: 18px;
            transition: box-shadow 0.2s;
        }
        .google-btn:hover {
            box-shadow: 0 2px 8px rgba(102,126,234,0.08);
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 18px 0;
            color: #718096;
            font-size: 14px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        .divider span {
            padding: 0 12px;
        }
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        input[type="email"]:focus, input[type="password"]:focus, input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102,126,234,0.08);
        }
        .btn-register {
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
        .btn-register:hover {
            box-shadow: 0 2px 8px rgba(102,126,234,0.08);
        }
        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: #222;
        }
        .login-link a {
            color: #000000ff;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }
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
        .terms {
            font-size: 13px;
            color: #718096;
            margin-top: 15px;
            line-height: 1.5;
        }
        .terms a { color: #667eea; text-decoration: none; }
        .terms a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container-register">
        <div class="register-left">
            <div class="logo-section" style="margin-bottom: 0;">
                <span style="font-size:22px;font-weight:700;color:#222;margin-bottom:18px;display:block;">Pools Ice</span>
            </div>
            <div class="register-title">Create Account</div>
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul style="list-style: none;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Full Name" value="{{ old('name') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" placeholder="Email address" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 8 characters" required>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter your password" required>
                </div>
                <button type="submit" class="btn-register">Create Account</button>
            </form>
            <div class="login-link">
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
            </div>
        </div>
    </div>
</body>
</html>
