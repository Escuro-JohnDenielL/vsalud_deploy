<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Access - Villa Salud Catering</title>
    @vite('resources/css/login.css')
    <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}" />
</head>
<body>
    <a href="{{ url('/') }}" style="position: absolute; top: 18px; left: 18px; z-index: 10; display: inline-block; padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.86); color: #165c34; text-decoration: none; font-weight: 700; font-size: 12px; border: 1px solid rgba(22,92,52,0.2);">
        Back to Landing
    </a>

    <div class="container">
        <div class="left-section">
            <h1>IT ACCESS PORTAL</h1>
            <p>System administration and account management</p>
            <div class="login-box">
                <h2>Sign In</h2>

                @if ($errors->any())
                    <div class="error-message" style="color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('it.login.submit') }}">
                    @csrf

                    <label>Email Address:</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>

                    <label>Password:</label>
                    <input type="password" name="password" required>

                    <button type="submit">Log In</button>
                </form>

                <p class="login-link">
                    Admin login? <a href="{{ route('admin.login') }}">Open Admin Login</a>
                </p>
            </div>
        </div>
        <div class="right-section" style="background-image: url('/images/background_picture.jpeg'); background-repeat: no-repeat; background-position: center center; background-size: cover;"></div>
    </div>
</body>
</html>
