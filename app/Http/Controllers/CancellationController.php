<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inquiry;
use App\Models\Reservation;
use App\Models\CancellationRequest;
use App\Support\IdentityMatch;
use App\Support\PatronAudit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CancellationController extends Controller
{
    /**
     * One answer for every failed lookup or submit.
     *
     * Saying which half was wrong (unknown code, wrong email) would turn the
     * form into a way to test whether a tracking code exists, so a mismatch of
     * code, email, ownership or state all collapse into this sentence.
     */
    private const GENERIC_FAILURE = 'Those details do not match an active reservation. Please check the code and email address.';

    /** Failed lookups allowed per tracking code plus IP, per hour. */
    private const FAILED_LOOKUPS_PER_HOUR = 5;

    /**
     * Show the cancellation request form.
     */
    public function index()
    {
        return view('patron.cancel-reservation');
    }

    /**
     * Look up a reservation the caller owns and return the safe summary fields.
     *
     * The response carries no patron name and no email address: the page never
     * needs them, and echoing them back would hand a stranger the owner's
     * details.
     */
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'tracking_code' => 'required|string|max:32',
            'email'         => 'required|email|max:255',
        ]);

        $throttleKey = $this->failedLookupKey($validated['tracking_code'], $request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::FAILED_LOOKUPS_PER_HOUR)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many failed lookups for this code. Please try again later.',
            ], 429);
        }

        $owned = $this->resolveOwnedReservation($validated['tracking_code'], $validated['email']);

        if (! $owned) {
            RateLimiter::hit($throttleKey, 3600);

            return response()->json([
                'success' => false,
                'message' => self::GENERIC_FAILURE,
            ]);
        }

        ['inquiry' => $inquiry, 'reservation' => $reservation] = $owned;

        if (! $reservation) {
            return response()->json([
                'success' => false,
                'message' => 'This inquiry has not been confirmed as a reservation yet. Please contact us for assistance.',
            ]);
        }

        if ($reservation->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'This reservation has already been cancelled.',
            ]);
        }

        // A pending request is a real state, not a failed lookup, so it is
        // reported as itself rather than through the generic message.
        $pending = CancellationRequest::where('reserve_id', $reservation->reserve_id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return response()->json([
                'success' => false,
                'message' => 'A cancellation request for this reservation is already pending. Please wait for admin approval.',
            ]);
        }

        return response()->json([
            'success' => true,
            'reservation' => [
                'date'    => $reservation->date ?? $inquiry->date,
                'venue'   => $reservation->venue ?? $inquiry->venue,
                'package' => $reservation->event_type ?? $inquiry->event_type,
                'status'  => $inquiry->status ?? 'Pending',
            ],
        ]);
    }

    /**
     * Submit a cancellation request.
     *
     * The reservation is identified by tracking code plus the email it was
     * booked with. The numeric reserve_id the browser happens to hold is never
     * trusted on its own, so guessing an id cannot cancel someone else's event.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'tracking_code' => 'required|string|max:32',
            'patron_email'  => 'required|email|max:255',
            'reason'        => 'nullable|string|max:1000',
        ]);

        $owned = $this->resolveOwnedReservation($validated['tracking_code'], $validated['patron_email']);

        if (! $owned) {
            return response()->json([
                'success' => false,
                'message' => self::GENERIC_FAILURE,
            ]);
        }

        ['inquiry' => $inquiry, 'reservation' => $reservation] = $owned;

        if (! $reservation || $reservation->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'This reservation is no longer active.',
            ]);
        }

        // Check for existing pending request
        $existing = CancellationRequest::where('reserve_id', $reservation->reserve_id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A cancellation request is already pending for this reservation.',
            ]);
        }

        try {
            CancellationRequest::create([
                'reserve_id'   => $reservation->reserve_id,
                'patron_email' => $validated['patron_email'],
                'reason'       => $validated['reason'] ?? null,
                'status'       => 'pending',
            ]);

            PatronAudit::log(
                'Patron Cancellation Requested',
                $request,
                $inquiry->tracking_code,
                'cancellation request submitted for reservation #' . $reservation->reserve_id,
                $inquiry->inquiry_id,
                $reservation->reserve_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Your cancellation request has been submitted. An admin will review it shortly.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to submit cancellation request: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit cancellation request. Please try again.',
            ]);
        }
    }

    /**
     * Resolve "code + email" to the inquiry and reservation they own.
     *
     * Returns null when the code is unknown, the inquiry has no patron, or the
     * submitted email does not match the booking email.
     *
     * @return array{inquiry: Inquiry, reservation: Reservation|null}|null
     */
    private function resolveOwnedReservation(string $code, string $email): ?array
    {
        $inquiry = Inquiry::with('patron')->where('tracking_code', trim($code))->first();

        if (! $inquiry || ! IdentityMatch::emailMatches($inquiry->patron->email ?? null, $email)) {
            return null;
        }

        return [
            'inquiry' => $inquiry,
            'reservation' => Reservation::where('inquiry_id', $inquiry->inquiry_id)->first(),
        ];
    }

    /**
     * Rate limit key for failed lookups: one bucket per tracking code and IP.
     */
    private function failedLookupKey(string $code, Request $request): string
    {
        return 'cancel-lookup-failed:' . sha1(mb_strtolower(trim($code)) . '|' . (string) $request->ip());
    }
}
