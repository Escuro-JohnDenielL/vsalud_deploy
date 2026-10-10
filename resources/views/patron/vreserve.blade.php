@extends('layouts.patron')

@section('title', 'View Reservation')

@push('styles')
    @vite('resources/css/patron/vreserve.css')
@endpush

@section('content')
    <div class="vs-reservation-page">
      <div class="reservation-container vs-card">
        <h2>Reservation Form</h2>
        <p>To view your reservation form, please enter the Reservation Code that we sent to your provided email, together
            with the email address itself, then click "View Details."</p>
        <form id="reservationForm">
            @csrf
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input class="vs-form-control" type="email" id="email" name="email" required />
            </div>
            <div class="form-group">
                <label for="reservation_code">Reservation Code:</label>
                <input class="vs-form-control" type="text" id="reservation_code" name="reservation_code" required />
            </div>

            <button type="submit" class="view-details vs-btn vs-btn--primary">View Details</button>
        </form>

        <div id="reservationModal" class="vs-modal modal" style="display: none;">
            <div class="modal-content vs-modal__dialog">
                <button type="button" class="vs-modal__close close-btn" id="closeReservationModal" aria-label="Close reservation details">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
                <h3>Reservation Details</h3>
                <div class="vs-reservation-scroll" role="region" tabindex="0" aria-label="Reservation details">
                    <div id="reservationDetails"></div>
                </div>
            </div>
        </div>
      </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/patron/view-reservation.js')
@endpush
