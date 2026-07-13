@extends('layouts.admin')

@section('title', 'Reservation Logs')

@push('styles')
    @vite('resources/css/admin/reserve-logs.css')
@endpush

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Reservation Logs</h1>
            <p>Manage and track all reservation requests</p>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table class="reservation-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Message</th>
                            <th>Venue</th>
                            <th>Event Type</th>
                            <th>Theme/Motif</th>
                            {{-- <th>Receipt</th> --}}
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="paginated-table" id="reservationTableBody">
                        @forelse($reservations as $reservation)
                            <tr>
                                <td>{{ $reservation->patron->name ?? 'N/A' }}</td>
                                <td>{{ $reservation->patron->email ?? '-' }}</td>
                                <td><strong>{{ $reservation->inquiry->tracking_code ?? 'RSV-' . str_pad($reservation->reserve_id, 6, '0', STR_PAD_LEFT) }}</strong></td>
                                <td>{{ $reservation->date ?? '-' }}</td>
                                <td>{{ $reservation->time ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($reservation->message, 50) ?? '-' }}</td>
                                <td>{{ $reservation->venue ?? '-' }}</td>
                                <td>{{ $reservation->event_type ?? '-' }}</td>
                                <td>{{ $reservation->theme_motif ?? '-' }}</td>
                                {{-- <td>
                                    <a href="#" class="receipt-link">View Receipt</a>
                                </td> --}}
                                <td>
                                    <select class="status-dropdown" data-id="{{ $reservation->id }}">
                                        <option value="active" {{ $reservation->status === 'active' ? 'selected' : '' }}>
                                            Active</option>
                                        <option value="canceled"
                                            {{ $reservation->status === 'canceled' ? 'selected' : '' }}>Canceled</option>
                                        <option value="completed"
                                            {{ $reservation->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <button class="admin-btn admin-btn-primary admin-btn-sm" data-reservation-id="{{ $reservation->reserve_id }}"
                                            onclick="viewReservation(this.dataset.reservationId)">View</button>
                                        <button class="admin-btn admin-btn-danger admin-btn-sm" data-reservation-id="{{ $reservation->reserve_id }}"
                                            onclick="deleteReservation(this.dataset.reservationId)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center">No reservations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- View Modal -->
            <div id="viewModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeModal()">&times;</span>
                    <h2>Reservation Details</h2>
                    <div id="modalBody">
                        Loading...
                    </div>
                </div>
            </div>


            <div style="margin-top: 16px; padding: 16px 20px;">
                {{ $reservations->links() }}
            </div>

        </div>

    </div>

    {{-- Confirm Delete Modal --}}
    <div id="confirmDeleteReservationModal" class="modal">
        <div class="modal-content modal-sm">
            <span class="close-btn" id="closeConfirmDeleteModal">&times;</span>
            <h3>Confirm Delete</h3>
            <p id="confirmDeleteReservationMessage" style="font-size: 15px; margin: 12px 0;">Are you sure you want to delete this reservation?</p>
            <div class="modal-footer">
                <button id="confirmDeleteReservationNo" class="admin-btn admin-btn-ghost">Cancel</button>
                <button id="confirmDeleteReservationYes" class="admin-btn admin-btn-danger">Delete</button>
            </div>
        </div>
    </div>

    @vite('resources/js/admin/reserve-logs.js')
@endsection
