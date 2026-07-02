@extends('layouts.admin')

@section('title', 'Make Reservation')

@push('styles')
    @vite('resources/css/admin/reserve.css')
@endpush

@section('content')
    <div class="container">
        <div class="reservation-container">
            <h2>Let's bring your vision to life—just fill out the form.</h2>
            <form action="{{ route('admin.reserve.store') }}" method="POST">
                @csrf
                <input type="hidden" name="created_by_type" value="admin">

                <div class="form-group">
                    <label for="name">Name:<span>*</span></label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:<span>*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Number:<span>*</span></label>
                    <input type="tel" id="contact" name="contact_number" required>
                </div>

                <div class="form-group">
                    <label for="date">Date:<span>*</span></label>
                    <input type="date" id="date" name="date" required>
                </div>

                <div class="form-group">
                    <label for="period">Select Period:<span>*</span></label>
                    <select id="period" name="period" required>
                        <option value="" selected disabled>Choose AM or PM</option>
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>

                <div class="form-group" id="timeSlotWrapper" style="display: none;">
                    <label for="time_slot">Select Time Slot:<span>*</span></label>
                    <select id="time_slot" name="time" required>
                        <option value="">Select a time slot</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="venue">Venue:<span>*</span></label>
                    <select id="venue" name="venue" required>
                        <option value="" disabled selected>Choose a venue</option>
                        <option value="Villa I">Villa I</option>
                        <option value="Villa II">Villa II</option>
                        <option value="Elizabeth Hall">Elizabeth Hall</option>
                        <option value="Private Pool">Private Pool</option>
                        <option value="Others">Others</option>
                    </select>
                    <input type="text" id="otherVenue" name="other_venue" style="display: none;"
                        placeholder="Please specify">
                </div>

                <div class="form-group">
                    <label for="event_type">Event Type:<span>*</span></label>
                    <select id="event_type" name="event_type" required>
                        <option value="" disabled selected>Choose event type</option>
                        <option value="Baptismal Package">Baptismal Package</option>
                        <option value="Birthday Package">Birthday Package</option>
                        <option value="Debut Package">Debut Package</option>
                        <option value="Kiddie Package">Kiddie Package</option>
                        <option value="Wedding Package">Wedding Package</option>
                        <option value="Standard Package">Standard Package</option>
                        <option value="Others">Others</option>
                    </select>
                    <input type="text" id="otherEventType" name="other_event_type" style="display: none;"
                        placeholder="Please specify">
                </div>

                <div class="form-group">
                    <label for="theme_motif">Theme/Motif:<span>*</span></label>
                    <select id="theme_motif" name="theme_motif" required>
                        <option value="" disabled selected>Choose theme/motif</option>
                        <option value="Floral">Floral</option>
                        <option value="Rustic">Rustic</option>
                        <option value="Elegant">Elegant</option>
                        <option value="Beach">Beach</option>
                        <option value="Modern">Modern</option>
                        <option value="Others">Others</option>
                    </select>
                    <input type="text" id="otherThemeMotif" name="other_theme_motif" style="display: none;"
                        placeholder="Please specify">
                </div>

                <div class="form-group">
                    <label for="message">Other Request:<span>*</span></label>
                    <textarea id="message" name="message" required placeholder="Please describe your specific requirements..."></textarea>
                </div>

                <div class="form-group">
                    <button type="submit">Submit Inquiry</button>
                </div>
            </form>
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
