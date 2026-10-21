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

    @vite(['resources/css/app.css', 'resources/css/admin/shared.css', 'resources/css/notify.css', 'resources/js/app.js', 'resources/js/notify.js', 'resources/js/admin/shared.js'])
    @stack('styles')
</head>

<body class="vs-layout--admin">

    <header class="header-image">
        <img src="{{ asset('images/background_picture.jpeg') }}" alt="Villa Salud Header" class="header-banner">
    </header>

    {{-- Floating site-home-btn intentionally removed; logout now lives in the account menu. --}}

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

        // Initials for the account menu avatar (falls back to a single letter).
        $initials = $adminUser
            ? strtoupper(mb_substr($adminUser->f_name ?? '', 0, 1) . mb_substr($adminUser->l_name ?? '', 0, 1))
            : '';
        $initials = $initials !== '' ? $initials : 'A';
    @endphp

    @php
        // Helper: check if any route pattern is active for dropdown highlighting
        $isActive = fn($patterns) => collect((array) $patterns)->contains(fn($p) => request()->is($p));
    @endphp

    <nav class="navbar vs-admin-nav">
        <ul class="navbar vs-admin-nav__links">
            {{-- Dashboard (single link to the report dashboard) --}}
            @if($canAccess('reports'))
            <li>
                <a href="{{ route('admin.report') }}" class="{{ request()->is('admin/report') ? 'active' : '' }}">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    Dashboard
                </a>
            </li>
            @endif

            {{-- Home (public landing page). A plain link, so the admin session stays signed in. --}}
            <li>
                <a href="{{ url('/') }}">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>
                    Home
                </a>
            </li>

            {{-- Reservations Dropdown --}}
            @if($canAccess('reservations') || $canAccess('inquiries') || $canAccess('reserve-logs') || $canAccess('payment-logs') || $canAccess('cancellations') || $canAccess('waitlist'))
            <li class="nav-dropdown {{ $isActive(['admin/reserve', 'admin/inquiry', 'admin/reserve-logs', 'admin/payment-logs', 'admin/cancellations', 'admin/waitlist']) ? 'active' : '' }}">
                <a class="nav-dropdown-toggle" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Reservations
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <ul class="nav-dropdown-menu">
                    @if($canAccess('reservations'))
                    <li>
                        <a href="{{ route('admin.reserve.create') }}"
                            class="{{ request()->is('admin/reserve') ? 'active' : '' }}">
                            Reservation Calendar
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

            {{-- Packages (single link) --}}
            @if($canAccess('packages'))
            <li>
                <a href="{{ route('admin.home') }}" class="{{ request()->is('admin/home') ? 'active' : '' }}">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    Packages
                </a>
            </li>
            @endif

            {{-- Records Dropdown --}}
            @if($canAccess('feedback') || $canAccess('audit-logs') || $canAccess('incident-logs'))
            <li class="nav-dropdown {{ $isActive(['admin/feedback', 'admin/audit-logs', 'admin/incident-logs']) ? 'active' : '' }}">
                <a class="nav-dropdown-toggle" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                    Records
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </a>
                <ul class="nav-dropdown-menu">
                    @if($canAccess('feedback'))
                    <li>
                        <a href="{{ route('admin.feedback') }}" class="{{ request()->is('admin/feedback') ? 'active' : '' }}">
                            Customer Feedback
                        </a>
                    </li>
                    @endif
                    @if($canAccess('audit-logs'))
                    <li>
                        <a href="{{ route('admin.audit-logs') }}" class="{{ request()->is('admin/audit-logs') ? 'active' : '' }}">
                            Audit Logs
                        </a>
                    </li>
                    @endif
                    @if($canAccess('incident-logs'))
                    <li>
                        <a href="{{ route('admin.incident-logs') }}" class="{{ request()->is('admin/incident-logs') ? 'active' : '' }}">
                            Incident Logs
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif

            {{-- Settings Dropdown (gear icon) --}}
            <li class="nav-dropdown {{ $isActive(['admin/payment-settings', 'admin/forms*', 'admin/it*']) ? 'active' : '' }}">
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
                </ul>
            </li>

            {{-- Account menu (far right) --}}
            <li class="nav-dropdown vs-account-menu {{ request()->is('admin/profile') ? 'active' : '' }}">
                <button type="button" class="nav-dropdown-toggle vs-account-toggle" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                    <span class="vs-avatar" aria-hidden="true">{{ $initials }}</span>
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <ul class="nav-dropdown-menu vs-account-menu__list">
                    @if($adminUser?->name)
                    <li class="vs-account-menu__header">{{ $adminUser->name }}</li>
                    @endif
                    <li>
                        <a href="{{ route('admin.profile') }}" class="{{ request()->is('admin/profile') ? 'active' : '' }}">
                            Admin Profile
                        </a>
                    </li>
                    <li>
                        <a href="#" id="reportIssueLink">
                            Report an Issue
                        </a>
                    </li>
                    <li class="vs-account-menu__divider" role="separator"></li>
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}" id="adminLogoutForm">
                            @csrf
                            <button type="submit" class="admin-logout-button" id="adminLogoutButton">
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

    {{-- Report an Issue Modal --}}
    <div id="reportIssueModal" class="modal">
        <div class="modal-content">
            <button type="button" class="js-modal-close close-btn" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h2>Report a Security Issue</h2>
            <p class="vs-inline-note report-issue-intro">For reporting security vulnerabilities, suspected breaches, or system problems.</p>

            <div class="detail-section">
                <div class="detail-section-title">Contact</div>
                <div class="detail-section-card">
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><a href="mailto:security@villasalud.com">security@villasalud.com</a></span>
                    </div>
                    <div class="detail-row detail-row--borderless">
                        <span class="detail-label">Response</span>
                        <span class="detail-value">Within 48 hours on business days</span>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">What to Include</div>
                <ul class="report-issue-list">
                    <li>A brief description of the issue</li>
                    <li>How and when it was discovered</li>
                    <li>Any relevant system logs or timestamps</li>
                    <li>Steps to reproduce (if applicable)</li>
                </ul>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">Already Logged?</div>
                <p class="report-issue-copy">
                    Once reported, incidents should be recorded in the <a href="{{ route('admin.incident-logs') }}">Incident Log</a> for tracking.
                    For urgent issues (active breach, service outage), contact the administrator directly.
                </p>
            </div>

            <div class="modal-footer">
                <button class="admin-btn admin-btn-ghost js-modal-close">Close</button>
                <a href="mailto:security@villasalud.com" class="admin-btn admin-btn-primary">Send Email</a>
            </div>
        </div>
    </div>

    <style>
    .detail-section-card {
        background: var(--color-bg);
        border: 1px solid var(--color-border-light);
        border-radius: var(--radius-sm);
        padding: 16px 18px;
    }
    </style>

    {{-- Logout confirmation. The form stays a POST with CSRF; this only asks first. --}}
    <div id="logoutConfirmModal" class="modal" role="dialog" aria-modal="true"
         aria-labelledby="logoutConfirmTitle" aria-describedby="logoutConfirmText">
        <div class="modal-content modal-sm">
            <h2 id="logoutConfirmTitle">Log out?</h2>
            <p id="logoutConfirmText">Are you sure you want to log out?</p>
            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-ghost" id="logoutCancel">Cancel</button>
                <button type="button" class="admin-btn admin-btn-danger" id="logoutConfirm">Log out</button>
            </div>
        </div>
    </div>

    @stack('scripts')

