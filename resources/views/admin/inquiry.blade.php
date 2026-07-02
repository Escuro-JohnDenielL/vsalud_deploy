@extends('layouts.admin')

@section('title', 'View Inquiries')

@push('styles')
    @vite('resources/css/admin/inquiry.css')
@endpush

@section('content')
    <div class="inquiry-content">
        <section id="content">
            <main>
                <div class="head-title">
                    <h1>View Inquiries</h1>
                    <p>View and manage customer inquiries.</p>
                </div>

                <div class="table-data">
                    <div class="order">
                        <div class="head">
                            <h3>Recent Inquiries</h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact Number</th>
                                    <th>Time</th>
                                    <th>Date</th>
                                    <th>Venue</th>
                                    <th>Event Type</th>
                                    <th>Theme and Motif</th>
                                    <th>Status</th>
                                    <th>Submitted By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inquiries as $index => $inquiry)
                                    <tr data-inquiry-id="{{ $inquiry->inquiry_id }}">
                                        <td>{{ $inquiry->patron->name ?? 'N/A' }}</td>
                                        <td>{{ $inquiry->patron->email ?? '-' }}</td>
                                        <td>{{ $inquiry->patron->contact_number ?? '-' }}</td>
                                        <td>{{ $inquiry->time ?? '-' }}</td>
                                        <td>{{ $inquiry->date ?? '-' }}</td>
                                        <td>{{ ($inquiry->venue === 'Others' ? $inquiry->other_venue : $inquiry->venue) ?? '-' }}
                                        </td>
                                        <td>{{ ($inquiry->event_type === 'Others' ? $inquiry->other_event_type : $inquiry->event_type) ?? '-' }}
                                        </td>
                                        <td>{{ ($inquiry->theme_motif === 'Others' ? $inquiry->other_theme_motif : $inquiry->theme_motif) ?? '-' }}
                                        </td>

                                        <td>
                                            <select class="status-dropdown" data-index="{{ $index }}">
                                                <option value=""
                                                    {{ $inquiry->status == null ? 'selected hidden' : 'hidden' }}>Set
                                                    Status</option>
                                                <option value="Pending"
                                                    {{ $inquiry->status === 'Pending' ? 'selected' : '' }}>
                                                    Pending</option>
                                                <option value="In Progress"
                                                    {{ $inquiry->status === 'In Progress' ? 'selected' : '' }}>In Progress
                                                </option>
                                                <option value="Completed"
                                                    {{ $inquiry->status === 'Completed' ? 'selected' : '' }}>Completed
                                                </option>
                                                <option value="Cancelled"
                                                    {{ $inquiry->status === 'Cancelled' ? 'selected' : '' }}>Cancelled
                                                </option>
                                            </select>
                                        </td>
                                        <td>patron</td>
                                        <td>
                                            <button class="reply-btn" data-index="{{ $index }}">Reply</button>
                                            @if ($inquiry->status === 'Completed')
                                                <button class="undo-btn"
                                                    data-inquiry-id="{{ $inquiry->inquiry_id }}">Undo</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </section>

        <div id="statusModal" class="modal">
            <div class="modal-content">
                <h2>Set Inquiry Status</h2>
                <select id="statusSelect">
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <button id="saveStatusBtn">Save</button>
                <button id="closeModalBtn">Close</button>
            </div>
        </div>
    </div>

    <div id="replyModal" class="modal reply-modal">
        <div class="modal-content">
            <span class="close-btn" id="closeReplyModal">&times;</span>
            <h2>Reply to Inquiry</h2>

            <label for="replyOptions">Choose a response:</label>
            <select id="replyOptions" class="reply-select">
                <option value="" disabled selected>Select a suggestion</option>
            </select>

            <label for="replyMessage">Message:</label>
            <textarea id="replyMessage" placeholder="Type your reply..." rows="6"></textarea>

            <div class="modal-actions">
                <button id="sendReplyBtn">Send Reply</button>
                <button id="cancelReplyBtn">Cancel</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/admin/inquiries.js')
@endpush
