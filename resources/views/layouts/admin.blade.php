<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel')</title>

    <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body>

    <header class="header-image">
        <img src="{{ asset('images/background_picture.jpeg') }}" alt="Villa Salud Header" class="header-banner">
    </header>

    <nav class="navbar">
        <ul class="navbar">
            <li>
                <a href="{{ url('/') }}" class="site-home-link">
                    Landing Page
                </a>
            </li>
            <li>
                <a href="{{ route('admin.home') }}" class="{{ request()->is('admin/home') ? 'active' : '' }}">
                    Home
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
                <a href="{{ route('admin.reserve_logs') }}"
                    class="{{ request()->is('admin/reserve_logs') ? 'active' : '' }}">
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
