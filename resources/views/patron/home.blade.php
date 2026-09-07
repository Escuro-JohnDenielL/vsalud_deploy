@extends('layouts.patron')

@section('title', 'Patron Home')

@push('styles')
    @vite('resources/css/patron/home.css')
@endpush

@section('content')
    <div class="banner-container">
        <div class="banner">
            Welcome to Villa Salud Catering! Your go-to destination for elegant events and hassle-free reservations.
        </div>
    </div>

    <div class="packages">
        @foreach ($packages as $package)
            <x-package-card
                :name="$package->name"
                :description="$package->description"
                :price="$package->price"
                :imagePath="$package->image_path"
            />
        @endforeach

        {{-- Optionally, fallback if no packages exist --}}
        @if($packages->isEmpty())
            <p style="text-align: center;">No packages available at the moment. Please check back later.</p>
        @endif
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
@endsection

@push('scripts')
    @vite('resources/js/patron/home.js')
@endpush
