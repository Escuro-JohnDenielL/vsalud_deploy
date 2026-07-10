<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Signup - Villa Salud Catering</title>
    @vite('resources/css/signup.css')
        <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}" />
</head>

<body>
    <div class="wrapper">
    <div class="container">
        <div class="left-section">
            <h1>Welcome to Villa Salud Catering System</h1>
            <p>Enter your personal details to complete your reservation and access all system features.</p>
            <div class="signup-box">
                <h2>Create Account!</h2>

                @if(session('error'))
                <div class="error-message" style="color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    {{ session('error') }}
                </div>
                @endif

                @if(session('success'))
                <div class="success-message" style="color: #28a745; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    {{ session('success') }}
                </div>
                @endif

                <form method="POST" action="{{ route('admin.signup.submit') }}" id="signupForm">
                    @csrf

                    <label>Email Address:</label>
                    <input type="email" name="email" id="email" required placeholder="e.g. johndoe@gmail.com"><br>

                    <label>First Name:</label>
                    <input type="text" name="f_name" id="first_name" required placeholder="e.g John"><br>

                    <label>Last Name:</label>
                    <input type="text" name="l_name" id="last_name" required placeholder="e.g. Doe"><br>

                    <label>Contact Number:</label>
                    <input type="text" id="phone" name="phone" required ><br>

                    <label>Password:</label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-strength-container" style="margin-top:6px; margin-bottom:10px;">
                        <div style="height:6px; background:#e0e0e0; border-radius:4px; overflow:hidden;">
                            <div id="password-strength-bar" style="width:0%; height:100%; background:#dc3545; border-radius:4px;"></div>
                        </div>
                        <span id="password-strength-text" style="font-size:12px; color:#999;"></span>
                    </div>

                    <label>Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required><br>
                    
                    <button type="submit">Sign Up</button>
                </form>

                {{-- Confirm Signup Modal --}}
                <div id="confirmSignupModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
                    <div style="background:#fff; border-radius:8px; padding:24px; max-width:420px; width:90%; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                        <h3 style="font-size:20px; color:#0d7a3e; margin:0 0 12px;">Confirm Your Details</h3>
                        <div id="confirmSignupDetails" style="font-size:14px; color:#333; margin-bottom:20px; line-height:1.8;"></div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" id="cancelSignupBtn" style="width:auto; padding:8px 20px; background:#6c757d;">Cancel</button>
                            <button type="button" id="confirmSignupBtn" style="width:auto; padding:8px 20px; background:#0d7a3e;">Confirm</button>
                        </div>
                    </div>
                </div>

                <p class="login-link">
                    Already have an account? <a href="{{ route('admin.login') }}">Log in here!</a>
                </p>
            </div>
        </div>
        <div class="right-section">
            <img src="{{ asset('images/background_picture.jpeg') }}" alt="Background" class="right-img">
        </div>
    </div>
    </div>
    @vite('resources/js/signup.js')
</body>

</html>