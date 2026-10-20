<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Villa Salud Catering booking and reservation system.">
    <title>Villa Salud | Booking and Reservation</title>
    <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">
    @vite(['resources/css/index.css', 'resources/css/notify.css', 'resources/js/index.js', 'resources/js/notify.js'])
</head>
<body class="landing-page">
    <nav class="landing-nav" aria-label="Main navigation">
        <a href="#home" class="landing-logo" aria-label="Villa Salud home">
            <span class="landing-logo__mark">VS</span>
            <span class="landing-logo__name"><small>VILLA</small><strong>SALUD</strong></span>
        </a>
        <div class="landing-nav__links" id="navMenu">
            <a class="active" href="#home">Home</a>
            <a href="#about">About us</a>
            <a href="#contact">Contact us</a>
        </div>
        <button class="landing-menu-button" id="menuButton" type="button" aria-label="Open navigation menu" aria-controls="navMenu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </nav>
    <main>
        <section id="home" class="landing-hero" aria-label="Villa Salud Catering home">
            <img class="landing-hero__background" src="{{ asset('images/background_picture.jpeg') }}" alt="" fetchpriority="high">
            <div class="landing-hero__overlay"></div>
            <div class="landing-hero__content">
                <p class="landing-eyebrow">Welcome to</p>
                <h1>Villa Salud<br><em>Catering</em></h1>
                <p class="landing-hero__subtitle">Booking and Reservation</p>
                <div class="landing-ornament" aria-hidden="true"><span></span><span></span></div>
            </div>

            <div class="portal-card container">
                <div class="portal-card__intro">
                    <span class="portal-card__eyebrow">Welcome</span>
                    <h2>Choose your portal</h2>
                    <p>Start your Villa Salud experience.</p>
                </div>
                <div class="portal-card__divider" aria-hidden="true"></div>
                <div class="portal-card__actions">
                    <a class="portal-action portal-action--patron" href="{{ route('patron.home') }}">
                        <span class="portal-action__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg></span>
                        <span class="portal-action__copy"><strong>Patrons</strong><small>Book your event</small></span>
                        <span class="portal-action__arrow" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                    </a>
                </div>
            </div>
        </section>

        <section id="about" class="landing-about">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="landing-about__image">
                            <img src="{{ asset('images/background_picture.jpeg') }}" alt="Villa Salud Catering event venue" loading="lazy">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="landing-about__copy">
                            <span class="section-eyebrow">About Villa Salud</span>
                            <h2>Creating moments <em>worth celebrating.</em></h2>
                            <p>Villa Salud is an events venue in Taguig for weddings, birthdays, christenings, and other celebrations.</p>
                            <p>Our digital booking system makes event reservations easier and more convenient for our customers.</p>
                            <a class="landing-outline-link" href="#home">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="landing-contact">
            <div class="container">
                <div class="landing-contact__heading">
                    <span class="section-eyebrow">Get in touch</span>
                    <h2>We'd love to hear <em>from you.</em></h2>
                    <p>Have questions about your event or reservation? Contact Villa Salud and we'll be happy to assist you.</p>
                </div>
                <a class="landing-outline-link landing-outline-link--light" href="#home">Back to Home</a>
            </div>
        </section>
    </main>
    <x-site-footer class="landing-footer">
        <a class="landing-admin-link" href="{{ route('admin.login') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true" focusable="false">
                <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
            </svg>
            <span>Admin Login</span>
        </a>
    </x-site-footer>

    <x-ui.notification-modal />
</body>
</html>
