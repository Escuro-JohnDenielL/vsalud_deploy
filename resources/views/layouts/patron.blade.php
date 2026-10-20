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

    @vite(['resources/css/app.css', 'resources/css/notify.css', 'resources/js/app.js', 'resources/js/notify.js'])
    @stack('styles')
</head>

<body class="vs-layout--patron">
    <header class="header-image">
        <img src="{{ asset('images/background_picture.jpeg') }}" alt="Villa Salud Header" class="header-banner">
    </header>

    <nav class="navbar nav-collapsible vs-patron-nav" aria-label="Main navigation">
        <a href="{{ url('/') }}" class="landing-logo vs-patron-brand" aria-label="Villa Salud home">
            <span class="landing-logo__mark">VS</span>
            <span class="landing-logo__name"><small>VILLA</small><strong>SALUD</strong></span>
        </a>

        <span class="vs-nav-pill" aria-hidden="true" hidden></span>

        <ul class="navbar nav-links" id="patronNavLinks">
            <li>
                <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">
                    Home
                </a>
            </li>
            <li>
                <a href="{{ route('patron.home') }}"
                    class="{{ request()->routeIs('patron.home') ? 'active' : '' }}">
                    Packages
                </a>
            </li>
            <li class="nav-dropdown {{ request()->routeIs('patron.vreserve', 'patron.payment', 'patron.cancel-reservation') ? 'active' : '' }}">
                <a class="nav-dropdown-toggle" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    Manage Booking
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <ul class="nav-dropdown-menu">
                    <li>
                        <a href="{{ route('patron.vreserve') }}"
                            class="{{ request()->routeIs('patron.vreserve') ? 'active' : '' }}">
                            Track Booking
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('patron.payment') }}"
                            class="{{ request()->routeIs('patron.payment') ? 'active' : '' }}">
                            Payment Order
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('patron.cancel-reservation') }}"
                            class="{{ request()->routeIs('patron.cancel-reservation') ? 'active' : '' }}">
                            Cancel Reservation
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-dropdown {{ request()->routeIs('patron.guidelines', 'patron.faq', 'patron.feedback') ? 'active' : '' }}">
                <a class="nav-dropdown-toggle" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    Help
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <ul class="nav-dropdown-menu">
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
            </li>
        </ul>

        <a href="{{ route('patron.vreserve') }}" class="vs-nav-track">Track Booking</a>

        <a href="{{ route('patron.mreserve') }}" class="vs-nav-cta">Make Reservation</a>

        <button type="button" class="nav-toggle" id="patronNavToggle"
                aria-label="Toggle navigation menu" aria-controls="patronNavLinks"
                aria-expanded="false">
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>
    </nav>

    {{-- Site-wide notification confirmation modal (handles ?toast= redirects and flash messages) --}}
    <x-ui.notification-modal />

    <main class="patron-content">
        @yield('content')
    </main>

    <x-site-footer class="vs-patron-footer" />

    @stack('scripts') {{-- Page-specific scripts --}}
</body>

</html>
