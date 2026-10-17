@extends('layouts.patron')

@section('title', 'Patron Home')

@push('styles')
    @vite('resources/css/patron/home.css')
@endpush

@section('content')
    <div class="vs-page vs-patron-packages">
        <x-ui.page-header title="Packages" subtitle="Welcome to Villa Salud Catering! Your go-to destination for elegant events and hassle-free reservations." />

    <div class="packages">
        @foreach ($packages as $package)
            <x-package-card
                :name="$package->name"
                :description="$package->description"
                :price="$package->price"
                :imagePath="$package->image_path"
                :inclusions="$package->inclusions ?? []"
                :image2Path="$package->image_2_path"
                :image3Path="$package->image_3_path"
            />
        @endforeach

        {{-- Optionally, fallback if no packages exist --}}
        @if($packages->isEmpty())
            <p class="packages-empty">No packages available at the moment. Please check back later.</p>
        @endif
    </div>
    </div>

    {{-- View Package Modal --}}
    <div id="packageModal" class="pkg-modal" role="dialog" aria-modal="true" aria-labelledby="pkgModalTitle">
        <div class="pkg-modal__dialog">
            <button type="button" class="pkg-modal__close" id="pkgModalClose" aria-label="Close package details">&times;</button>

            <div class="pkg-modal__media">
                <img id="pkgModalMainImage" class="pkg-modal__main-image" src="" alt="Package image">
                <div class="pkg-modal__thumbs" id="pkgModalThumbs"></div>
            </div>

            <div class="pkg-modal__info">
                <h2 class="pkg-modal__title" id="pkgModalTitle"></h2>
                <p class="pkg-modal__desc" id="pkgModalDesc"></p>
                <p class="pkg-modal__price" id="pkgModalPrice"></p>

                <div class="pkg-modal__inclusions">
                    <h3>Package Inclusions</h3>
                    <ul id="pkgModalInclusions"></ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Privacy Act (RA 10173) notice -- auto-opens on every page load --}}
    <div id="privacyModal" class="privacy-overlay" role="dialog" aria-modal="true" aria-labelledby="privacyTitle">
        <div class="privacy-modal">
            <button type="button" class="privacy-modal__close" id="privacyClose" aria-label="Close privacy notice">&times;</button>

            <h2 id="privacyTitle">Data Privacy Notice</h2>
            <p class="privacy-modal__subtitle">Republic Act No. 10173 &middot; Data Privacy Act of 2012</p>

            <div class="privacy-modal__body">
                <p>Welcome to <strong>Villa Salud Catering</strong>. We value your privacy and are committed to protecting your personal information in accordance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> and its Implementing Rules and Regulations.</p>
                <p><strong>What we collect and why:</strong> We collect the personal information you provide through this system &mdash; such as your name, contact number, email address, event and reservation details, and payment information &mdash; for the legitimate purpose of processing your inquiries, bookings, reservations, and other related services.</p>
                <p><strong>How we protect your data:</strong> Your information is stored securely and is accessed only by authorized personnel. We do not sell or share your personal information with third parties without your consent, except when required or permitted by law.</p>
                <p><strong>Your rights:</strong> Under the Data Privacy Act, you have the right to be informed, to access, to object, to correct, and to request the deletion of your personal data. For any privacy-related concern, you may contact our office or data protection officer.</p>
                <p>By clicking <strong>&ldquo;I Agree&rdquo;</strong>, you confirm that you have read and understood this notice and you consent to the collection, use, storage, and processing of your personal information as described above.</p>
            </div>

            <div class="privacy-modal__actions">
                <button type="button" class="privacy-btn" id="privacyAgree">I Agree</button>
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
    @vite('resources/js/patron/home.js')
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
