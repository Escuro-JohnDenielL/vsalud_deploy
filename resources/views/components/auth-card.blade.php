{{--
    Shared split card for the admin auth pages (login + MFA challenge).
    Form/content goes in the default slot; the event photo panel is decorative
    and hides below 900px. Styles live in resources/css/login.css.
--}}
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-form-panel">
            <div class="auth-brand">
                <span class="auth-brand__mark" aria-hidden="true">VS</span>
                <span class="auth-brand__name"><small>VILLA</small><strong>SALUD</strong></span>
            </div>

            {{ $slot }}
        </div>

        <div class="auth-media" aria-hidden="true">
            <img class="auth-media__img" src="{{ asset('images/wedding1.jpg') }}" alt="">
            <img class="auth-media__img" src="{{ asset('images/debut1.jpg') }}" alt="">
            <img class="auth-media__img" src="{{ asset('images/baptismal1.jpg') }}" alt="">
            <img class="auth-media__img" src="{{ asset('images/background_picture.jpeg') }}" alt="">
        </div>
    </div>
</div>
