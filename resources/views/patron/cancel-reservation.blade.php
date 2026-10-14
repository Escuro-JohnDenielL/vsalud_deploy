@extends('layouts.patron')

@section('title', 'Cancel Reservation')

@push('styles')
    @vite('resources/css/patron/cancel-reservation.css')
@endpush

@section('content')
    <div class="cancel-reservation-container">
        <h2>Request Cancellation</h2>
        <p>Enter your reservation code and the email address used to book it, so we can look up your reservation and submit a cancellation request.</p>

        {{-- Step 1: Lookup --}}
        <form id="lookupForm">
            @csrf
            <div class="form-group">
                <label for="tracking_code">Reservation Code:</label>
                <input type="text" id="tracking_code" name="tracking_code" placeholder="e.g. VS-ABCDEF" required />
            </div>
            <div class="form-group">
                <label for="lookup_email">Email Address:</label>
                <input type="email" id="lookup_email" name="email" placeholder="The email used to book" required />
            </div>
            <button type="submit" class="btn-lookup">Look Up Reservation</button>
        </form>

        {{-- Messages (appear above details when a reservation is found) --}}
        <div id="messageContainer"></div>

        {{-- Step 2: Reservation Details + Cancellation Form (hidden initially) --}}
        <div id="reservationDetails" style="display: none;">
            <div class="details-card">
                <h3>Reservation Details</h3>
                <div id="detailsContent"></div>
            </div>

            <div class="cancel-form-card">
                <form id="cancelForm">
                    @csrf

                    <div class="form-group">
                        <label for="reason">Reason for Cancellation (optional):</label>
                        <textarea id="reason" name="reason" rows="4" placeholder="Tell us why you'd like to cancel..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit-cancel">Submit Cancellation Request</button>
                </form>
            </div>
        </div>
    </div>

    <div id="cancelConfirm" class="cancel-confirm" role="dialog" aria-modal="true" aria-labelledby="cancelConfirmTitle" hidden>
        <div class="cancel-confirm__dialog">
            <h3 id="cancelConfirmTitle">Send cancellation request?</h3>
            <p>This sends a request for admin review. Your reservation is not cancelled until it is approved.</p>
            <div class="cancel-confirm__actions">
                <button type="button" class="cancel-confirm__cancel" id="cancelConfirmDismiss">Keep reservation</button>
                <button type="button" class="cancel-confirm__confirm" id="cancelConfirmSubmit">Send request</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/patron/cancel-reservation.js')
@endpush
