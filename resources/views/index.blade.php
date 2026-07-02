<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villa Salud Catering</title>
    @vite('resources/css/index.css')

</head>
<body>

    <div id="toast" class="toast-notification"></div>

    <div class="hero">
        <a href="{{ route('patron.guest.renew') }}" class="guest-reset-link">Reset Guest</a>
        <div class="image-section">
            <img src="{{ asset('./images/background_picture.jpeg') }}" alt="Background" class="background-image">
        </div>
        <div class="content">
            <h1>
                <span>VILLA SALUD CATERING</span>
                <span>BOOKING AND RESERVATION SYSTEM</span>
            </h1>
            <div class="buttons">
                <a href="{{ url('/home') }}" class="btn client">Patrons</a>
                <a href="{{ route('admin.login') }}" class="btn admin">Admin</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <script>
            (function() {
                var toast = document.getElementById('toast');
                toast.textContent = '{{ session('success') }}';
                toast.classList.add('show');
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 2000);
            })();
        </script>
    @endif

</body>
</html>
