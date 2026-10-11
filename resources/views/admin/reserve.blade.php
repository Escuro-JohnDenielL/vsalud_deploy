@extends('layouts.admin')

@section('title', 'Make Reservation')

@push('styles')
    @vite('resources/css/admin/reserve.css')
@endpush

@section('content')
    <div class="vs-page vs-admin-reservation">
    <x-ui.page-header title="Reservations" subtitle="Create a new reservation and manage date availability." />
    <div class="container vs-admin-reservation__layout">
        <div class="reservation-container">
            <h2>Let's bring your vision to life. Just fill out the form.</h2>
            @php
                $reservationForm = \App\Models\Form::with('activeFields')
                    ->where('slug', 'reservation')
                    ->where('is_published', true)
                    ->first();
            @endphp

            @if($reservationForm)
                <x-dynamic-form
                    :form="$reservationForm"
                    action="{{ route('admin.reserve.store') }}"
                    :errors="$errors"
                    submitLabel="Submit Inquiry"
                />
                <input type="hidden" name="created_by_type" value="admin">
            @else
                <p class="vs-admin-reservation__notice" role="status">The reservation form is currently unavailable. Please contact the administrator.</p>
            @endif
        </div>

        {{-- data-today is computed for Manila on this page only (the app
             timezone stays UTC) so past-date marking never depends on the
             visitor's browser clock. --}}
        <div class="calendar-container"
             data-today="{{ \Carbon\Carbon::now('Asia/Manila')->toDateString() }}"
             data-max-slots="{{ (int) config('availability.max_inquiries', 4) }}">
            <div class="calendar-header">
                <button type="button" id="prevMonth">◀</button>
                <span id="month-year"></span>
                <button type="button" id="nextMonth">▶</button>
            </div>
            <div id="calendar"></div>

            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="legend-box legend-green vs-admin-reservation__legend-box vs-admin-reservation__legend-box--success vs-calendar__legend-swatch vs-calendar-day--success"></div> 0/4 to 1/4 Available
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-yellow vs-admin-reservation__legend-box vs-admin-reservation__legend-box--warning vs-calendar__legend-swatch vs-calendar-day--half"></div> 2/4 Limited
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-orange vs-admin-reservation__legend-box vs-admin-reservation__legend-box--warning vs-calendar__legend-swatch vs-calendar-day--nearly"></div> 3/4 Nearly Full
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-red vs-admin-reservation__legend-box vs-admin-reservation__legend-box--danger vs-calendar__legend-swatch vs-calendar-day--danger"></div> 4/4 Full
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-gray vs-admin-reservation__legend-box vs-admin-reservation__legend-box--neutral vs-calendar__legend-swatch vs-calendar-day--neutral"></div> Closed
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Status Selection Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content modal-sm">
            <h3>Select Status</h3>
            <select id="statusSelect">
                <option value="">No Status</option>
                <option value="Available">Available</option>
                <option value="Half">Half Full</option>
                <option value="Nearly">Nearly Full</option>
                <option value="Full">Full</option>
                <option value="Closed">Closed</option>
            </select>
            <div class="modal-footer">
                <button type="button" id="undoStatus" class="admin-btn admin-btn-danger">Undo Status</button>
                <button type="button" id="closeModal" class="admin-btn admin-btn-ghost">Cancel</button>
                <button type="button" id="saveStatus" class="admin-btn admin-btn-primary">Save</button>
            </div>
        </div>
    </div>

    <div id="dateUnavailableModal" class="modal">
        <div class="modal-content modal-sm">
            <span class="close-btn" id="closeUnavailableModal">&times;</span>
            <h3>Date Not Available</h3>
            <p>Sorry, the selected date is already full or closed. Please choose another available date.</p>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/reserve.js')
@endpush
