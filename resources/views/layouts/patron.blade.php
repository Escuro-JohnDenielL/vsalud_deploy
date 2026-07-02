<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Villa Salud Patron')</title>

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


    <nav class="navbar">
        <ul class="navbar">
            <li>
                <a href="{{ url('/') }}" class="site-home-link">
                    Landing Page
                </a>
            </li>
            <li>
                <a href="{{ route('patron.p_home') }}"
                    class="{{ request()->routeIs('patron.p_home') ? 'active' : '' }}">
                    Home
                </a>
            </li>
            <li>
                <a href="{{ route('patron.p_mreserve') }}"
                    class="{{ request()->routeIs('patron.p_mreserve') ? 'active' : '' }}">
                    Make Reservation
                </a>
            </li>
            <li>
                <a href="{{ route('patron.p_vreserve') }}"
                    class="{{ request()->routeIs('patron.p_vreserve') ? 'active' : '' }}">
                    View Reservation
                </a>
            </li>
            <li>
                <a href="{{ route('patron.p_payment') }}"
                    class="{{ request()->routeIs('patron.p_payment') ? 'active' : '' }}">
                    Payment Order
                </a>
            </li>
            <li>
                <a href="{{ route('patron.guidelines') }}"
                    class="{{ request()->routeIs('patron.guidelines') ? 'active' : '' }}">
                    Guidelines
                </a>
            </li>
            <li>
                <a href="{{ route('patron.faq') }}" class="{{ request()->routeIs('patron.faq') ? 'active' : '' }}">
                    FAQs
                </a>
            </li>
            <li>
                <a href="{{ route('patron.feedback') }}"
                    class="{{ request()->routeIs('patron.feedback') ? 'active' : '' }}">
                    Feedback
                </a>
            </li>
        </ul>

    </nav>

    <main class="patron-content">
        @yield('content')
    </main>

    @stack('scripts') {{-- Page-specific scripts --}}
</body>

</html>
