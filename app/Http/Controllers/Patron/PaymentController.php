<?php

namespace App\Http\Controllers\Patron;

use App\Http\Controllers\Controller;
use App\Support\FeedbackInput;
use App\Support\IdentityMatch;
use App\Support\PatronAudit;
use App\Support\TrackingCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use App\Models\PaymentSetting;
use App\Models\Inquiry;
use App\Models\Reservation;
use App\Models\Payment;

class PaymentController extends Controller
{
    /**
     * One answer for every verification failure (unknown code, wrong email,
     * cancelled reservation, nothing left to pay). Saying which check failed
     * would let the endpoint be used to test whether a code exists.
     */
    private const GENERIC_FAILURE = 'We could not match that reservation code and email address to a reservation waiting for payment. Please check both and try again.';

    /** Failed upload attempts allowed per code plus IP, per hour. */
    private const FAILED_ATTEMPTS_PER_HOUR = 5;

    public function index()
    {
        return view('patron.payment');
    }

    public function bankQr()
    {
        return $this->streamQr('bank_qr_path', 'bank-qr');
    }

    public function gcashQr()
    {
        return $this->streamQr('gcash_qr_path', 'gcash-qr');
    }

    private function streamQr(string $settingKey, string $directory)
    {
        $path = PaymentSetting::get($settingKey);
        if (! is_string($path) || ! preg_match('#^' . preg_quote($directory, '#') . '/[^/]+$#', $path)) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $mimeType = $disk->mimeType($path);
        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            abort(404);
        }

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //         'tracking_code' => 'required|string',
    //         'reference_code' => 'nullable|string|max:100',
    //         'receipt' => 'required|mimes:jpg,jpeg,png,pdf|max:2048',
    //     ]);

    //     $inquiry = Inquiry::where('tracking_code', $request->tracking_code)
    //         ->whereHas('patron', fn($q) => $q->where('email', $request->email))
    //         ->first();

    //     if (!$inquiry) {
    //         return back()->with('error', 'Invalid email or tracking code.');
    //     }

    //     $reservation = Reservation::where('inquiry_id', $inquiry->id)->first();

    //     if (!$reservation) {
    //         return back()->with('error', 'No reservation found for this tracking code.');
    //     }

    //     $path = $request->file('receipt')->store('payments', 'public');

    //     Payment::create([
    //         'reserve_id' => $reservation->id,
    //         'reference_code' => $request->reference_code,
    //         'receipt_path' => $path,
    //         'status' => 'pending',
    //     ]);

    //     return back()->with('success', 'Receipt uploaded successfully. Your payment is now under review.');
    // }

