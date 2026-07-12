@extends('layouts.admin')

@section('title', 'Cancellation Requests')

@push('styles')
    @vite('resources/css/admin/cancellations.css')
@endpush

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Cancellation Requests</h1>
            <p>Review and manage patron cancellation requests.</p>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table class="cancellation-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patron</th>
                            <th>Reservation Code</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td>#{{ $req->id }}</td>
                                <td>
                                    {{ $req->reservation->patron->name ?? 'N/A' }}
                                    <br><small style="color:#888;">{{ $req->patron_email }}</small>
                                </td>
                                <td>{{ $req->reservation->inquiry->tracking_code ?? $req->reservation->reserve_id ?? 'N/A' }}</td>
                                <td>{{ $req->reservation->date ?? 'N/A' }}</td>
                                <td>{{ $req->reservation->venue ?? 'N/A' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($req->reason, 60) ?: '—' }}</td>
                                <td>
                                    @if($req->status === 'pending')
                                        <span class="badge badge-pending">Pending</span>
                                    @elseif($req->status === 'approved')
                                        <span class="badge badge-approved">Approved</span>
                                    @elseif($req->status === 'denied')
                                        <span class="badge badge-denied">Denied</span>
                                    @endif
                                </td>
                                <td>{{ $req->created_at->format('M d, Y g:i A') }}</td>
                                <td>
                                    @if($req->status === 'pending')
                                        <div class="action-buttons">
                                            <button class="btn-approve"
                                                data-request-id="{{ $req->id }}">Approve</button>
                                            <button class="btn-deny"
                                                data-request-id="{{ $req->id }}">Deny</button>
                                        </div>
                                    @else
                                        <span class="text-muted">
                                            Processed by {{ $req->admin?->name ?? 'N/A' }}
                                            @if($req->admin_note)
                                                <br><small>"{{ \Illuminate\Support\Str::limit($req->admin_note, 40) }}"</small>
                                            @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No cancellation requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px;">
                {{ $requests->links() }}
            </div>
        </div>
    </div>

    {{-- Approve Modal --}}
    <div id="approveModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 480px;">
            <span class="close" onclick="closeModal('approveModal')">&times;</span>
            <h3>Approve Cancellation</h3>
            <p style="font-size: 14px; margin: 12px 0;">The reservation will be cancelled and the patron will be notified.</p>
            <form id="approveForm">
                @csrf
                <input type="hidden" id="approveRequestId" name="request_id" />
                <div class="form-group">
                    <label for="approveNote">Admin Note (optional, included in email):</label>
                    <textarea id="approveNote" name="admin_note" rows="3" placeholder="Add a note..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px;">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('approveModal')">Cancel</button>
                    <button type="submit" class="btn-approve">Confirm Approve</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Deny Modal --}}
    <div id="denyModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 480px;">
            <span class="close" onclick="closeModal('denyModal')">&times;</span>
            <h3>Deny Cancellation</h3>
            <p style="font-size: 14px; margin: 12px 0;">The patron will be notified that their request was denied.</p>
            <form id="denyForm">
                @csrf
                <input type="hidden" id="denyRequestId" name="request_id" />
                <div class="form-group">
                    <label for="denyNote">Reason for Denial (optional, included in email):</label>
                    <textarea id="denyNote" name="admin_note" rows="3" placeholder="Explain why the request was denied..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px;">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('denyModal')">Cancel</button>
                    <button type="submit" class="btn-deny">Confirm Deny</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Success/Error Message Toast --}}
    <div id="toast" class="toast" style="display:none;"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/cancellations.js')
@endpush
