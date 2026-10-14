@extends('layouts.admin')

@section('title', 'Payment Logs')

@push('styles')
    @vite('resources/css/admin/payment-logs.css')
@endpush

@section('content')
    <div class="container payment-logs-page vs-admin-payment-logs">
        <div class="page-header">
            <h1>Payment Logs</h1>
            <p>View and manage payment records submitted by patrons.</p>
        </div>

        <div class="table-container">
            <div class="payment-toolbar">
                <form class="payment-search" method="GET" action="{{ route('admin.payment-logs') }}">
                    <label for="paymentSearch">Search reservations</label>
                    <div class="payment-search-controls">
                        <input id="paymentSearch" name="search" type="search" value="{{ $search }}"
                            placeholder="Name, email, or reservation code">
                        <button type="submit" class="admin-btn admin-btn-primary">Search</button>
                        @if($search !== '')
                            <a class="admin-btn admin-btn-ghost" href="{{ route('admin.payment-logs') }}">Clear</a>
                        @endif
                    </div>
                </form>
                <button type="button" id="paymentInfoOpen" class="payment-info-button"
                    aria-haspopup="dialog" aria-controls="paymentInfoModal">
                    <span class="payment-info-icon" aria-hidden="true">i</span>
                    How totals work
                </button>
            </div>

            <div class="table-wrapper vs-table-wrap" role="region" tabindex="0" aria-label="Payment logs table">
                <table class="payment-table vs-table">
                    <colgroup>
                        <col class="payment-col-reservation">
                        <col class="payment-col-email">
                        <col class="payment-col-money">
                        <col class="payment-col-money">
                        <col class="payment-col-money">
                        <col class="payment-col-confirmation">
                        <col class="payment-col-pending">
                        <col class="payment-col-details">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Name / Reservation</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th>Paid So Far</th>
                            <th>Remaining</th>
                            <th>Confirmation</th>
                            <th>Pending</th>
                            <th>Payment Details</th>
                        </tr>
                    </thead>
                    <tbody class="paginated-table" id="paymentTableBody">
                        @forelse($payment_groups as $group)
                            @if($group['kind'] === 'reservation')
                                <tr class="reservation-group-row">
                                    <td>
                                        <button type="button" class="payment-group-toggle"
                                            aria-expanded="false" aria-controls="{{ $group['dom_id'] }}">
                                            <span class="payment-group-indicator" aria-hidden="true">+</span>
                                            <span class="payment-group-identity">
                                                <strong>{{ $group['name'] }}</strong>
                                                <small>{{ $group['reservation_code'] }}</small>
                                            </span>
                                        </button>
                                    </td>
                                    <td>{{ $group['email'] }}</td>
                                    <td>₱{{ $group['total_display'] }}</td>
                                    <td>₱{{ $group['paid_display'] }}</td>
                                    <td>₱{{ $group['remaining_display'] }}</td>
                                    <td>
                                        @php
                                            $confirmationVariant = match ($group['confirmation_label']) {
                                                'Fully paid' => 'success',
                                                'Downpayment received' => 'info',
                                                default => 'neutral',
                                            };
                                        @endphp
                                        <span class="vs-badge vs-badge--{{ $confirmationVariant }}">{{ $group['confirmation_label'] }}</span>
                                    </td>
                                    <td>{{ $group['pending_count'] }}</td>
                                    <td>
                                        <button type="button" class="payment-group-details-toggle"
                                            data-payment-count="{{ $group['payment_count'] }}"
                                            aria-expanded="false" aria-controls="{{ $group['dom_id'] }}">
                                            View {{ $group['payment_count'] }} payments
                                        </button>
                                    </td>
                                </tr>
                                <tr class="payment-child-container" data-parent-group="{{ $group['dom_id'] }}" hidden>
                                    <td colspan="8">
                                        <table class="payment-child-table vs-table">
                                            <thead>
                                                <tr>
                                                    <th>Payment Type</th>
                                                    <th>Payment Method</th>
                                                    <th>Tracking Code</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Receipt</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($group['payments'] as $payment_log)
                                                    <tr>
                                                        <td>{{ $payment_log->payment_type ?? '-' }}</td>
                                                        <td>{{ $payment_log->payment_method ?? '-' }}</td>
                                                        <td>{{ $payment_log->tracking_code ?? '-' }}</td>
                                                        <td>{{ $payment_log->amount_display }}</td>
                                                        <td>
                                                            <select class="payment-status-dropdown vs-form-control" data-payment-id="{{ $payment_log->payment_id }}">
                                                                <option value="pending" {{ $payment_log->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                                <option value="approved" {{ $payment_log->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                                                <option value="rejected" {{ $payment_log->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                                <option value="refunded" {{ $payment_log->status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <a href="#" class="receipt-link1"
                                                                data-receipt="{{ route('admin.receipts.show', $payment_log->payment_id) }}">View Receipt</a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @else
                                @php($payment_log = $group['payment'])
                                <tr class="standalone-payment-row">
                                    <td colspan="8">
                                        <div class="standalone-payment-card">
                                            <div class="standalone-payment-identity">
                                                <strong>{{ $payment_log->full_name }}</strong>
                                                <span>{{ $payment_log->reservation_code ?? 'No reservation code' }}</span>
                                                <span>{{ $payment_log->email ?? 'No email recorded' }}</span>
                                            </div>
                                            <div class="standalone-payment-actions">
                                                <select class="payment-status-dropdown vs-form-control" data-payment-id="{{ $payment_log->payment_id }}" aria-label="Payment status for {{ $payment_log->full_name }}">
                                                    <option value="pending" {{ $payment_log->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="approved" {{ $payment_log->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                                    <option value="rejected" {{ $payment_log->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                    <option value="refunded" {{ $payment_log->status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                                                </select>
                                                <button type="button" class="payment-info-button payment-details-open"
                                                    data-payment-info-target="paymentDetails{{ $payment_log->payment_id }}"
                                                    aria-haspopup="dialog" aria-controls="paymentDetails{{ $payment_log->payment_id }}">
                                                    <span class="payment-info-icon" aria-hidden="true">i</span>
                                                    Payment info
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    {{ $search !== '' ? 'No reservations match your search.' : 'No payment records found.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @foreach($payment_groups as $group)
                @if($group['kind'] === 'standalone')
                    @php($payment_log = $group['payment'])
                    <div class="modal payment-details-modal vs-admin-payment-logs__modal" id="paymentDetails{{ $payment_log->payment_id }}" aria-hidden="true">
                        <div class="modal-content modal-lg payment-info-modal" role="dialog" aria-modal="true" aria-labelledby="paymentDetailsTitle{{ $payment_log->payment_id }}">
                            <button type="button" class="close-btn js-payment-modal-close" aria-label="Close payment information">&times;</button>
                            <h2 id="paymentDetailsTitle{{ $payment_log->payment_id }}">Payment details</h2>
                            <div class="detail-section">
                                <div class="detail-section-title">Payment</div>
                                <div class="detail-section-card">
                                    <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">{{ $payment_log->payment_type ?? 'Not recorded' }}</span></div>
                                    <div class="detail-row"><span class="detail-label">Method</span><span class="detail-value">{{ $payment_log->payment_method ?? 'Not recorded' }}</span></div>
                                    <div class="detail-row"><span class="detail-label">Tracking code</span><span class="detail-value">{{ $payment_log->tracking_code ?? 'Not recorded' }}</span></div>
                                    <div class="detail-row"><span class="detail-label">Amount</span><span class="detail-value">{{ $group['amount_display'] }}</span></div>
                                </div>
                            </div>
                            <div class="detail-section">
                                <div class="detail-section-title">Receipt</div>
                                <div class="detail-section-card">
                                    <div class="detail-row"><span class="detail-label">File</span><span class="detail-value"><a href="#" class="receipt-link1" data-receipt="{{ route('admin.receipts.show', $payment_log->payment_id) }}">View Receipt</a></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            <div class="payment-pagination">
                {{ $payment_groups->links() }}
            </div>
        </div>
    </div>

    <div id="paymentInfoModal" class="modal vs-admin-payment-logs__modal vs-admin-payment-logs__info-modal" aria-hidden="true">
        <div class="modal-content modal-lg payment-info-modal" role="dialog" aria-modal="true" aria-labelledby="paymentInfoTitle">
            <button type="button" class="close-btn js-modal-close" aria-label="Close payment information">&times;</button>
            <h2 id="paymentInfoTitle">Understanding payment totals</h2>
            <p class="payment-info-intro">Reservation totals and payment progress are calculated from recorded amounts.</p>
            <dl class="payment-info-list">
                <div>
                    <dt>Total</dt>
                    <dd>The reservation price set by an admin.</dd>
                </div>
                <div>
                    <dt>Paid so far</dt>
                    <dd>The sum of approved payments with recorded amounts. Pending, rejected, refunded, and unrecorded payments do not count.</dd>
                </div>
                <div>
                    <dt>Remaining</dt>
                    <dd>The reservation total minus paid so far.</dd>
                </div>
                <div>
                    <dt>Confirmation</dt>
                    <dd>Downpayment received means approved payments cover the default 25% downpayment. Fully paid means the remaining balance is zero.</dd>
                </div>
                <div>
                    <dt>Pending</dt>
                    <dd>The number of payment submissions waiting for admin review. Expand a reservation to review receipts and update individual payment statuses.</dd>
                </div>
            </dl>
        </div>
    </div>

    <div id="receiptModal" class="modal vs-admin-payment-logs__modal vs-admin-payment-logs__receipt-modal">
        <div class="modal-content">
            <span class="close-btn" id="closeReceiptModal">&times;</span>
            <h2>Payment Receipt</h2>
            <div class="vs-admin-payment-logs__receipt-content">
                <img id="receiptImage1" src="" alt="Receipt Image" class="receipt-image" hidden>
                <iframe id="receiptFrame1" src="" class="vs-admin-payment-logs__receipt-frame" title="Receipt PDF" hidden></iframe>
                <p id="receiptMissing" class="text-muted" hidden>No receipt available.</p>
            </div>
        </div>
    </div>

    <div id="statusModal" class="modal vs-admin-payment-logs__modal vs-admin-payment-logs__status-modal">
        <div class="modal-content">
            <span class="close-btn" id="closeStatusModal">&times;</span>
            <h2 id="statusModalTitle">Confirm Status Change</h2>
            <p id="statusModalMessage" class="vs-admin-payment-logs__status-message"></p>
            <div id="statusModalNoteWrapper" class="vs-admin-payment-logs__status-note" hidden>
                <label for="statusModalNote" class="vs-admin-payment-logs__status-note-label">Reason (required for a refund, included in the email):</label>
                <textarea id="statusModalNote" class="vs-admin-payment-logs__status-note-input" rows="3" placeholder="Explain the decision..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="admin-btn admin-btn-ghost" id="cancelStatusModal">Cancel</button>
                <button type="button" class="admin-btn admin-btn-primary" id="confirmStatusModal">Confirm</button>
            </div>
        </div>
    </div>

    @vite('resources/js/admin/payment-logs.js')
@endsection
