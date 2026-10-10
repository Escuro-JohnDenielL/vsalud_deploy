@extends('layouts.admin')

@section('title', 'View Inquiries')

@push('styles')
    @vite('resources/css/admin/inquiry.css')
@endpush

@section('content')
    <div class="vs-page vs-admin-inquiry">
        <x-ui.page-header title="Inquiries" subtitle="View and manage customer inquiries." />

        <div class="vs-table-frame">
            <div class="vs-table-wrap" role="region" tabindex="0" aria-label="Inquiries table">
                <table class="vs-table inquiry-table">
                    <thead>
                        <tr>
                            <th>Patron</th>
                            <th>Code</th>
                            <th>Event date</th>
                            <th>Venue</th>
                            <th>Event Type</th>
                            <th>Theme</th>
                            <th>Status</th>
                            <th class="vs-cell-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inquiries as $index => $inquiry)
                            @php
                                $inqName = $inquiry->patron->name ?? 'None';
                                $inqEmail = $inquiry->patron->email ?? 'None';
                                $inqContact = $inquiry->patron->contact_number ?? 'None';
                                $inqVenue = ($inquiry->venue === 'Others' ? $inquiry->other_venue : $inquiry->venue) ?? 'None';
                                $inqEventType = ($inquiry->event_type === 'Others' ? $inquiry->other_event_type : $inquiry->event_type) ?? 'None';
                                $inqTheme = ($inquiry->theme_motif === 'Others' ? $inquiry->other_theme_motif : $inquiry->theme_motif) ?? 'None';
                            @endphp
                            <tr class="vs-table__row" data-inquiry-id="{{ $inquiry->inquiry_id }}">
                                <td>
                                    <strong>{{ $inqName }}</strong>
                                    <br><span class="vs-cell-truncate vs-none" title="{{ $inqEmail }}">{{ $inqEmail }}</span>
                                    <br><span class="vs-none">{{ $inqContact }}</span>
                                </td>
                                <td class="vs-cell-nowrap"><strong>{{ $inquiry->tracking_code ?? 'None' }}</strong></td>
                                <td class="vs-cell-nowrap">
                                    {{ $inquiry->date ? vs_date($inquiry->date) : 'None' }}
                                    <br><span class="vs-none">{{ $inquiry->time ? vs_time($inquiry->time) : 'None' }}</span>
                                </td>
                                <td>{{ $inqVenue }}</td>
                                <td>{{ $inqEventType }}</td>
                                <td>{{ $inqTheme }}</td>
                                <td>
                                    <x-ui.status-select
                                        name="status"
                                        :selected="$inquiry->status"
                                        :options="['Pending' => 'Pending', 'In Progress' => 'In Progress', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled']"
                                        :variants="['Pending' => 'warning', 'In Progress' => 'info', 'Completed' => 'success', 'Cancelled' => 'danger']"
                                        placeholder="Set Status"
                                        class="status-dropdown"
                                        data-index="{{ $index }}" />
                                </td>
                                <td class="vs-cell-actions">
                                    <x-ui.row-actions>
                                        <x-slot:view>
                                            <button type="button" class="vs-row-actions__view view-inquiry-btn"
                                                data-name="{{ $inqName }}"
                                                data-email="{{ $inqEmail }}"
                                                data-contact="{{ $inqContact }}"
                                                data-code="{{ $inquiry->tracking_code ?? 'None' }}"
                                                data-time="{{ $inquiry->time ? vs_time($inquiry->time) : 'None' }}"
                                                data-date="{{ $inquiry->date ? vs_date($inquiry->date) : 'None' }}"
                                                data-venue="{{ $inqVenue }}"
                                                data-event-type="{{ $inqEventType }}"
                                                data-theme="{{ $inqTheme }}"
                                                data-status="{{ $inquiry->status ?? 'Pending' }}"
                                                data-message="{{ $inquiry->message ?? '' }}">View</button>
                                        </x-slot:view>
                                        <x-slot:menu>
                                            <button type="button" class="vs-overflow__item reply-btn"
                                                data-inquiry-id="{{ $inquiry->inquiry_id }}"
                                                data-email="{{ $inqEmail }}"
                                                data-status="{{ $inquiry->status ?? 'Pending' }}">Reply</button>
                                        </x-slot:menu>
                                    </x-ui.row-actions>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="8" message="No inquiries found." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $inquiries->links() }}
    </div>

    {{-- View Inquiry Modal --}}
    <div id="viewInquiryModal" class="modal vs-modal">
        <div class="modal-content vs-modal__dialog">
            <button type="button" class="close-btn vs-modal__close" id="closeViewInquiryModal" aria-label="Close inquiry details"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
            <h2>Inquiry Details</h2>
            <div class="inquiry-detail">
                <div class="detail-section">
                    <div class="detail-section-title">Patron</div>
                    <div class="detail-row">
                        <span class="detail-label">Name</span>
                        <span class="detail-value" id="view-name"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value" id="view-email"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contact Number</span>
                        <span class="detail-value" id="view-contact"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tracking Code</span>
                        <span class="detail-value" id="view-code"></span>
                    </div>
                    <div class="detail-row vs-inquiry-detail-row--last">
                        <span class="detail-label">Status</span>
                        <span class="detail-value" id="view-status"></span>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-title">Event Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Date</span>
                        <span class="detail-value" id="view-date"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Time</span>
                        <span class="detail-value" id="view-time"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Venue</span>
                        <span class="detail-value" id="view-venue"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Event Type</span>
                        <span class="detail-value" id="view-event-type"></span>
                    </div>
                    <div class="detail-row vs-inquiry-detail-row--last">
                        <span class="detail-label">Theme and Motif</span>
                        <span class="detail-value" id="view-theme"></span>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-title">Message</div>
                    <div id="view-message" class="detail-message-block"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reply Modal --}}
    <div id="replyModal" class="modal vs-modal">
        <div class="modal-content modal-lg vs-modal__dialog">
            <button type="button" class="close-btn vs-modal__close" id="closeReplyModal" aria-label="Close reply dialog"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
            <h2>Reply to Inquiry</h2>
            <p class="vs-inquiry-copy">Send a response to the patron's inquiry.</p>

            <div class="detail-section">
                <div class="detail-section-title">Your Message</div>
                <p class="vs-inquiry-copy vs-inquiry-copy--tip">Tip: use <strong>AI Draft Reply</strong> below to generate a personalized draft from the inquiry.</p>
                <p class="vs-inquiry-copy vs-inquiry-copy--disclaimer">Disclaimer: AI drafts are generated from general Villa Salud info only; package prices and details inside the venue may not be fully accurate. Always review and edit before sending.</p>
                <textarea id="replyMessage" class="vs-form-control" placeholder="Type your reply..." rows="6"></textarea>
                <p id="aiDraftStatus" class="ai-draft-status"></p>
            </div>

            <div class="modal-footer">
                <button id="cancelReplyBtn" class="admin-btn admin-btn-ghost">Cancel</button>
                <button id="aiDraftBtn" class="admin-btn admin-btn-ghost" type="button">
                    <span id="aiDraftLabel">AI Draft Reply</span>
                </button>
                <button id="sendReplyBtn" class="admin-btn admin-btn-primary">Send Reply</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/admin/inquiries.js')
@endpush
