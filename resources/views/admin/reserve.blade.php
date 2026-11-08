@extends('layouts.admin')

@section('title', 'Reservation Calendar')

@push('styles')
    @vite(['resources/css/admin/reserve.css', 'resources/css/venue-panel.css'])
@endpush

@section('content')
    @php
        // Daily event cap drives the calendar scale and the legend
        // (config/availability.php).
        $dailyCap = \App\Support\AvailabilityScale::cap();
    @endphp

    <div class="vs-page vs-admin-reservation">
    <x-ui.page-header title="Reservation Calendar" subtitle="Create a new reservation and manage date availability.">
        <x-slot:action>
            <x-ui.page-info id="adminReserveInfo" title="How to create a reservation">
                <ol>
                    <li>Click any date on the calendar to open that date's status panel. Full and closed dates can still be opened.</li>
                    <li>Fill out the form and submit it. This creates the patron's booking request, which then appears on Inquiries.</li>
                    <li>If the Date field in the form is set to a full or closed date, a notice appears and the field is cleared.</li>
                    <li>The venue panel shows which venues are still free on that date.</li>
                    <li>The legend shows how full a date is: Available, Limited, Nearly Full, Full, Closed. Only confirmed reservations count.</li>
                    <li>In the status panel, set the date to Available, Half Full, Nearly Full, Full, or Closed. That status overrides the automatic count.</li>
                    <li>Available is refused when the date already holds the daily limit of confirmed reservations.</li>
                    <li>Closing a date that still has bookings needs a reason and a confirmation of the booking count. Closing does not cancel those bookings.</li>
                    <li>Select No Status and save to remove the override, so the date returns to the automatic count.</li>
                </ol>
            </x-ui.page-info>
        </x-slot:action>
    </x-ui.page-header>
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
                    :venues="$venues"
                    :show-venue-price-estimate="true"
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
             data-max-events="{{ $dailyCap }}">
            <div class="calendar-header">
                <button type="button" id="prevMonth">◀</button>
                <span id="month-year"></span>
                <button type="button" id="nextMonth">▶</button>
            </div>
            <div id="calendar"></div>

            @php
                // Legend wording comes from config/availability.php; only the
                // admin panel's box colours are local to this page.
                $legendRows = collect(\App\Support\AvailabilityScale::legendRows($dailyCap))->keyBy('state');
                $legendBoxExtras = [
                    'Available' => 'legend-green vs-admin-reservation__legend-box vs-admin-reservation__legend-box--success',
                    'Half' => 'legend-yellow vs-admin-reservation__legend-box vs-admin-reservation__legend-box--warning',
                    'Nearly' => 'legend-orange vs-admin-reservation__legend-box vs-admin-reservation__legend-box--warning',
                    'Full' => 'legend-red vs-admin-reservation__legend-box vs-admin-reservation__legend-box--danger',
                    'Closed' => 'legend-gray vs-admin-reservation__legend-box vs-admin-reservation__legend-box--neutral',
                ];
            @endphp
            <div class="calendar-legend">
                @foreach ($legendRows as $state => $legendRow)
                    <div class="legend-item">
                        <div class="legend-box {{ $legendBoxExtras[$state] ?? '' }} vs-calendar__legend-swatch {{ $legendRow['className'] }}"></div> {{ $legendRow['text'] }}
                    </div>
                @endforeach
            </div>

            {{-- Venue availability for the picked date. Shared markup with the
                 patron page; filled by resources/js/venue-availability.js. --}}
            <x-venue-panel :endpoint="route('venue.availability')" />
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

            {{-- Shown only when the chosen status is refused (422) or when
                 closing a date that still holds reservations. --}}
            <p id="statusModalNotice" class="vs-admin-reservation__notice" role="alert" hidden></p>

            {{-- Reason is required only when closing a date that still has
                 reservations; it is stored in the audit log. --}}
            <div class="form-group" id="statusReasonGroup" hidden>
                <label for="statusReason">Reason for closing this date</label>
                <textarea id="statusReason" rows="3" maxlength="255" placeholder="Recorded in the audit log"></textarea>
            </div>

            {{-- Explicit confirmation, only for closing a date that still
                 holds reservations. Closing never cancels them. --}}
            <div class="form-group status-modal__confirm" id="statusConfirmGroup" hidden>
                <label for="statusConfirmCheck">
                    <input type="checkbox" id="statusConfirmCheck" />
                    <span id="statusConfirmLabel"></span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" id="closeModal" class="admin-btn admin-btn-ghost">Cancel</button>
                <button type="button" id="saveStatus" class="admin-btn admin-btn-primary">Save</button>
            </div>
        </div>
    </div>

    <div id="dateUnavailableModal" class="modal">
        <div class="modal-content modal-sm">
            <button type="button" class="close-btn" id="closeUnavailableModal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h3>Date Not Available</h3>
            <p>Sorry, the selected date is already full or closed. Please choose another available date.</p>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.vsBookingTimeSlots = @json(config('booking.time_slots'));
    </script>
    @vite('resources/js/admin/reserve.js')
@endpush
