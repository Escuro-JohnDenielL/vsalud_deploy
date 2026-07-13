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
                                        <button class="admin-btn admin-btn-primary admin-btn-sm reply-btn"
                                            data-inquiry-id="{{ $inquiry->inquiry_id }}"
                                            data-email="{{ $inquiry->patron->email ?? '-' }}"
                                            data-status="{{ $inquiry->status ?? 'Pending' }}">Reply</button>
                                        @if ($inquiry->status === 'Completed')
                                            <button class="admin-btn admin-btn-ghost admin-btn-sm undo-btn"
                                                data-inquiry-id="{{ $inquiry->inquiry_id }}">Undo</button>
                                        @endif
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

    {{-- Reply Modal --}}
    <div id="replyModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close-btn" id="closeReplyModal">&times;</span>
            <h2>Reply to Inquiry</h2>
            <p style="font-size: 14px; color: var(--color-text-muted); margin: -8px 0 16px;">Send a response to the patron's inquiry.</p>

            <div class="detail-section">
                <div class="detail-section-title">Response Template</div>
                <label for="replyOptions">Choose a quick response:</label>
                <select id="replyOptions" class="reply-select">
                    <option value="" disabled selected>Select a suggestion</option>
                </select>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">Your Message</div>
                <textarea id="replyMessage" placeholder="Type your reply..." rows="6"></textarea>
            </div>

            <div class="modal-footer">
                <button id="cancelReplyBtn" class="admin-btn admin-btn-ghost">Cancel</button>
                <button id="sendReplyBtn" class="admin-btn admin-btn-primary">Send Reply</button>
            </div>
        </div>
    </div>

    {{-- Confirm Undo Modal --}}
    <div id="confirmUndoModal" class="modal">
        <div class="modal-content modal-sm">
            <span class="close-btn" id="closeUndoModal">&times;</span>
            <h3>Confirm Undo</h3>
            <p id="confirmUndoMessage" style="font-size: 15px; margin: 16px 0;">Are you sure you want to undo this reservation?</p>
            <div class="modal-footer">
                <button id="confirmUndoNo" class="admin-btn admin-btn-ghost">Cancel</button>
                <button id="confirmUndoYes" class="admin-btn admin-btn-primary">Undo</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/inquiries.js')
@endpush
