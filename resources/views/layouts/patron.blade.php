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


    <a href="{{ url('/') }}" class="site-home-btn" title="Back to Homepage">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    </a>

    <nav class="navbar">
        <ul class="navbar">
            <li>
                <a href="{{ route('patron.home') }}"
                    class="{{ request()->routeIs('patron.home') ? 'active' : '' }}">
                    Packages
                </a>
            </li>
            <li>
                <a href="{{ route('patron.mreserve') }}"
                    class="{{ request()->routeIs('patron.mreserve') ? 'active' : '' }}">
                    Make Reservation
                </a>
            </li>
            <li>
                <a href="{{ route('patron.vreserve') }}"
                    class="{{ request()->routeIs('patron.vreserve') ? 'active' : '' }}">
                    View Reservation
                </a>
            </li>
            <li>
                <a href="{{ route('patron.cancel-reservation') }}"
                    class="{{ request()->routeIs('patron.cancel-reservation') ? 'active' : '' }}">
                    Cancel Reservation
                </a>
            </li>
            <li>
                <a href="{{ route('patron.payment') }}"
                    class="{{ request()->routeIs('patron.payment') ? 'active' : '' }}">
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