<script>
(function() {
    // Modal helpers for the report issue modal and any .modal on the page.
    // Dropdown behaviour lives in app.js so the admin and patron navs share it.
    function initModals() {
        // Open report issue modal
        var link = document.getElementById('reportIssueLink');
        var reportModal = document.getElementById('reportIssueModal');
        if (link && reportModal) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                window.vsModalPrepareOpen(reportModal);
                reportModal.classList.add('open');
            });
        }

        // Close any modal when clicking the backdrop, X button, or Close button
        document.addEventListener('click', function(e) {
            var closeBtn = e.target.closest('.js-modal-close');
            var modal = e.target.closest('.modal');
            if (closeBtn && modal) {
                if (modal.classList.contains('fade')) return;
                window.vsModalCloseAfterFade(modal, function() {
                    modal.classList.remove('open');
                });
                return;
            }
            // Click on backdrop (the modal itself, not its content)
            if (e.target.classList.contains('modal')) {
                if (e.target.classList.contains('fade')) return;
                window.vsModalCloseAfterFade(e.target, function() {
                    e.target.classList.remove('open');
                });
            }
        });

        // Escape closes any open modal.
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.modal.open').forEach(function(m) {
                window.vsModalCloseAfterFade(m, function() {
                    m.classList.remove('open');
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModals);
    } else {
        initModals();
    }
})();
</script>

<script>
(function () {
    var form = document.getElementById('adminLogoutForm');
    var modal = document.getElementById('logoutConfirmModal');
    if (!form || !modal) return;

    var cancel = document.getElementById('logoutCancel');
    var confirm = document.getElementById('logoutConfirm');
    var trigger = document.getElementById('adminLogoutButton');
    var submitting = false;

    function open() {
        window.vsModalPrepareOpen(modal);
        modal.classList.add('open');
        cancel.focus();
    }

    function close() {
        window.vsModalCloseAfterFade(modal, function () {
            modal.classList.remove('open');
        });
        if (trigger) trigger.focus();
    }

    form.addEventListener('submit', function (event) {
        if (submitting) return;
        event.preventDefault();
        open();
    });

    cancel.addEventListener('click', close);
    confirm.addEventListener('click', function () {
        submitting = true;
        window.vsModalCloseAfterFade(modal, function () {
            form.submit();
        });
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            event.stopPropagation();
            close();
        }
    });

    modal.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            event.stopPropagation();
            close();
            return;
        }
        if (event.key !== 'Tab') return;

        var focusable = [cancel, confirm];
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
})();
</script>

{{-- Site-wide notification confirmation modal (handles ?toast= redirects and flash messages) --}}
<x-ui.notification-modal />

</body>

</html>
