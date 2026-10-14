<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentApproved;
use App\Mail\PaymentRefunded;
use App\Mail\PaymentRejected;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\AuditNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentStatusController extends Controller
{
    public function update(Request $request, Payment $payment)
    {
        // Refunding money is the one status change that always needs a written
        // reason. Approving or rejecting keeps the note optional, as before.
        $isRefund = $request->input('status') === 'refunded';

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,refunded',
            'admin_note' => $isRefund
                ? AuditNote::requiredRules()
                : AuditNote::optionalRules(),
        ]);

        $newStatus = $validated['status'];
        $mailSent = true;

        try {
            $result = DB::transaction(function () use ($payment, $newStatus, $validated) {
                $lockedPayment = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
                $previousStatus = $lockedPayment->status;

                if ($newStatus === 'approved' && $previousStatus !== 'approved'
                    && $lockedPayment->inquiry_id && $lockedPayment->amount !== null) {
                    $reservation = Reservation::where('inquiry_id', $lockedPayment->inquiry_id)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation && $reservation->total_amount !== null) {
                        $paid = Payment::where('inquiry_id', $lockedPayment->inquiry_id)
                            ->where('status', 'approved')
                            ->whereNotNull('amount')
                            ->selectRaw('CAST(COALESCE(SUM(amount), 0) AS CHAR) AS approved_total')
                            ->value('approved_total');
                        $paid = bcadd((string) $paid, '0', 2);
                        $nextPaid = bcadd($paid, (string) $lockedPayment->amount, 2);

                        if (bccomp($nextPaid, (string) $reservation->total_amount, 2) > 0) {
                            return ['blocked' => true];
                        }
                    }
                }

                $lockedPayment->status = $newStatus;
                $lockedPayment->admin_note = $validated['admin_note'] ?? $lockedPayment->admin_note;
                $lockedPayment->save();

                return [
                    'blocked' => false,
                    'payment' => $lockedPayment,
                    'previous_status' => $previousStatus,
                ];
            });

            if ($result['blocked']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Approving this payment would exceed the reservation total.',
                ], 422);
            }

            $payment = $result['payment'];
            $previousStatus = $result['previous_status'];

            // Record the change with its reason. Writing the entry here, instead
            // of leaving the generic request entry from the middleware to stand,
            // is what puts the refund reason in the audit trail.
            if ($previousStatus !== $newStatus) {
                $admin = Auth::guard('admin')->user();
                $name = trim((string) ($admin->f_name ?? '') . ' ' . (string) ($admin->l_name ?? ''))
                    ?: (string) ($admin->email ?? 'Admin');

                ActivityLog::create([
                    'admin_id'      => $admin?->getAuthIdentifier() ?? 0,
                    'activity_type' => 'Payment ' . ucfirst($newStatus),
                    'category'      => 'data',
                    'route_name'    => 'admin.payments.status',
                    'http_method'   => $request->method(),
                    'subject'       => 'Payment #' . $payment->getKey(),
                    'description'   => "Admin {$name} set payment #{$payment->getKey()} to {$newStatus} (was {$previousStatus})."
                        . AuditNote::describe($validated['admin_note'] ?? null),
                    'ip_address'    => $request->ip(),
                    'user_agent'    => substr((string) $request->userAgent(), 0, 500),
                ]);
            }

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
