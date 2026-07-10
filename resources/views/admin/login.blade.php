<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Villa Salud Catering</title>
     @vite('resources/css/login.css')
        <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}" />
</head>

<body>
    <a href="{{ url('/') }}" style="position: absolute; top: 18px; left: 18px; z-index: 10; display: inline-block; padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.86); color: #0d7a3e; text-decoration: none; font-weight: 700; font-size: 12px; border: 1px solid rgba(13,122,62,0.2);">
        Back to Home
    </a>
    <div class="container">
        <div class="left-section">
            <h1>Hello, Admin! Welcome to Villa Salud System</h1>
            <p>Your Event, Your Way - Log In to Start!</p>
            <div class="login-box">
                <h2>Log In</h2>

                @if ($errors->any())
                    <div class="error-message" style="color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="success-message" style="color: #0d7a3e; background: #e8f5e9; border: 1px solid #c8e6c9; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                        {{ session('success') }}
                    </div>
                @endif

                <form id="login-form" method="POST" action="{{ route('admin.login.submit') }}">
                    @csrf

                    <label>Email Address:</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>

                    <label>Password:</label>
                    <input type="password" id="password" name="password" required>

                    <div class="checkbox-container">
                        <input type="checkbox" id="show-password">
                        <label for="show-password">Show Password</label>
                    </div>
                    
                    <button type="submit">Log In</button>
                </form>

                <p class="login-link">
                    Account registration is managed by IT administration.
                </p>
            </div>
        </div>
            <div class="right-section" style="background-image: url('/images/background_picture.jpeg'); background-repeat: no-repeat; background-position: center center; background-size: cover;"></div>
    </div>
    @vite('resources/js/login.js')

</body>
</html>
