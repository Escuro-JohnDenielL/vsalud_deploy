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

                <form method="POST" action="{{ route('patron.mreserve.submit') }}">
                    @csrf

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
                    <button id="prevMonth">◀</button>
                    <span id="month-year"></span>
                    <button id="nextMonth">▶</button>
                </div>
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
    {{-- Preload calendar availability data (eliminates a round-trip API call) --}}
    @php
        $availData = \App\Models\Inquiry::selectRaw('date, COUNT(*) as total')
            ->groupBy('date')
            ->get()
            ->mapWithKeys(fn($i) => [$i->date => match(true) {
                $i->total >= 4 => 'Full',
                $i->total === 3 => 'Nearly',
                $i->total === 2 => 'Half',
                default => 'Available',
            }]);
    @endphp
    <script>
        window.__calendarAvailability = @json($availData);
    </script>

    {{-- Inline app logic (calendar + form behaviors, no module script wait needed) --}}
    <script>
        (function() {
            // ── Calendar ──
            var data = window.__calendarAvailability || {};
            var cal = document.getElementById('calendar');
            var mY = document.getElementById('month-year');
            var now = new Date();
            var month = now.getMonth();
            var year = now.getFullYear();

            function renderCal() {
                if (!cal || !mY) return;
                mY.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(new Date(year, month, 1));
                var fd = new Date(year, month, 1).getDay();
                var ld = new Date(year, month + 1, 0).getDate();
                cal.innerHTML = '';
                ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function(d) {
                    var h = document.createElement('div');
                    h.className = 'calendar-day day-header';
                    h.textContent = d;
                    cal.appendChild(h);
                });
                for (var i = 0; i < fd; i++) {
                    var e = document.createElement('div');
                    e.className = 'calendar-day empty';
                    cal.appendChild(e);
                }
                for (var d = 1; d <= ld; d++) {
                    var pm = String(month + 1).padStart(2, '0');
                    var pd = String(d).padStart(2, '0');
                    var key = year + '-' + pm + '-' + pd;
                    var cell = document.createElement('div');
                    cell.className = 'calendar-day';
                    cell.textContent = d;
                    cell.setAttribute('data-date', key);
                    if (data[key]) cell.classList.add(data[key]);
                    cal.appendChild(cell);
                }
            }
            renderCal();
            document.getElementById('prevMonth')?.addEventListener('click', function() {
                month--; if (month < 0) { month = 11; year--; } renderCal();
            });
            document.getElementById('nextMonth')?.addEventListener('click', function() {
                month++; if (month > 11) { month = 0; year++; } renderCal();
            });

            // ── Date unavailable modal ──
            var dateInput = document.getElementById('date');
            var dateModal = document.getElementById('dateUnavailableModal');
            var closeModal = document.getElementById('closeUnavailableModal');
            dateInput?.addEventListener('change', function() {
                if (data[this.value] === 'Full' || data[this.value] === 'Closed') {
                    if (dateModal) dateModal.style.display = 'block';
                    this.value = '';
                }
            });
            closeModal?.addEventListener('click', function() { if (dateModal) dateModal.style.display = 'none'; });
            window.addEventListener('click', function(e) { if (e.target === dateModal) dateModal.style.display = 'none'; });

            // ── "Others" toggles ──
            function toggleOther(sel, inp) {
                if (!sel || !inp) return;
                inp.style.display = sel.value === 'Others' ? 'block' : 'none';
                inp.required = sel.value === 'Others';
                if (sel.value !== 'Others') inp.value = '';
            }
            [
                ['venue', 'otherVenue'],
                ['theme_motif', 'otherThemeMotif'],
                ['event_type', 'otherEventType']
            ].forEach(function(pair) {
                var sel = document.getElementById(pair[0]);
                var inp = document.getElementById(pair[1]) || document.getElementById(pair[1].replace('other', 'other_'));
                if (sel) {
                    sel.addEventListener('change', function() { toggleOther(sel, inp); });
                    toggleOther(sel, inp);
                }
            });

            // ── Time slot toggles ──
            var periodSel = document.getElementById('period');
            var tsWrap = document.getElementById('timeSlotWrapper');
            var tsSel = document.getElementById('time_slot');
            var slots = { AM: ['9am–1pm','10am–2pm','11am–3pm'], PM: ['4pm-8pm','5pm–9pm','6pm–10pm'] };
            periodSel?.addEventListener('change', function() {
                var v = this.value;
                tsSel.innerHTML = '<option value="">Select a time slot</option>';
                if (slots[v]) {
                    slots[v].forEach(function(s) {
                        var o = document.createElement('option');
                        o.value = s; o.textContent = s;
                        tsSel.appendChild(o);
                    });
                    tsWrap.style.display = 'block';
                    tsSel.disabled = false;
                } else {
                    tsWrap.style.display = 'none';
                    tsSel.disabled = true;
                }
            });

            // ── Agreement modal ──
            var modal = document.getElementById('agreementModal');
            var content = document.getElementById('agreementContent');
            var agreeBtn = document.getElementById('agreeButton');
            var form = document.querySelector('form');
            var scrolled = false;

            if (modal && content && agreeBtn) {
                var showAgreement = !window.guestAgreementAccepted;

                if (showAgreement) {
                    setTimeout(function() {
                        modal.style.display = 'flex';
                        modal.classList.add('show');
                        if (form) form.classList.add('form-disabled');
                        document.body.style.overflow = 'hidden';
                    }, 100);
                } else {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    if (form) form.classList.remove('form-disabled');
                    document.body.style.overflow = 'auto';
                }

                if (showAgreement) {
                    setTimeout(function() {
                        if (content.scrollHeight <= content.clientHeight + 10) {
                            scrolled = true;
                            agreeBtn.disabled = false;
                        }
                    }, 300);
                }

                content.addEventListener('scroll', function() {
                    if (content.scrollTop + content.clientHeight >= content.scrollHeight - 10) {
                        scrolled = true;
                        agreeBtn.disabled = false;
                    }
                });

                agreeBtn.addEventListener('click', function() {
                    // Enable form immediately — no need to wait for server
                    window.guestAgreementAccepted = true;
                    modal.classList.remove('show');
                    setTimeout(function() { modal.style.display = 'none'; }, 300);
                    if (form) form.classList.remove('form-disabled');
                    document.body.style.overflow = 'auto';
                    // Send consent in background (fire & forget)
                    fetch(window.guestConsentAgreeUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ accepted: true })
                    }).catch(function(err) { console.error('Guest consent error:', err); });
                });
            }
        })();
    </script>


    {{-- Module script removed — all functionality is now inline above for faster loading --}}
@endpush
