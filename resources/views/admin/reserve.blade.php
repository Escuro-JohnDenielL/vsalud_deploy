@extends('layouts.admin')

@section('title', 'Make Reservation')

@push('styles')
    @vite('resources/css/admin/reserve.css')
@endpush

@section('content')
    <div class="container">
        <div class="reservation-container">
            <h2>Let's bring your vision to life—just fill out the form.</h2>
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
                <p style="color:#dc3545;">The reservation form is currently unavailable. Please contact the administrator.</p>
            @endif
        </div>

        <div class="calendar-container">
            <div class="calendar-header">
                <button type="button" id="prevMonth">◀</button>
                <span id="month-year"></span>
                <button type="button" id="nextMonth">▶</button>
            </div>
            <div id="calendar"></div>

            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="legend-box legend-green"></div> Available (1–2 left)
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-yellow"></div> Half Full (2/4)
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-orange"></div> Nearly Full (3/4)
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-red"></div> Full (4/4)
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-gray"></div> Closed
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Status Selection Modal -->
    <div id="statusModal" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <h3>Select Status</h3>
            <select id="statusSelect">
                <option value="">No Status</option>
                <option value="Available">Available</option>
                <option value="Half">Half Full</option>
                <option value="Nearly">Nearly Full</option>
                <option value="Full">Full</option>
                <option value="Closed">Closed</option>

            </select>
            <div class="modal-buttons">
                <button type="button" id="saveStatus">Save</button>
                <button type="button" id="closeModal">Cancel</button>
                <button type="button" id="undoStatus">Undo Status</button>
            </div>
        </div>
    </div>

    <div id="dateUnavailableModal" class="modal1" style="display: none;">
        <div class="modal-content1">
            <span class="close-modal1" id="closeUnavailableModal">&times;</span>
            <h3>Date Not Available</h3>
            <p>Sorry, the selected date is already full or closed. Please choose another available date.</p>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/reserve.js')
@endpush
