@extends('layouts.patron')

@section('title', 'Patron Home')

@push('styles')
    @vite('resources/css/patron/home.css')
@endpush

@section('content')
    <div class="vs-page vs-patron-packages">
        <x-ui.page-header title="Packages" subtitle="Welcome to Villa Salud Catering! Your go-to destination for elegant events and hassle-free reservations."></x-ui.page-header>

        {{-- One gallery for the whole page: it holds every villa, and the chips
             inside switch between Villa I, Villa II, and the rest. The trigger
             sits under the description instead of the header action slot. --}}
        @if (! empty($venuePayloads))
            <div class="packages-venue-pics">
                <button type="button"
                        class="venue-gallery-trigger view-venue"
                        id="venueGalleryTrigger"
                        data-venues='@json($venuePayloads)'>
                    View venue pictures
                </button>
            </div>
        @endif

    @if ($packages->count() > 1)
        <p class="packages-swipe-hint">
            <span aria-hidden="true">&larr;</span>
            Swipe right to see more packages
            <span aria-hidden="true">&rarr;</span>
        </p>
    @endif

    <div class="packages">
        @foreach ($packages as $package)
            <x-package-card
                :name="$package->name"
                :description="$package->description"
                :imagePath="$package->image_path"
                :inclusions="$package->inclusions ?? []"
                :venues="$package->venues"
            />
        @endforeach

        {{-- Optionally, fallback if no packages exist --}}
        @if($packages->isEmpty())
            <p class="packages-empty">No packages available at the moment. Please check back later.</p>
        @endif
    </div>
    </div>

    {{-- View Package Modal. A package carries one photo beside its details and venue rates. --}}
    <div id="packageModal" class="pkg-modal pkg-modal--package" role="dialog" aria-modal="true" aria-labelledby="pkgModalTitle">
        <div class="pkg-modal__dialog">
            <button type="button" class="pkg-modal__close close-btn" id="pkgModalClose" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true" focusable="false">
                    <path d="m6 6 12 12M18 6 6 18"/>
                </svg>
            </button>
            <div class="pkg-modal__body">
                <div class="pkg-modal__media">
                    <button type="button" class="pkg-modal__zoom" id="pkgModalZoom" aria-label="Enlarge package photo">
                        <img id="pkgModalMainImage" class="pkg-modal__main-image" src="" alt="Package image">
                    </button>
                </div>

                <div class="pkg-modal__info">
                    <h2 class="pkg-modal__title" id="pkgModalTitle"></h2>
                    <p class="pkg-modal__desc" id="pkgModalDesc"></p>

                    <section class="pkg-modal__inclusions" id="pkgModalInclusionsSection" aria-labelledby="pkgModalInclusionsTitle">
                        <h3 id="pkgModalInclusionsTitle">Package inclusions</h3>
                        <ul id="pkgModalInclusions"></ul>
                    </section>

                    <section class="pkg-modal__pricing" aria-labelledby="pkgModalPricingTitle">
                        <div class="pkg-modal__estimate">
                            <h3 id="pkgModalPricingTitle">Venue rental estimate</h3>
                            <p id="pkgModalPriceRange" class="pkg-modal__pricing-range"></p>
                            <p class="pkg-modal__pricing-disclaimer">Venue rental only. The final quote is confirmed by Villa Salud.</p>
                        </div>
                        <button type="button" class="pkg-modal__rates-trigger" id="pkgModalRatesOpen" aria-haspopup="dialog" aria-controls="pkgVenueRatesModal" hidden>
                            <span>View venue rates</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </button>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div id="pkgVenueRatesModal" class="pkg-modal pkg-modal--rates" role="dialog" aria-modal="true" aria-labelledby="pkgModalVenueHeading" hidden>
        <div class="pkg-modal__dialog pkg-modal__rates-dialog">
            <div class="pkg-modal__rates-header">
                <h2 class="pkg-modal__venue-heading" id="pkgModalVenueHeading">Venue rates</h2>
                <button type="button" class="pkg-modal__rates-close" id="pkgModalRatesClose" aria-label="Close venue rates">&times;</button>
            </div>
            <div class="pkg-modal__venue-picker">
                <div class="pkg-modal__venue-tabs" id="pkgModalVenueTabs" role="tablist" aria-label="Choose a venue"></div>
                <label class="pkg-modal__venue-picker-label" for="pkgModalVenueSelect">Choose a venue</label>
                <select class="pkg-modal__venue-select" id="pkgModalVenueSelect" aria-label="Choose a venue">
                    <option value="">No venues linked</option>
                </select>
                <div class="pkg-modal__rates-panel" id="pkgModalVenueRatesPanel" role="tabpanel" aria-live="polite">
                    <div class="pkg-modal__venue-table-scroll">
                        <table class="pkg-modal__venue-table" id="pkgModalVenueTable" hidden>
                            <thead>
                                <tr>
                                    <th scope="col">Period</th>
                                    <th scope="col">Non-air-conditioned</th>
                                    <th scope="col">Air-conditioned</th>
                                </tr>
                            </thead>
                            <tbody id="pkgModalVenueRates"></tbody>
                        </table>
                    </div>
                    <p class="pkg-modal__venue-rates-empty" id="pkgModalVenueRatesEmpty" hidden></p>
                </div>
            </div>
            <footer class="pkg-modal__rates-footer">
                <button type="button" class="pkg-modal__cta pkg-modal__cta--secondary" id="pkgModalRatesDone">Done</button>
            </footer>
        </div>
    </div>

    {{-- Villa gallery, shared with the Make Reservation page. --}}
    <x-venue-gallery />

    {{-- Enlarged photo. Opens from the main photo in the package dialog. --}}
    <div id="pkgLightbox" class="pkg-lightbox" role="dialog" aria-modal="true" aria-label="Enlarged photo" hidden>
        <div class="pkg-lightbox__dialog">
            <button type="button" class="pkg-lightbox__close" id="pkgLightboxClose">Close</button>
            <img id="pkgLightboxImage" class="pkg-lightbox__image" src="" alt="">
        </div>
    </div>
    <div id="privacyModal" class="privacy-overlay" role="dialog" aria-modal="true" aria-labelledby="privacyTitle" data-decline-url="{{ url('/') }}">
        <div class="privacy-modal" tabindex="-1">
            <h2 id="privacyTitle">Data Privacy Notice</h2>
            <p class="privacy-modal__subtitle">Republic Act No. 10173 &middot; Data Privacy Act of 2012</p>

            <div class="privacy-modal__body" tabindex="0" role="region" aria-label="Data privacy notice text">
                <p>Welcome to <strong>Villa Salud Catering</strong>. We value your privacy and are committed to protecting your personal information in accordance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> and its Implementing Rules and Regulations.</p>
                <section class="privacy-section">
                    <h3>What we collect and why:</h3>
                    <p>We collect the personal information you provide through this system &mdash; such as your name, contact number, email address, event and reservation details, and payment information &mdash; for the legitimate purpose of processing your inquiries, bookings, reservations, and other related services.</p>
                </section>
                <section class="privacy-section">
                    <h3>How we protect your data:</h3>
                    <p>Your information is stored securely and is accessed only by authorized personnel. We do not sell or share your personal information with third parties without your consent, except when required or permitted by law.</p>
                </section>
                <section class="privacy-section">
                    <h3>Your rights:</h3>
                    <p>Under the Data Privacy Act, you have the right to be informed, to access, to object, to correct, and to request the deletion of your personal data. For any privacy-related concern, you may contact our office or data protection officer.</p>
                </section>
                <p>By clicking <strong>&ldquo;I Agree&rdquo;</strong>, you confirm that you have read and understood this notice and you consent to the collection, use, storage, and processing of your personal information as described above.</p>
            </div>

            <div class="privacy-modal__actions">
                <button type="button" class="privacy-btn" id="privacyAgree">I Agree</button>
                <button type="button" class="privacy-decline" id="privacyDecline">Decline</button>
            </div>
        </div>
    </div>

    {{-- Tracking code confirmation — shown once after a patron submits an inquiry.
         The code comes from a one-view session flash, never from the URL. --}}
    @if (session('tracking_code'))
        <div id="trackingCodeModal" class="privacy-overlay" role="dialog" aria-modal="true"
            aria-labelledby="trackingCodeTitle" style="z-index:2100; display:flex;">
            <div class="privacy-modal">
                <h2 id="trackingCodeTitle">Inquiry submitted</h2>
                <p class="privacy-modal__subtitle">Your reservation request has been received.</p>

                <div style="margin:20px 0; padding:16px; border:1px dashed #006b53; border-radius:10px; background:#f5f2df; text-align:center;">
                    <span id="trackingCodeValue"
                        style="font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size:22px; font-weight:700; letter-spacing:2px; color:#004c3a; user-select:all;">{{ session('tracking_code') }}</span>
                </div>

                <p class="privacy-modal__body-text" style="text-align:center;">
                    Save this code. You need it with your email to track, pay or cancel.
                </p>

                <div class="privacy-modal__actions">
                    <button type="button" class="privacy-btn" id="copyTrackingCode">Copy code</button>
                    <button type="button" class="privacy-btn" id="closeTrackingCode"
                        style="background:#ffffff; color:#006b53; border:1px solid #006b53;">Done</button>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @vite(['resources/js/patron/home.js', 'resources/js/patron/venue-gallery.js'])
    @if (session('tracking_code'))
        <script>
            (function () {
                var modal = document.getElementById('trackingCodeModal');
                var copyBtn = document.getElementById('copyTrackingCode');
                var doneBtn = document.getElementById('closeTrackingCode');
                var value = document.getElementById('trackingCodeValue');
                if (!modal || !copyBtn || !doneBtn || !value) return;

                function close() {
                    modal.style.display = 'none';
                }

                function copyCode() {
                    var code = value.textContent.trim();
                    var done = function () {
                        copyBtn.textContent = 'Copied';
                        setTimeout(function () { copyBtn.textContent = 'Copy code'; }, 2000);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(code).then(done).catch(fallback);
                    } else {
                        fallback();
                    }

                    function fallback() {
                        var field = document.createElement('textarea');
                        field.value = code;
                        field.setAttribute('readonly', '');
                        field.style.position = 'fixed';
                        field.style.opacity = '0';
                        document.body.appendChild(field);
                        field.select();
                        try { document.execCommand('copy'); done(); } catch (e) {}
                        document.body.removeChild(field);
                    }
                }

                copyBtn.addEventListener('click', copyCode);
                doneBtn.addEventListener('click', close);
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) close();
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && modal.style.display !== 'none') close();
                });
            })();
        </script>
    @endif
@endpush
