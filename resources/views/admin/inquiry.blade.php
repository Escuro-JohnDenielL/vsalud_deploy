@extends('layouts.admin')

@section('title', 'View Inquiries')

@push('styles')
    @vite('resources/css/admin/inquiry.css')
@endpush

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>View Inquiries</h1>
            <p>View and manage customer inquiries.</p>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table class="inquiry-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact Number</th>
                            <th>Tracking Code</th>
                            <th>Time</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Event Type</th>
                            <th>Theme and Motif</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inquiries as $index => $inquiry)
                            <tr data-inquiry-id="{{ $inquiry->inquiry_id }}">
                                <td>{{ $inquiry->patron->name ?? 'N/A' }}</td>
                                <td>{{ $inquiry->patron->email ?? '-' }}</td>
                                <td>{{ $inquiry->patron->contact_number ?? '-' }}</td>
                                <td><strong>{{ $inquiry->tracking_code ?? '-' }}</strong></td>
                                <td>{{ $inquiry->time ?? '-' }}</td>
                                <td>{{ $inquiry->date ?? '-' }}</td>
                                <td>{{ ($inquiry->venue === 'Others' ? $inquiry->other_venue : $inquiry->venue) ?? '-' }}</td>
                                <td>{{ ($inquiry->event_type === 'Others' ? $inquiry->other_event_type : $inquiry->event_type) ?? '-' }}</td>
                                <td>{{ ($inquiry->theme_motif === 'Others' ? $inquiry->other_theme_motif : $inquiry->theme_motif) ?? '-' }}</td>
                                <td>
                                    <select class="status-dropdown" data-index="{{ $index }}">
                                        <option value="" {{ $inquiry->status == null ? 'selected hidden' : 'hidden' }}>Set Status</option>
                                        <option value="Pending" {{ $inquiry->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="In Progress" {{ $inquiry->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="Completed" {{ $inquiry->status === 'Completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="Cancelled" {{ $inquiry->status === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </td>
                                <td>patron</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="admin-btn admin-btn-ghost admin-btn-sm view-inquiry-btn"
                                            data-name="{{ $inquiry->patron->name ?? 'N/A' }}"
                                            data-email="{{ $inquiry->patron->email ?? '-' }}"
                                            data-contact="{{ $inquiry->patron->contact_number ?? '-' }}"
                                            data-code="{{ $inquiry->tracking_code ?? '-' }}"
                                            data-time="{{ $inquiry->time ?? '-' }}"
                                            data-date="{{ $inquiry->date ?? '-' }}"
                                            data-venue="{{ ($inquiry->venue === 'Others' ? $inquiry->other_venue : $inquiry->venue) ?? '-' }}"
                                            data-event-type="{{ ($inquiry->event_type === 'Others' ? $inquiry->other_event_type : $inquiry->event_type) ?? '-' }}"
                                            data-theme="{{ ($inquiry->theme_motif === 'Others' ? $inquiry->other_theme_motif : $inquiry->theme_motif) ?? '-' }}"
                                            data-status="{{ $inquiry->status ?? 'Pending' }}"
                                            data-message="{{ $inquiry->message ?? '' }}">View</button>
                                        <button class="admin-btn admin-btn-primary admin-btn-sm reply-btn"
                                            data-inquiry-id="{{ $inquiry->inquiry_id }}"
                                            data-email="{{ $inquiry->patron->email ?? '-' }}"
                                            data-status="{{ $inquiry->status ?? 'Pending' }}">Reply</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center">No inquiries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px; padding: 16px 20px;">
                {{ $inquiries->links() }}
            </div>
        </div>
    </div>

    {{-- View Inquiry Modal --}}
    <div id="viewInquiryModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeViewInquiryModal">&times;</span>
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
                    <div class="detail-row" style="border:none;">
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
                    <div class="detail-row" style="border:none;">
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
    <div id="replyModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close-btn" id="closeReplyModal">&times;</span>
            <h2>Reply to Inquiry</h2>
            <p style="font-size: 14px; color: var(--color-text-muted); margin: -8px 0 16px;">Send a response to the patron's inquiry.</p>

            <div class="detail-section">
                <div class="detail-section-title">Your Message</div>
                <p style="font-size: 13px; color: var(--color-text-muted); margin-top: -8px;">Tip: use <strong>AI Draft Reply</strong> below to generate a personalized draft from the inquiry.</p>
                <p style="font-size: 12px; color: var(--color-text-muted); font-style: italic; margin-top: -4px;">Disclaimer: AI drafts are generated from general Villa Salud info only — package prices and details inside the venue may not be fully accurate. Always review and edit before sending.</p>
                <textarea id="replyMessage" placeholder="Type your reply..." rows="6"></textarea>
                <p id="aiDraftStatus" class="ai-draft-status"></p>
            </div>

            <div class="modal-footer">
                <button id="cancelReplyBtn" class="admin-btn admin-btn-ghost">Cancel</button>
                <button id="aiDraftBtn" class="admin-btn admin-btn-ghost" type="button">
                    <span id="aiDraftLabel">✨ AI Draft Reply</span>
                </button>
                <button id="sendReplyBtn" class="admin-btn admin-btn-primary">Send Reply</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/admin/inquiries.js')
@endpush
