<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Csv;
use App\Support\PaymentAmount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\AiReplyService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationController extends Controller
{
    public function showReservationLogs()
    {
        $reservations = Reservation::with(['patron', 'inquiry'])
            ->select('*')
            ->latest()
            ->paginate(10);

        return view('admin.reserve-logs', compact('reservations'));
    }

    /**
     * Download every reservation log as CSV. Uses the same formula-safe writer
     * as the audit-log export, and records the export in the audit trail.
     */
    public function exportLogs(): StreamedResponse
    {
        $filename = 'reservation-logs-' . now()->format('Y-m-d-His') . '.csv';

        $admin = Auth::guard('admin')->user();
        $name = trim((string) ($admin->f_name ?? '') . ' ' . (string) ($admin->l_name ?? ''))
            ?: (string) ($admin->email ?? 'Admin');

        ActivityLog::create([
            'admin_id'      => $admin?->getAuthIdentifier() ?? 0,
            'activity_type' => 'Reservation Logs Exported',
            'category'      => 'data',
            'route_name'    => 'admin.reserve-logs.export',
            'http_method'   => 'GET',
            'subject'       => 'Reservation logs CSV',
            'description'   => "Admin {$name} exported the reservation logs as CSV.",
            'ip_address'    => request()->ip(),
            'user_agent'    => substr((string) request()->userAgent(), 0, 500),
        ]);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            // BOM so Excel opens the file as UTF-8.
            fwrite($out, "\xEF\xBB\xBF");

            $this->putCsvRow($out, [
                'Tracking code', 'Name', 'Event date', 'Venue', 'Event type', 'Status', 'Total',
            ]);

            Reservation::with(['patron', 'inquiry'])
                ->latest()
                ->limit(10000)
                ->cursor()
                ->each(function (Reservation $reservation) use ($out) {
                    $this->putCsvRow($out, [
                        Csv::neutralize($reservation->inquiry?->tracking_code ?? 'None'),
                        Csv::neutralize($reservation->patron->name ?? ''),
                        Csv::neutralize($reservation->date),
                        Csv::neutralize($reservation->venue),
                        Csv::neutralize($reservation->event_type),
                        Csv::neutralize($reservation->status),
                        // Numeric column: never neutralized.
                        $reservation->total_amount === null
                            ? ''
                            : number_format((float) $reservation->total_amount, 2, '.', ''),
                    ]);
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function putCsvRow($handle, array $row): void
    {
        fputcsv($handle, $row, ',', '"', '');
    }

    public function getReservation($id)
    {
        try {
            $reservation = \App\Models\Reservation::with(['patron', 'inquiry'])->find($id);

            if (!$reservation) {
                return response()->json([
                    'error' => true,
                    'message' => "Reservation #{$id} not found"
                ], 404);
            }

            // Make sure all required fields are available
            $responseData = [
                'id' => $reservation->reserve_id,
                'tracking_code' => $reservation->inquiry?->tracking_code,
                'date' => $reservation->date,
                'time' => $reservation->time,
                'venue' => $reservation->venue ?? 'N/A',
                'event_type' => $reservation->event_type ?? 'N/A',
                'theme_motif' => $reservation->theme_motif ?? 'N/A',
                'message' => $reservation->message ?? 'N/A',
                'status' => $reservation->status ?? 'pending',
                'total_amount' => $reservation->total_amount,
                'paid_amount' => $reservation->total_amount === null
                    ? null
                    : $this->approvedPaidAmount($reservation->inquiry_id),
                'patron' => [
                    'name' => $reservation->patron->name ?? 'N/A',
                    'email' => $reservation->patron->email ?? 'N/A'
                ]
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Error fetching reservation: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Unable to fetch reservation details'
            ], 500);
        }
    }

    public function updateTotal(Request $request, $id)
    {
        $validated = $request->validate([
            'total_amount' => ['required', 'string', 'regex:/^\\d{1,8}(?:\\.\\d{1,2})?$/'],
            'confirm' => ['sometimes', 'boolean'],
        ]);

        $total = bcadd($validated['total_amount'], '0', 2);
        if (bccomp($total, '0.00', 2) <= 0) {
            return response()->json(['success' => false, 'message' => 'Reservation total must be positive.'], 422);
        }

        $confirmed = $request->boolean('confirm');

        $result = DB::transaction(function () use ($id, $total, $confirmed) {
            $reservation = Reservation::whereKey($id)->lockForUpdate()->firstOrFail();

            // Approved receipts recorded before a total was agreed carry no
            // amount (there was nothing to measure them against). Their value
            // is only a suggestion until the admin confirms it: never write it
            // silently.
            $unvalued = Payment::where('inquiry_id', $reservation->inquiry_id)
                ->where('status', 'approved')
                ->whereNull('amount')
                ->get();

            $preview = [];
            $derived = '0.00';

            foreach ($unvalued as $payment) {
                $amount = PaymentAmount::forType($payment->payment_type, $total);

                if ($amount !== null) {
                    $preview[] = [
                        'payment_id' => $payment->payment_id,
                        'payment_type' => $payment->payment_type,
                        'amount' => $amount,
                    ];
                    $derived = bcadd($derived, $amount, 2);
                }
            }

            $paid = bcadd($this->approvedPaidAmount($reservation->inquiry_id), $derived, 2);

            if (bccomp($total, $paid, 2) < 0) {
                return ['blocked' => true];
            }

            if ($preview !== [] && ! $confirmed) {
                return [
                    'needs_confirmation' => true,
                    'affected_count' => count($preview),
                    'amounts' => $preview,
                ];
            }

            foreach ($unvalued as $payment) {
                $amount = PaymentAmount::forType($payment->payment_type, $total);

                if ($amount !== null) {
                    $payment->amount = $amount;
                    $payment->save();
                }
            }

            $reservation->total_amount = $total;
            $reservation->save();

            return ['blocked' => false];
        });

        if ($result['blocked'] ?? false) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation total cannot be less than approved payments.',
            ], 422);
        }

        if ($result['needs_confirmation'] ?? false) {
            return response()->json([
                'success' => false,
                'needs_confirmation' => true,
                'affected_count' => $result['affected_count'],
                'amounts' => $result['amounts'],
                'message' => $result['affected_count']
                    . ' approved payment(s) have no recorded amount. Confirm to record them.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'total_amount' => $total,
            'message' => 'Reservation total saved.',
        ]);
    }

    private function approvedPaidAmount($inquiryId): string
    {
        $paid = \App\Models\Payment::where('inquiry_id', $inquiryId)
            ->where('status', 'approved')
            ->whereNotNull('amount')
            ->selectRaw('CAST(COALESCE(SUM(amount), 0) AS CHAR) AS approved_total')
            ->value('approved_total');

        return bcadd((string) $paid, '0', 2);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:active,cancelled,completed',
        ]);

        try {
            $reservation = Reservation::findOrFail($id);
            $reservation->status = $request->input('status');
            $reservation->save();

            // Also update the linked inquiry status if cancelling
            if ($request->input('status') === 'cancelled' && $reservation->inquiry) {
                $inquiry = $reservation->inquiry;
                $inquiry->status = 'Cancelled';
                $inquiry->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Reservation status updated successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating reservation status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update reservation status.',
            ], 500);
        }
    }

    public function deleteReservation($id)
    {
        try {
            $reservation = \App\Models\Reservation::findOrFail($id);

            // A deleted active reservation frees its slot exactly like an
            // approved cancellation does, so the waitlist has to be offered it.
            $freedDate = null;

            if ($reservation->status === 'active' && $reservation->date) {
                $freedDate = \Illuminate\Support\Carbon::parse($reservation->date)->format('Y-m-d');
            }

            $reservation->delete();

            if ($freedDate !== null) {
                try {
                    app(\App\Services\WaitlistService::class)->notifyNextForDate($freedDate);
                } catch (\Throwable $waitlistError) {
                    Log::error('Failed to offer the freed slot to the waitlist: ' . $waitlistError->getMessage());
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error deleting reservation: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Unable to delete reservation'
            ], 500);
        }
    }

    /**
     * Generate an AI-assisted "Event Brief" for a reservation (Google Gemini).
     *
     * Summarizes the booking + dynamic booking-form answers into a short internal
     * handoff brief the admin / coordinator team can read at a glance. Mirrors the
     * inquiry "AI Draft Reply" flow (same service, throttled like draft-reply).
     */
    public function aiBrief(Request $request, $id)
    {
        try {
            $reservation = Reservation::with(['patron', 'inquiry'])->find($id);

            if (! $reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found.'], 404);
            }

            $inquiry = $reservation->inquiry;

            // Resolve "Others" values from the linked inquiry's *_other columns.
            $venue = $reservation->venue;
            $eventType = $reservation->event_type;
            $theme = $reservation->theme_motif;

            if ($inquiry) {
                if (($venue ?? '') === 'Others') {
                    $venue = $inquiry->other_venue ?? $venue;
                }
                if (($eventType ?? '') === 'Others') {
                    $eventType = $inquiry->other_event_type ?? $eventType;
                }
                if (($theme ?? '') === 'Others') {
                    $theme = $inquiry->other_theme_motif ?? $theme;
                }
            }

            // Only the fields the brief needs are sent. Email, contact number,
            // tracking code and the rest of the booking form are excluded.
            $context = [
                'client_name'  => $reservation->patron->name ?? 'Not specified',
                'event_type'   => $eventType ?? '',
                'venue'        => $venue ?? '',
                'theme_motif'  => $theme ?? '',
                'date'         => $reservation->date ? \Illuminate\Support\Carbon::parse($reservation->date)->format('F j, Y') : '',
                'time'         => $reservation->time ?? '',
                'status'       => $reservation->status ?? '',
                'message'      => $reservation->message ?? '',
            ];

            $brief = AiReplyService::generateEventBrief($context);

            return response()->json(['success' => true, 'brief' => $brief]);
        } catch (\Throwable $e) {
            // Generic on purpose: a provider error can echo the prompt, which
            // would put patron data in the log and in the response.
            Log::error('AI event brief failed', ['type' => $e::class]);

            return response()->json([
                'success' => false,
                'message' => 'The AI service is unavailable right now. Please try again.',
            ], 500);
        }
    }
}
