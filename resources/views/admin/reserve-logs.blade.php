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
                                        <button class="btn-view" data-reservation-id="{{ $reservation->reserve_id }}"
                                            onclick="viewReservation(this.dataset.reservationId)">View</button>
                                        <button class="btn-delete" data-reservation-id="{{ $reservation->reserve_id }}"
                                            onclick="deleteReservation(this.dataset.reservationId)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">No reservations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- View Modal -->
            <div id="viewModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Reservation Details</h2>
                    <div id="modalBody">
                        Loading...
                    </div>
                </div>
            </div>


            <div class="pagination">
                <button class="btn-pagination" onclick="prevPage('reservationTableBody')">Previous</button>
                <span class="page-info" id="reservationPageInfo">Page 1</span>
                <button class="btn-pagination" onclick="nextPage('reservationTableBody')">Next</button>
            </div>

        </div>

        <div class="page-header">
            <br>
            <h1>Reservation Logs</h1>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table class="reservation-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Payment Type</th>
                            <th>Payment Method</th>
                            <th>Tracking Code</th>
                            <th>Reservation Code</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="paginated-table" id="paymentTableBody">
                        @forelse($payment_logs as $payment_log)
                            <tr>
                                <td>{{ $payment_log->full_name }}</td>
                                <td>{{ $payment_log->payment_type ?? '-' }}</td>
                                <td>{{ $payment_log->payment_method ?? '-' }}</td>
                                <td>{{ $payment_log->tracking_code ?? '-' }}</td>
                                <td>{{ $payment_log->reservation_code ?? '-' }}</td>
                                <td>{{ $payment_log->email ?? '-' }}</td>
                                <td>
                                    <select class="payment-status-dropdown" data-payment-id="{{ $payment_log->payment_id }}">
                                        <option value="pending" {{ $payment_log->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="approved" {{ $payment_log->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="rejected" {{ $payment_log->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        <option value="refunded" {{ $payment_log->status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                                    </select>
                                </td>
                                <td>
                                    <a href="#" class="receipt-link1"
                                        data-receipt="{{ asset('storage/' . $payment_log->receipt_path) }}">View
                                        Receipt</a>

                                    {{-- <a href="#" class="receipt-link">View Receipt</a> --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">No payment found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- View Modal -->
            <div id="viewModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Reservation Details</h2>
                    <div id="modalBody">
                        Loading...
                    </div>
                </div>
            </div>


            <div class="pagination">
                <button class="btn-pagination" onclick="prevPage('paymentTableBody')">Previous</button>
                <span class="page-info" id="paymentPageInfo">Page 1</span>
                <button class="btn-pagination" onclick="nextPage('paymentTableBody')">Next</button>
            </div>

        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal1" class="modal1 custom-modal">
        <div class="custom-modal-content modal-content1">
            <button class="close1 custom-modal-close" onclick="closeModal()">&times;</button>
            <h2 class="custom-modal-title">Payment Receipt</h2>
            <div class="custom-modal-body">
                <img id="receiptImage1" src="" alt="Receipt Image" class="receipt-image">
            </div>
        </div>
    </div>


    {{-- Confirm Delete Modal --}}
    <div id="confirmDeleteReservationModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 420px;">
            <span class="close" id="closeConfirmDeleteModal" style="cursor:pointer;font-size:24px;float:right;">&times;</span>
            <h3>Confirm Delete</h3>
            <p id="confirmDeleteReservationMessage" style="font-size: 15px; margin: 20px 0;">Are you sure you want to delete this reservation?</p>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button id="confirmDeleteReservationNo" class="btn-view" style="background:#6c757d;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;">Cancel</button>
                <button id="confirmDeleteReservationYes" class="btn-delete" style="border:none;padding:8px 16px;border-radius:4px;cursor:pointer;">Delete</button>
            </div>
        </div>
    </div>

    @vite('resources/js/admin/reserve-logs.js')
@endsection