    public function store(Request $request)
    {
        // Clean before validating: control characters are stripped from the
        // name first, so they cannot be used to pad a value past the length
        // limit or to hide a blank name inside a "present" string.
        $request->merge([
            'full_name' => FeedbackInput::cleanLine((string) $request->input('full_name')),
        ]);

        $validated = $request->validate([
            'full_name' => 'required|string|min:2|max:120',
            'payment_type' => 'required|in:full,half',
            'payment_method' => 'required|in:cash,gcash,bank',
            'email' => 'required|email|max:255',
            'reservation_code' => 'required|string|max:32',
            'receipt' => ['required', 'file', 'image', 'mimetypes:image/jpeg,image/png', 'max:5120'],
        ]);

        // Failed uploads are counted per code + IP, not per IP alone: a patron
        // who mistypes their own code cannot lock out everyone behind the same
        // NAT, while probing one code for a matching address still runs out.
        $failedKey = $this->failedAttemptKey($validated['reservation_code'], $request);

        if (RateLimiter::tooManyAttempts($failedKey, self::FAILED_ATTEMPTS_PER_HOUR)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts for this reservation code. Please try again later.',
            ], 429);
        }

        // The code must resolve to a reservation that is still active and still
        // waiting for payment, and the submitted email must be the booking
        // email. Every one of those failures answers with the same sentence, so
        // the endpoint cannot be used to tell "code unknown" from "email wrong".
        $code = trim($validated['reservation_code']);
        $inquiry = Inquiry::with('patron')->where('tracking_code', $code)->first();
        $reservation = Reservation::where('inquiry_id', $inquiry->inquiry_id ?? 0)->first();

        $emailMatches = IdentityMatch::emailMatches($inquiry->patron->email ?? null, $validated['email']);

        // A reservation is still waiting for money while anything remains
        // unpaid. Once a total is agreed, a downpayment does not close the
        // door on the balance; without a total only the first receipt can be
        // taken, because there is nothing to measure a second one against.
        $awaitingPayment = false;

        if ($reservation !== null && $reservation->status === 'active') {
            if ($reservation->total_amount === null) {
                $awaitingPayment = ! Payment::where('inquiry_id', $inquiry->inquiry_id)
                    ->where('status', 'approved')
                    ->exists();
            } else {
                $approved = Payment::where('inquiry_id', $inquiry->inquiry_id)
                    ->where('status', 'approved')
                    ->whereNotNull('amount')
                    ->selectRaw('CAST(COALESCE(SUM(amount), 0) AS CHAR) AS approved_total')
                    ->value('approved_total');
                $remaining = bcsub(
                    bcadd((string) $reservation->total_amount, '0', 2),
                    bcadd((string) $approved, '0', 2),
                    2
                );
                $awaitingPayment = bccomp($remaining, '0.00', 2) > 0;
            }
        }

        if (! $inquiry || ! $reservation || ! $emailMatches || ! $awaitingPayment) {
            RateLimiter::hit($failedKey, 3600);

            return response()->json([
                'success' => false,
                'message' => self::GENERIC_FAILURE,
            ], 422);
        }

        $trackingCode = TrackingCode::unique(
            fn (string $code) => Payment::query()->where('tracking_code', $code)->exists()
        );

        // Store the receipt on the Railway volume, which is mounted at
        // /var/www/storage/app/public (the root of the "public" disk), so
        // uploads are fast (same server) and survive redeploys. R2 and the
        // private local disk remain as fallbacks. Note: the public disk is
        // served through the public/storage symlink, so a receipt there is
        // fetchable by URL if the random file name leaks.
        //
        // NOTE: every disk here is configured with 'throw' => false, so a
        // failed write RETURNS false instead of throwing, check the result
        // and move to the next disk instead of trusting try/catch alone.
        $receiptPath = null;
        foreach (['public', 'r2', 'local'] as $diskName) {
            try {
                $stored = $request->file('receipt')->store('receipts', $diskName);
            } catch (\Throwable $e) {
                Log::warning("Receipt upload failed on disk [{$diskName}]: " . $e->getMessage());
                continue;
            }

            if (is_string($stored) && $stored !== '') {
                $receiptPath = $stored;
                break;
            }
        }

        if (! $receiptPath) {
            return response()->json([
                'success' => false,
                'message' => 'We could not save your receipt. Please try again.',
            ], 500);
        }

        $payment = Payment::create([
            'full_name' => $validated['full_name'],
            'payment_type' => $validated['payment_type'],
            'payment_method' => $validated['payment_method'],
            'tracking_code' => $trackingCode,
            'reservation_code' => $validated['reservation_code'],
            'inquiry_id' => $inquiry->inquiry_id,
            'receipt_path' => $receiptPath,
            'email' => $validated['email'],
            'status' => 'pending',
        ]);

        // Audit the upload without recording anything about the money: no
        // amount, no method, no receipt path. Failed verifications and throttled
        // attempts never reach this point.
        PatronAudit::log(
            'Patron Receipt Uploaded',
            $request,
            $inquiry->tracking_code,
            'payment receipt uploaded for reservation #' . $inquiry->inquiry_id,
            $inquiry->inquiry_id
        );

        return response()->json([
            'success' => true,
            'tracking_code' => $trackingCode,
        ]);
    }

    /**
     * Rate limit key for failed uploads: one bucket per code and IP.
     */
    private function failedAttemptKey(string $code, Request $request): string
    {
        return 'payment-submit-failed:' . sha1(mb_strtolower(trim($code)) . '|' . (string) $request->ip());
    }
}
