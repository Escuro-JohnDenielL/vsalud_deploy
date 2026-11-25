@extends('layouts.patron')

@section('title', 'Data Privacy Notice')

@push('styles')
    @vite('resources/css/patron/privacy.css')
@endpush

@section('content')
    @php
        $contactEmail = config('villasalud.contact.email');
        $contactPhone = config('villasalud.contact.phone');
    @endphp

    <div class="vs-page privacy-page">
        <x-ui.page-header
            title="Data Privacy Notice"
            subtitle="Republic Act No. 10173 · Data Privacy Act of 2012" />

        {{-- Informational page. The notice is shown next to, and linked from, the
             forms that collect personal data; browsing pages stay unobstructed. --}}
        <article class="privacy-document">
            <p>
                Welcome to <strong>Villa Salud Catering</strong>. We value your privacy and are
                committed to protecting your personal information in accordance with the
                <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> and its
                Implementing Rules and Regulations.
            </p>

            <h2>When this notice applies</h2>
            <p>
                This notice covers the personal information you enter in the forms of this system:
                making a reservation, joining a waitlist, submitting a payment receipt, requesting a
                cancellation, sending feedback, and looking up a booking. Simply browsing packages,
                venue photos, rates, and guidelines does not collect personal information.
            </p>

            <h2>What we collect and why</h2>
            <p>
                We collect the personal information you provide through this system &mdash; such as
                your name, contact number, email address, event and reservation details, and payment
                information &mdash; for the legitimate purpose of processing your inquiries, bookings,
                reservations, and other related services.
            </p>

            <h2>How we protect your data</h2>
            <p>
                Your information is stored securely and is accessed only by authorized personnel. We
                do not sell or share your personal information with third parties without your
                consent, except when required or permitted by law.
            </p>

            <h2>How long we keep it</h2>
            <p>
                Reservation, payment, and feedback records are retained for as long as needed to
                service your booking and to meet the company's accounting and legal obligations. After
                that period they are archived or securely disposed of.
            </p>

            <h2>Your rights</h2>
            <p>Under the Data Privacy Act, you have the right to:</p>
            <ul>
                <li>be informed of how your personal data is collected and used;</li>
                <li>access the personal data we hold about you;</li>
                <li>object to its processing or withdraw your consent;</li>
                <li>correct inaccurate or incomplete personal data;</li>
                <li>request the deletion or blocking of your personal data; and</li>
                <li>file a complaint with the National Privacy Commission.</li>
            </ul>
            <p>
                For any privacy-related concern, you may contact our office or data protection officer.
            </p>

            <div class="privacy-document__contact">
                <h2 class="privacy-document__contact-title">Contact us</h2>
                <p class="privacy-document__meta">
                    Villa Salud Catering Services
                    @if ($contactEmail)
                        &middot; <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    @endif
                    @if ($contactPhone)
                        &middot; {{ $contactPhone }}
                    @endif
                </p>
                <p class="privacy-document__meta">
                    Full office details are listed in our
                    <a href="{{ route('patron.guidelines') }}">Guidelines</a> page.
                </p>
            </div>
        </article>
    </div>
@endsection
