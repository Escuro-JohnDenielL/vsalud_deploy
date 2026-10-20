@extends('layouts.patron')

@section('title', 'Manual Reservation')

@push('styles')
    @vite(['resources/css/patron/mreserve.css', 'resources/css/venue-panel.css'])
@endpush

@section('content')
    @php
        // Daily event cap drives the calendar scale and the legend
        // (config/availability.php).
        $dailyCap = \App\Support\AvailabilityScale::cap();
    @endphp

    <!-- Agreement Modal -->
    <div id="agreementModal" class="modal agreement-modal">
        <div class="guidelines-content" id="agreementContent" tabindex="0" role="region" aria-label="Data Privacy Notice and Reservation Guidelines">
            <h3>Data Privacy Notice and Reservation Guidelines</h3>

            @if (session('consent_error'))
                <p class="agreement-consent-error" role="alert">{{ session('consent_error') }}</p>
            @endif

            <section class="agreement-privacy">
                <h3>Data Privacy Act Summary</h3>
                <p>We collect your name, email, phone number, event details and payment receipts to process your booking and payments. Villa Salud administrators can see this information. It is kept as stated in the full notice.</p>
                <p><a href="{{ route('patron.home') }}#privacy" target="_blank" rel="noopener noreferrer">Read the full Data Privacy Notice</a></p>
            </section>

            <section>
                <h3>Reservation & Payment Policy</h3>
                <p><strong>Reservation Fee:</strong> Php 25,000.00 (Venue & Catering) / Php 5,000.00 (Swimming Pool).
                    Non-refundable, non-consumable, non-transferable.</p>
                <p><strong>Payment Terms:</strong> 50% due one month prior, full payment due two weeks before event. Cash or
                    approved credit cards only. Bank deposits must be advised via fax/email.</p>
                <p><strong>Cancellation Charges:</strong></p>
                <ul>
                    <li>25%: 8 months or more before the event</li>
                    <li>50%: 4 to 7 months before the event</li>
                    <li>75%: 2 to 3 months before the event</li>
                    <li>100%: 60 days or less before the event</li>
                </ul>
            </section>

            <section>
                <h3>Catering Coverage & Policies</h3>
                <ul>
                    <li>Excess guests will be charged accordingly.</li>
                    <li>Up to 10% reduction in guest count allowed 2 weeks before the event.</li>
                    <li>No refund for unconsumed catering items.</li>
                    <li>Clients are liable for toll fees and external catering incidentals.</li>
                    <li>Outside food/drinks are prohibited unless pre-approved in writing.</li>
                    <li>Villa Salud is not liable for spoiled food due to client delay in serving.</li>
                    <li>All food must be consumed immediately; Villa Salud is not liable for takeout food-related illness.
                    </li>
                </ul>
            </section>

            <section>
                <h3>Function Set-up</h3>
                <ul>
                    <li>Villa Salud controls layout and equipment setup.</li>
                    <li>No attachment to walls, ceilings, or floors is allowed.</li>
                    <li>Damages by client/guests will be charged based on market value.</li>
                    <li>Client must secure permits for offsite catering setups.</li>
                </ul>
            </section>

            <section>
                <h3>Safety and Security</h3>
                <ul>
                    <li>No flammable or explosive materials allowed.</li>
                    <li>No fireworks allowed during events.</li>
                    <li>Villa Salud is not responsible for lost or damaged personal items.</li>
                    <li>Client assumes liability for any injury or damages caused.</li>
                </ul>
            </section>

            <section>
                <h3>Swimming Pool Rental Terms</h3>
                <p><strong>Rates:</strong> AM (7:00 AM – 5:00 PM): Php 8,500 | PM (7:00 PM – 5:00 AM): Php 9,500</p>
                <p><strong>Standard Amenities:</strong> 50 monobloc chairs, 6 tables, buffet table, griller</p>
                <p><strong>Extra Charges:</strong></p>
                <ul>
                    <li>Videoke: Php 1,000</li>
                    <li>Mobile System: Php 8,000 (4 hrs)</li>
                    <li>Room: Php 2,500 (12 hrs)</li>
                    <li>Photobooth: Php 5,000 (3 hrs)</li>
                    <li>Mobile bar: Php 5,000 and up</li>
                </ul>
                <p><strong>Important Notes:</strong></p>
                <ul>
                    <li>No lifeguard on duty – swim at your own risk.</li>
                    <li>Children must be accompanied by adults.</li>
                    <li>Clients must follow capacity limits and LGU guidelines.</li>
                    <li>Overtime, excess guests, and damages incur additional charges.</li>
                </ul>
            </section>

            <section>
                <h3>Guidelines for Outside Caterers & Suppliers</h3>
                <ul>
                    <li>No removal or rearranging of Villa Salud's equipment without permission.</li>
                    <li>Cooking is not allowed. Food warming only.</li>
                    <li>No dishwashing in premises. Clean-up required.</li>
                    <li>Sufficient garbage bags must be provided by suppliers.</li>
                    <li>Staff must wear proper uniforms; no casual clothing allowed.</li>
                    <li>No bathing for staff in venue.</li>
                    <li>Ingress: AM – 6:00 AM | PM – 6:00 PM</li>
                    <li>Vehicles cannot park inside Villa Salud. Coordinate for parking.</li>
                    <li>Vicinity lights/fans allowed 30 mins before the event only.</li>
                    <li>All equipment must be cleared post-event with gate pass.</li>
                </ul>
            </section>

            <section>
                <h3>Force Majeure Clause</h3>
                <p>Villa Salud is not liable for performance failures caused by uncontrollable events (e.g., natural
                    disasters, war, governmental regulations). In such cases, Villa Salud reserves the right to provide
                    alternative accommodations or menus as fulfillment of the contract.</p>
            </section>

            <div style="text-align: center; margin-top: 30px;">
                <button id="agreeButton" disabled>I Agree to the Terms</button>
                <a class="agreement-decline" href="{{ url('/') }}">Decline</a>
            </div>
        </div>
    </div>

    <button type="button" id="reopenAgreement" class="agreement-reopen" hidden>
        Read the notice and agree to continue
    </button>

    <script>
        window.guestAgreementAccepted = @json($agreementAccepted ?? false);
        window.agreeUrl = @json(route('patron.agree'));
        window.vsBookingTimeSlots = @json(config('booking.time_slots'));
    </script>

    <div class="page-wrapper vs-patron-reservation">
        <div class="container vs-patron-reservation__layout">
            <div class="reservation-container">
                <h2>Let's bring your vision to life. Fill out the form below.</h2>

                @php
                    $reservationForm = \App\Models\Form::with('activeFields')
                        ->where('slug', 'reservation')
                        ->where('is_published', true)
                        ->first();
                @endphp

                @if($reservationForm)
                    <x-dynamic-form
                        :form="$reservationForm"
                        action="{{ route('patron.mreserve.submit') }}"
                        :errors="$errors"
                        submitLabel="Submit Inquiry"
                    />
                @else
                    <p class="vs-inline-danger">The reservation form is currently unavailable. Please contact the administrator.</p>
                @endif
            </div>

            {{-- data-today is computed for Manila on this page only (the app
                 timezone stays UTC) so past-date handling never depends on the
                 visitor's browser clock. --}}
            <div class="calendar-container vs-calendar"
                 data-today="{{ \Carbon\Carbon::now('Asia/Manila')->toDateString() }}"
                 data-max-events="{{ $dailyCap }}">
                <div class="calendar-header">
                    <button id="prevMonth" class="vs-calendar-btn">◀</button>
                    <span id="month-year"></span>
                    <button id="nextMonth" class="vs-calendar-btn">▶</button>
                </div>
                <div id="calendar"></div>

                <div class="calendar-legend">
                    @foreach (\App\Support\AvailabilityScale::legendRows($dailyCap) as $legendRow)
                        <p><span class="vs-calendar__legend-swatch {{ $legendRow['className'] }}"></span> {{ $legendRow['text'] }}</p>
                    @endforeach
                </div>

                {{-- Venue availability for the picked date. Shared markup with the
                     admin page; filled by resources/js/venue-availability.js. --}}
                <x-venue-panel :endpoint="route('venue.availability')" />
            </div>
        </div>
    </div>

    <div id="dateUnavailableModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" id="closeUnavailableModal">&times;</span>
            <h3 id="unavailableTitle">Date Not Available</h3>
            <p id="unavailableMessage">Sorry, the selected date is already full or closed. Please choose another available date.</p>
            <div id="waitlistOption" class="waitlist-option" style="display: none;">
                <p class="vs-inline-note waitlist-option-copy">
                    Want to be notified if a slot opens up? Join the waitlist!
                </p>
                <div class="form-group" style="margin-bottom: 10px;">
                    <input type="text" id="waitlistName" placeholder="Your Name" style="width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; box-sizing: border-box;">
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <input type="email" id="waitlistEmail" placeholder="Your Email" style="width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; box-sizing: border-box;">
                </div>
                <div id="waitlistMessage" style="font-size: 13px; margin-bottom: 10px; display: none;"></div>
                <button id="joinWaitlistBtn" class="vs-inline-waitlist-button">
                    Join Waitlist
                </button>
                <x-privacy-note />
            </div>
        </div>
    </div>

    <div id="waitlistSuccessModal" class="modal" style="display: none;">
        <div class="modal-content vs-inline-centered">
            <span class="close-modal" id="closeWaitlistSuccessModal">&times;</span>
            <h3 class="vs-inline-success">You're on the Waitlist!</h3>
            <p class="vs-inline-modal-copy">We'll email you if a slot opens up for this date. You'll have 24 hours to claim it.</p>
            <button onclick="window.vsModalCloseAfterFade(document.getElementById('waitlistSuccessModal'), () => { document.getElementById('waitlistSuccessModal').style.display = 'none'; })" class="vs-inline-success-button">
                Got it!
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/patron/mreserve.js')
@endpush
