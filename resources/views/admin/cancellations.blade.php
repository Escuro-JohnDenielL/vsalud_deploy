@extends('layouts.admin')

@section('title', 'Cancellation Requests')

@push('styles')
    @vite('resources/css/admin/cancellations.css')
@endpush

@section('content')
    <div class="vs-page vs-admin-cancellations">
        <x-ui.page-header title="Cancellation Requests" subtitle="Review and manage patron cancellation requests." />

        <div class="vs-table-frame">
            <div class="vs-table-wrap" role="region" tabindex="0" aria-label="Cancellation requests table">
                <table class="vs-table">
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
                            <th>Processed by</th>
                            <th class="vs-cell-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr class="vs-table__row">
                                <td class="vs-cell-nowrap">#{{ $req->id }}</td>
                                <td>
                                    {{ $req->reservation->patron->name ?? 'None' }}
                                    <br><small class="vs-none">{{ $req->patron_email ?? 'None' }}</small>
                                </td>
                                <td class="vs-cell-nowrap">{{ $req->reservation->inquiry->tracking_code ?? $req->reservation->reserve_id ?? 'None' }}</td>
                                <td class="vs-cell-nowrap">{{ vs_datetime($req->reservation->date ?? null) }}</td>
                                <td>{{ $req->reservation->venue ?? 'None' }}</td>
                                <td>
                                    @php($reason = $req->reason)
                                    @if ($reason && \Illuminate\Support\Str::length($reason) > 60)
                                        <details class="vs-reason">
                                            <summary class="vs-reason__summary">{{ \Illuminate\Support\Str::limit($reason, 60) }}</summary>
                                            <p class="vs-reason__full">{{ $reason }}</p>
                                        </details>
                                    @else
                                        {{ $reason ?: 'None' }}
                                    @endif
                                </td>
                                <td>
                                    @if($req->status === 'pending')
                                        <x-ui.badge variant="warning">Pending</x-ui.badge>
                                    @elseif($req->status === 'approved')
                                        <x-ui.badge variant="success">Approved</x-ui.badge>
                                    @elseif($req->status === 'denied')
                                        <x-ui.badge variant="danger">Denied</x-ui.badge>
                                    @else
                                        <span class="vs-none">None</span>
                                    @endif
                                </td>
                                <td class="vs-cell-nowrap">{{ vs_datetime($req->created_at) }}</td>
                                <td>
                                    @if($req->status === 'pending')
                                        <span class="vs-none">None</span>
                                    @else
                                        {{ $req->admin?->name ?? 'None' }}
                                        @if($req->admin_note)
                                            <br><small class="vs-none">{{ \Illuminate\Support\Str::limit($req->admin_note, 60) }}</small>
                                        @endif
                                    @endif
                                </td>
                                <td class="vs-cell-actions">
                                    @if($req->status === 'pending')
                                        <x-ui.row-actions>
                                            <x-slot:view>
                                                <button type="button" class="vs-row-actions__view btn-approve"
                                                    data-request-id="{{ $req->id }}">Approve</button>
                                            </x-slot:view>
                                            <x-slot:menu>
                                                <button type="button" class="vs-overflow__item vs-overflow__item--danger btn-deny"
                                                    data-request-id="{{ $req->id }}">Deny</button>
                                            </x-slot:menu>
                                        </x-ui.row-actions>
                                    @else
                                        <span class="vs-none">None</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="10" message="No cancellation requests found." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $requests->links() }}
    </div>

    {{-- Approve Modal --}}
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('approveModal')">&times;</span>
            <h3>Approve Cancellation</h3>
            <p style="font-size: 14px; margin: 12px 0;">The reservation will be cancelled and the patron will be notified.</p>
            <form id="approveForm">
                @csrf
                <input type="hidden" id="approveRequestId" name="request_id" />
                <div class="form-group">
                    <label for="approveNote">Admin Note (required, included in email):</label>
                    <textarea id="approveNote" name="admin_note" rows="3" placeholder="Why is this cancellation being approved?" required minlength="5"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('approveModal')">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Confirm Approve</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Deny Modal --}}
    <div id="denyModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('denyModal')">&times;</span>
            <h3>Deny Cancellation</h3>
            <p style="font-size: 14px; margin: 12px 0;">The patron will be notified that their request was denied.</p>
            <form id="denyForm">
                @csrf
                <input type="hidden" id="denyRequestId" name="request_id" />
                <div class="form-group">
                    <label for="denyNote">Reason for Denial (required, included in email):</label>
                    <textarea id="denyNote" name="admin_note" rows="3" placeholder="Explain why the request was denied..." required minlength="5"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-btn admin-btn-ghost" onclick="closeModal('denyModal')">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-danger">Confirm Deny</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/admin/cancellations.js')
@endpush
