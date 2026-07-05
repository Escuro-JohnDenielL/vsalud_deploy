@extends('layouts.patron')

@section('title', 'Cancel Reservation')

@push('styles')
    @vite('resources/css/patron/cancel-reservation.css')
@endpush

@section('content')
    <div class="cancel-reservation-container">
        <h2>Request Cancellation</h2>
        <p>Enter your reservation code to look up your reservation and submit a cancellation request.</p>

        {{-- Step 1: Lookup --}}
        <form id="lookupForm">
            @csrf
            <div class="form-group">
                <label for="tracking_code">Reservation Code:</label>
                <input type="text" id="tracking_code" name="tracking_code" placeholder="e.g. VS-ABCDEF" required />
            </div>
            <button type="submit" class="btn-lookup">Look Up Reservation</button>
        </form>

        {{-- Step 2: Reservation Details + Cancellation Form (hidden initially) --}}
        <div id="reservationDetails" style="display: none;">
            <h3>Reservation Details</h3>
            <div id="detailsContent"></div>

            <form id="cancelForm" style="margin-top: 20px;">
                @csrf
                <input type="hidden" id="reserve_id" name="reserve_id" />
                <input type="hidden" id="patron_email" name="patron_email" />

                <div class="form-group">
                    <label for="reason">Reason for Cancellation (optional):</label>
                    <textarea id="reason" name="reason" rows="4" placeholder="Tell us why you'd like to cancel..."></textarea>
                </div>

                <button type="submit" class="btn-submit-cancel">Submit Cancellation Request</button>
            </form>
        </div>

        {{-- Messages --}}
        <div id="messageContainer" style="margin-top: 20px;"></div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/patron/cancel-reservation.js')
@endpush
