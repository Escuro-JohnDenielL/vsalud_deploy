<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentApproved;
use App\Mail\PaymentRefunded;
use App\Mail\PaymentRejected;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentStatusController extends Controller
{
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,refunded',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $newStatus = $validated['status'];
        $previousStatus = $payment->status;
        $mailSent = true;

        try {
            // Save first so the status and note are persisted even if the email fails.
            $payment->status = $newStatus;
            $payment->admin_note = $validated['admin_note'] ?? $payment->admin_note;
            $payment->save();

            // Only notify the patron on a real change to a notifiable status.
            $notifiable = ['approved', 'rejected', 'refunded'];

            if ($previousStatus !== $newStatus && in_array($newStatus, $notifiable, true)) {
                $mailableClass = [
                    'approved' => PaymentApproved::class,
                    'rejected' => PaymentRejected::class,
                    'refunded' => PaymentRefunded::class,
                ][$newStatus];

                $emailData = [
                    'name'             => $payment->full_name,
                    'tracking_code'    => $payment->tracking_code ?? 'N/A',
                    'reservation_code' => $payment->reservation_code ?? 'N/A',
                    'payment_type'     => $payment->payment_type ?? 'N/A',
                    'payment_method'   => $payment->payment_method ?? 'N/A',
                    'admin_note'       => $validated['admin_note'] ?? null,
                ];

                try {
                    Mail::to($payment->email)->send(new $mailableClass($emailData));
                } catch (\Exception $mailError) {
                    Log::error('Failed to send payment ' . $newStatus . ' email: ' . $mailError->getMessage());
                    $mailSent = false;
                }
            }

            return response()->json([
                'success' => true,
                'mail_sent' => $mailSent,
                'message' => 'Payment status updated to ' . $newStatus . '.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update payment status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment status. Please try again.',
            ]);
        }
    }
}
