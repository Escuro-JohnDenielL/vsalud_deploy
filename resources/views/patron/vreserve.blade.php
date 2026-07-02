@extends('layouts.patron')

@section('title', 'View Reservation')

@push('styles')
    @vite('resources/css/patron/vreserve.css')
@endpush

@section('content')
    <div class="reservation-container">
        <h2>Reservation Form</h2>
        <p>To view your reservation form, please enter your Reservation Code that we sent to your provided email, then click
            "View Details."</p>
        <form id="reservationForm">
            @csrf
            <div class="form-group">
                <label for="reservation_code">Reservation Code:</label>
                <input type="text" id="reservation_code" name="reservation_code" required />
            </div>

            <button type="submit" class="view-details">View Details</button>
        </form>

        <div id="reservationModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close-btn" id="closeReservationModal">&times;</span>
                <h3>Reservation Details</h3>
                <div id="reservationDetails"></div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/patron/view-reservation.js')
@endpush
