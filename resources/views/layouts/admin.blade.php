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

    {{-- Removed site-home-btn — use the Logout button in Settings to end your session properly --}}

    @php
        $adminUser = auth('admin')->user();
        $isSuperAdmin = $adminUser && $adminUser->role === 'super_admin';
        // Get granted page slugs for non-super-admin users
        $grantedPages = [];
        if ($adminUser && !$isSuperAdmin) {
            $grantedPages = \App\Models\AdminPagePermission::where('admin_id', $adminUser->admin_id)
                ->pluck('page_slug')
                ->toArray();
        }
        $canAccess = fn($slug) => $isSuperAdmin || in_array($slug, $grantedPages);
    @endphp

    @php
        // Helper: check if any route pattern is active for dropdown highlighting
        $isActive = fn($patterns) => collect((array) $patterns)->contains(fn($p) => request()->is($p));
    @endphp

    <nav class="navbar">
        <ul class="navbar">
            {{-- Bookings Dropdown --}}
            @if($canAccess('reservations') || $canAccess('inquiries') || $canAccess('reserve-logs') || $canAccess('payment-logs') || $canAccess('cancellations') || $canAccess('waitlist'))
            <li class="nav-dropdown {{ $isActive(['admin/reserve', 'admin/inquiry', 'admin/reserve-logs', 'admin/payment-logs', 'admin/cancellations', 'admin/waitlist']) ? 'active' : '' }}">
                <a class="nav-dropdown-toggle" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Bookings
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <ul class="nav-dropdown-menu">
                    @if($canAccess('reservations'))
                    <li>
                        <a href="{{ route('admin.reserve.create') }}"
                            class="{{ request()->is('admin/reserve') ? 'active' : '' }}">
                            Reservations
                        </a>
                    </li>
                    @endif
                    @if($canAccess('inquiries'))
                    <li>
                        <a href="{{ route('admin.inquiry') }}" class="{{ request()->is('admin/inquiry') ? 'active' : '' }}">
                            Inquiries
                        </a>
                    </li>
                    @endif
                    @if($canAccess('reserve-logs'))
                    <li>
                        <a href="{{ route('admin.reserve-logs') }}"
                            class="{{ request()->is('admin/reserve-logs') ? 'active' : '' }}">
                            Reservation Logs
                        </a>
                    </li>
                    @endif
                    @if($canAccess('payment-logs'))
                    <li>
                        <a href="{{ route('admin.payment-logs') }}"
                            class="{{ request()->is('admin/payment-logs') ? 'active' : '' }}">
                            Payment Logs
                        </a>
                    </li>
                    @endif
                    @if($canAccess('cancellations'))
                    <li>
                        <a href="{{ route('admin.cancellations') }}"
                            class="{{ request()->is('admin/cancellations') ? 'active' : '' }}">
                            Cancellation Requests
                        </a>
                    </li>
                    @endif
                    @if($canAccess('waitlist'))
                    <li>
                        <a href="{{ route('admin.waitlist') }}"
                            class="{{ request()->is('admin/waitlist') ? 'active' : '' }}">
                            Waitlist
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif

            {{-- Packages (standalone) --}}
            @if($canAccess('packages'))
            <li>
                <a href="{{ route('admin.home') }}" class="{{ request()->is('admin/home') ? 'active' : '' }}">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    Packages
                </a>
            </li>
            @endif

            {{-- Reports Dropdown --}}
            @if($canAccess('reports') || $canAccess('feedback'))
            <li class="nav-dropdown {{ $isActive(['admin/report', 'admin/feedback']) ? 'active' : '' }}">
                <a class="nav-dropdown-toggle" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Reports
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <ul class="nav-dropdown-menu">
                    @if($canAccess('reports'))
                    <li>
                        <a href="{{ route('admin.report') }}" class="{{ request()->is('admin/report') ? 'active' : '' }}">
                            Reports
                        </a>
                    </li>
                    @endif
                    @if($canAccess('feedback'))
                    <li>
                        <a href="{{ route('admin.feedback') }}" class="{{ request()->is('admin/feedback') ? 'active' : '' }}">
                            Feedback
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif

            {{-- Settings Dropdown (gear icon) --}}
            <li class="nav-dropdown {{ $isActive(['admin/payment-settings', 'admin/forms*', 'admin/it*', 'admin/profile']) ? 'active' : '' }}">
                <a class="nav-dropdown-toggle" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <ul class="nav-dropdown-menu">
                    <li>
                        <a href="{{ route('admin.payment-settings') }}" class="{{ request()->is('admin/payment-settings') ? 'active' : '' }}">
                            Payment Settings
                        </a>
                    </li>
                    @if($isSuperAdmin)
                    <li>
                        <a href="{{ route('admin.forms.index') }}" class="{{ request()->is('admin/forms*') ? 'active' : '' }}">
                            Form Builder
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.it.dashboard') }}" class="{{ request()->is('admin/it*') ? 'active' : '' }}">
                            IT Management
                        </a>
                    </li>
                    @endif
                    <li>
                        <a href="{{ route('admin.profile') }}" class="{{ request()->is('admin/profile') ? 'active' : '' }}">
                            Admin Profile
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
                            @csrf
                            <button type="submit" style="background:none;border:none;color:#dc3545;cursor:pointer;font:inherit;padding:8px 20px;width:100%;text-align:left;">
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>

    <main class="admin-content">
        @yield('content')
    </main>

    @stack('scripts')

<script>
(function() {
    function initNavbar() {
        const navbar = document.querySelector('.navbar ul');
        if (!navbar) return;

        const dropdowns = navbar.querySelectorAll('.nav-dropdown');

        function closeAll() {
            dropdowns.forEach(d => {
                d.classList.remove('open');
                const btn = d.querySelector('.nav-dropdown-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }

        function toggleDropdown(el) {
            const isOpen = el.classList.contains('open');
            closeAll();
            if (!isOpen) {
                el.classList.add('open');
                const btn = el.querySelector('.nav-dropdown-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            }
        }

        // Click toggle to open/close
        navbar.addEventListener('click', function(e) {
            const btn = e.target.closest('.nav-dropdown-toggle');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            const li = btn.closest('.nav-dropdown');
            if (li) toggleDropdown(li);
        });

        // Click outside to close
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown')) {
                closeAll();
            }
        });

        // Escape to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAll();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbar);
    } else {
        initNavbar();
    }
})();
</script>

{{-- Toast notification --}}
<div id="toast" class="toast-notification"></div>
<script>
(function() {
    var t = document.getElementById('toast');
    if (!t) return;
    var type = new URLSearchParams(window.location.search).get('toast');
    var msg = new URLSearchParams(window.location.search).get('toast_msg');
    if (msg && type) {
        t.textContent = decodeURIComponent(msg);
        t.className = 'toast-notification toast-' + type + ' show';
        setTimeout(function(){
            t.classList.remove('show');
            t.textContent = '';
            t.className = 'toast-notification';
        }, 4500);
        // Clean URL without reloading
        if (history.replaceState) {
            var url = window.location.protocol + '//' + window.location.host + window.location.pathname;
            history.replaceState(null, '', url);
        }
    }
})();
</script>

</body>

</html>
