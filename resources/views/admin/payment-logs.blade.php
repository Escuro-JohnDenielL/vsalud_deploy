@extends('layouts.admin')

@section('title', 'Payment Logs')

@push('styles')
    @vite('resources/css/admin/payment-logs.css')
@endpush

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Payment Logs</h1>
            <p>View and manage payment records submitted by patrons.</p>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table class="payment-table">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 16px; padding: 16px 20px;">
                {{ $payment_logs->links() }}
            </div>
        </div>
    </div>

    <!-- Payment Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeReceiptModal">&times;</span>
            <h2>Payment Receipt</h2>
            <div style="text-align:center;">
                <img id="receiptImage1" src="" alt="Receipt Image" class="receipt-image">
            </div>
        </div>
    </div>

    @vite('resources/js/admin/payment-logs.js')
@endsection
