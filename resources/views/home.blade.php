<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villa Salud Catering</title>
    @vite('resources/css/landing_page.css')

</head>
<body>

    <div class="hero">
        <a href="{{ route('patron.guest.renew') }}" class="guest-reset-link">Reset Guest</a>
        <div class="image-section">
            <img src="{{ asset('./images/background_picture.jpeg') }}" alt="Background" class="background-image">
        </div>
        <div class="content">
            <h1>VILLA SALUD CATERING<br>BOOKING AND RESERVATION SYSTEM</h1>
            @if (session('success'))
                <div style="margin: 14px auto 0; max-width: 520px; background: rgba(255,255,255,0.92); color: #165c34; border: 1px solid rgba(22,92,52,0.25); padding: 12px 16px; border-radius: 10px; font-weight: 600;">
                    {{ session('success') }}
                </div>
            @endif
            <div class="buttons">
                <a href="{{ url('/home') }}" class="btn client">Patrons</a>
                <a href="{{ route('admin.login') }}" class="btn admin">Admin</a>
            </div>
        </div>
    </div>

</body>
</html>
