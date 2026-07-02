<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - Villa Salud')</title>

    <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}" />

    {{-- Google Fonts: Inter for UI, Playfair Display for branding --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body>

    <header class="header-image">
        <img src="{{ asset('images/background_picture.jpeg') }}" alt="Villa Salud Header" class="header-banner">
    </header>

    <a href="{{ url('/') }}" class="site-home-btn" title="Back to Homepage">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    </a>

    <nav class="navbar">
        <ul class="navbar">
            <li>
                <a href="{{ route('admin.home') }}" class="{{ request()->is('admin/home') ? 'active' : '' }}">
                    Packages
                </a>
            </li>
            <li>
                <a href="{{ route('admin.reserve.create') }}"
                    class="{{ request()->is('admin/reserve') ? 'active' : '' }}">
                    Reservations
                </a>
            </li>
            <li>
                <a href="{{ route('admin.inquiry') }}" class="{{ request()->is('admin/inquiry') ? 'active' : '' }}">
                    Inquiries
                </a>
            </li>
            <li>
                <a href="{{ route('admin.reserve-logs') }}"
                    class="{{ request()->is('admin/reserve-logs') ? 'active' : '' }}">
                    Reservation Logs
                </a>
            </li>
            <li>
                <a href="{{ route('admin.report') }}" class="{{ request()->is('admin/report') ? 'active' : '' }}">
                    Reports
                </a>
            </li>
            <li>
                <a href="{{ route('admin.profile') }}" class="{{ request()->is('admin/profile') ? 'active' : '' }}">
                    Admin Profile
                </a>
            </li>
        </ul>

    </nav>

    <main class="admin-content">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
