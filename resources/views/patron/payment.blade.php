@extends('layouts.patron')

@section('title', 'Submit Payment')

@push('styles')
    @vite('resources/css/patron/payment.css')
@endpush

@section('content')
    <div class="payment-wrapper">
        <div class="payment-container">
            <div class="payment-header">
                <h2>Submit Proof of Payment</h2>
                <p>Please complete the form and upload your payment receipt.</p>
            </div>

            <div id="orderForm">
                <div class="form-group">
                    <label for="fullName">Full Name:</label>
                    <input type="text" id="fullName" placeholder="e.g. John Doe">
                    <span id="fullNameError" class="input-error">Full name is required.</span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" placeholder="e.g. johndoe@gmail.com">
                    <span id="emailError" class="input-error">Valid email is required.</span>
                </div>

                <div class="form-group">
                    <label for="reservation_code">Reservation Code:</label>
                    <input type="text" id="reservation_code" placeholder="e.g. VS-12345">
                    <span id="reservation_code_error" class="input-error">Reservation Code is required.</span>
                </div>

                <div class="form-group">
                    <label for="downpaymentType">Payment Type:</label>
                    <select id="downpaymentType">
                        <option value="full">Full Payment</option>
                        <option value="half">25% Downpayment</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="paymentMethod">Payment Method:</label>
                    <select id="paymentMethod">
                        <option value="cash">Cash (Onsite)</option>
                        <option value="gcash">GCash</option>
                        <option value="bank">Bank Transfer (BPI)</option>
                    </select>
                </div>

                <button type="button" id="generateOrder" class="btn-submit">Generate Payment Instructions</button>
            </div>

            <div id="paymentInstructions" style="display: none;">
                <div class="payment-info">
                    <p><strong>Name:</strong> <span id="customerName"></span></p>
                    <p><strong>Tracking Code:</strong> <span id="trackingNumber" class="reference-number"></span></p>
                    <p><strong>Payment Type:</strong> <span class="payment-badge" id="paymentBadge"></span></p>
                </div>

                <div id="gcashInstructions" class="payment-method" style="display: none;">
                    <h3>GCash Payment</h3>
                    <ul class="steps">
                        <li>Scan the QR code below using your GCash app.</li>
                        <li>Account Name: <strong>{{ payment_setting('gcash_account_name', 'Elizabeth R.') }}</strong></li>
                        <li>Mobile Number: <strong>{{ payment_setting('gcash_mobile_number', '09062236120') }}</strong></li>
                        <li>Include your tracking code in the note.</li>
                        <li>Upload a screenshot of your payment below.</li>
                    </ul>
                    <div class="qr-code-container">
                        <img src="{{ asset(payment_setting('gcash_qr_path', 'images/gcash.jpg')) }}" alt="GCash QR Code">
                    </div>
                </div>

                <div id="bankInstructions" class="payment-method" style="display: none;">
                    <h3>Bank Transfer (BPI Only)</h3>
                    <div class="bank-note">Note: BDO is currently unavailable. Please use BPI.</div>
                    <ul class="steps">
                        <li>Bank: <strong>{{ payment_setting('bank_name', 'BPI Savings Bank') }}</strong></li>
                        <li>Account Name: <strong>{{ payment_setting('bank_account_name', 'Ernesto Rafael Jr. and/or Elizabeth Rafael') }}</strong></li>
                        <li>Account Number: <strong>{{ payment_setting('bank_account_number', '8230001538') }}</strong></li>
                        <li>Include your tracking code in the deposit note.</li>
                        <li>Upload a photo of your deposit slip below.</li>
                    </ul>
                </div>

                <div id="cashInstructions" class="payment-method" style="display: none;">
                    <h3>Cash Payment</h3>
                    <ul class="steps">
                        <li>{{ payment_setting('cash_instructions', 'Pay in person at the Villa office.') }}</li>
                        <li>Provide your tracking code at the counter.</li>
                        <li>Upload a photo of your official receipt below.</li>
                    </ul>
                </div>

                <div class="receipt-upload">
                    <form id="receiptForm">
                        <label for="receiptUpload">Upload Receipt (JPG, PNG, PDF):</label>
                        <input type="file" id="receiptUpload" accept="image/*">
                        <span id="receiptError" class="input-error">Please upload your payment receipt.</span>
                        <button type="submit" id="submitReceipt" class="btn-submit">Submit Receipt</button>
                    </form>
                    <button type="button" id="backButton" class="btn-submit"
                        style="background-color:#6c757d;">Back</button>
                    <div class="loader">
                        <div class="loader-spinner"></div>
                    </div>
                </div>
            </div>

            <div id="successMessage" class="success-message">
                <h3>Submission Successful!</h3>
                <p>Thank you. Your receipt has been submitted and is under review.</p>
                <p>We’ll contact <span class="customer-name"></span> once it’s verified.</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/patron/payment.js')
@endpush
