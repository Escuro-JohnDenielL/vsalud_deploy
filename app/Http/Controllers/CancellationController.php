<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inquiry;
use App\Models\Reservation;
use App\Models\CancellationRequest;
use Illuminate\Support\Facades\Log;

class CancellationController extends Controller
{
    /**
     * Show the cancellation request form.
     */
    public function index()
    {
        return view('patron.cancel-reservation');
    }

    /**
     * Look up reservation by tracking code and return details.
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'tracking_code' => 'required|string',
        ]);

        $code = $request->input('tracking_code');

        $inquiry = Inquiry::with('patron')->where('tracking_code', $code)->first();

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'No reservation found with that code.',
            ]);
        }

        // Find linked reservation
        $reservation = Reservation::where('inquiry_id', $inquiry->inquiry_id)->first();

        if (!$reservation) {
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

        // Check if there's already a pending cancellation request
        $existingRequest = CancellationRequest::where('reserve_id', $reservation->reserve_id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A cancellation request for this reservation is already pending. Please wait for admin approval.',
            ]);
        }

        return response()->json([
            'success' => true,
            'reservation' => [
                'reserve_id'    => $reservation->reserve_id,
                'tracking_code' => $inquiry->tracking_code,
                'patron_name'   => $inquiry->patron->name ?? 'N/A',
                'patron_email'  => $inquiry->patron->email ?? '',
                'date'          => $reservation->date ?? $inquiry->date,
                'time'          => $reservation->time ?? $inquiry->time,
                'venue'         => $reservation->venue ?? $inquiry->venue,
                'event_type'    => $reservation->event_type ?? $inquiry->event_type,
                'theme_motif'   => $reservation->theme_motif ?? $inquiry->theme_motif,
            ],
        ]);
    }

    /**
     * Submit a cancellation request.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'reserve_id'    => 'required|exists:reservation,reserve_id',
            'patron_email'  => 'required|email',
            'reason'        => 'nullable|string|max:1000',
        ]);

        $reservation = Reservation::find($request->reserve_id);

        if (!$reservation || $reservation->status === 'cancelled') {
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
                'patron_email' => $request->patron_email,
                'reason'       => $request->reason,
                'status'       => 'pending',
            ]);

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
}
