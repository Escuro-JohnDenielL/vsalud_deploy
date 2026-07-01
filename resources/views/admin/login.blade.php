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
    <di class="container">
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
                    <div class="success-message" style="color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
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
                    Haven't signed up? <a href="{{ route('admin.signup') }}">Register here!</a>
                </p>
            </div>
        </div>
            <div class="right-section" style="background-image: url('/images/background_picture.jpeg'); background-repeat: no-repeat; background-position: center center; background-size: cover;"></div>
    </div>
    @vite('resources/js/login.js')

</body>
</html>
