<?php

namespace App\Http\Controllers\Patron;

use App\Http\Controllers\Controller;
use App\Support\IdentityMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\PaymentSetting;
use App\Models\Inquiry;
use App\Models\Reservation;
use App\Models\Payment;

class PaymentController extends Controller
{
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
        $validated = $request->validate([
            'full_name' => 'required|string|min:2',
            'payment_type' => 'required|in:full,half',
            'payment_method' => 'required|in:cash,gcash,bank',
            'email' => 'required|email',
            'reservation_code' => 'required|string|exists:inquiry,tracking_code',
            'receipt' => ['required', 'file', 'image', 'mimetypes:image/jpeg,image/png', 'max:5120'],
        ]);

        // The reservation code and the email must identify the same reservation.
        // A code that matches nothing is refused here rather than stored as an
        // orphan payment, and the email match is trimmed and case-insensitive.
        $inquiry = Inquiry::with('patron')->where('tracking_code', trim((string) $validated['reservation_code']))->first();

        if (! $inquiry || ! IdentityMatch::emailMatches($inquiry->patron->email ?? null, $validated['email'])) {
            return response()->json([
                'success' => false,
                'message' => 'The reservation code does not match the provided email address.',
            ], 422);
        }

        $trackingCode = 'VS-' . substr(time(), -6) . '-' . rand(1000, 9999);

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
            'inquiry_id' => $inquiry?->inquiry_id,
            'receipt_path' => $receiptPath,
            'email' => $validated['email'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'tracking_code' => $trackingCode,
        ]);
    }
}
