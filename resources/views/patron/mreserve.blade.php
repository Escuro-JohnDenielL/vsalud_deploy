@extends('layouts.patron')

@section('title', 'Manual Reservation')

@push('styles')
    @vite('resources/css/patron/mreserve.css')
@endpush

@section('content')
    <!-- Agreement Modal -->
    <div id="agreementModal" class="modal agreement-modal">
        <div class="guidelines-content" id="agreementContent">
            <h3>Reservation Guidelines</h3>

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
            </div>
        </div>
    </div>

    <script>
        window.guestAgreementAccepted = @json($agreementAccepted ?? false);
        window.guestConsentAgreeUrl = @json(route('patron.guest.consent.agree'));
    </script>

    <div class="page-wrapper">
        <div class="container">
            <div class="reservation-container">
                <h2>Let's bring your vision to life—just fill out the form.</h2>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

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
                    <p style="color:#dc3545;">The reservation form is currently unavailable. Please contact the administrator.</p>
                @endif
            </div>

            <div class="calendar-container">
                <div class="calendar-header">
                    <button id="prevMonth">◀</button>
                    <span id="month-year"></span>
                    <button id="nextMonth">▶</button>
                </div>
                <div id="calendar"></div>
                <div id="calendar" class="calendar-grid"></div>

                <div class="calendar-legend">
                    <p><span class="legend-box Available"></span> Available (1-2 left)</p>
                    <p><span class="legend-box Half"></span> Half Full (2/4)</p>
                    <p><span class="legend-box Nearly"></span> Nearly Full (3/4)</p>
                    <p><span class="legend-box Full"></span> Full (4/4)</p>
                    <p><span class="legend-box Closed"></span> Closed</p>
                </div>
            </div>
        </div>
    </div>

    <div id="dateUnavailableModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" id="closeUnavailableModal">&times;</span>
            <h3>Date Not Available</h3>
            <p>Sorry, the selected date is already full or closed. Please choose another available date.</p>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/patron/mreserve.js')
@endpush
