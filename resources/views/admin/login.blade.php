<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin sign in - Villa Salud</title>
    <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    @vite('resources/css/login.css')
</head>

<body class="vs-layout--admin-auth">
    <a href="{{ url('/') }}" class="site-home-btn" title="Back to Homepage" aria-label="Back to homepage">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    </a>

    <x-auth-card>
        <div class="auth-head">
            <h1 class="auth-title">Admin sign in</h1>
            <p class="auth-sub">Sign in to manage reservations and events.</p>
        </div>

        @if (session('success'))
            <div class="auth-alert auth-alert--success" role="status" aria-live="polite">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="auth-alert auth-alert--error" role="alert" aria-live="assertive">{{ $errors->first() }}</div>
        @endif

        <form id="login-form" class="auth-form" method="POST" action="{{ route('admin.login.submit') }}">
            @csrf

            <div class="auth-field @error('email') auth-field--error @enderror">
                <label class="auth-label" for="email">Email address</label>
                <input class="auth-input" type="email" id="email" name="email" value="{{ old('email') }}"
                       placeholder="you@villasalud.com" autocomplete="username" required
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <span class="auth-error" id="email-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="password">Password</label>
                <div class="auth-input-group">
                    <input class="auth-input" type="password" id="password" name="password"
                           placeholder="Enter your password" autocomplete="current-password" required>
                    <button type="button" class="auth-eye" id="toggle-password"
                            aria-label="Show password" aria-pressed="false" aria-controls="password">
                        <svg class="auth-eye__icon" id="eye-show" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="auth-eye__icon" id="eye-hide" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="auth-btn">Log in</button>
        </form>

        <p class="auth-note">Account registration is managed by IT administration.</p>
    </x-auth-card>

    @vite('resources/js/login.js')

</body>
</html>
