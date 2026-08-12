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
    <a href="{{ url('/') }}" class="site-home-btn" title="Back to Homepage">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
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
