<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use App\Services\AiReplyService;

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
                'tracking_code' => $reservation->inquiry->tracking_code ?? 'RSV-' . str_pad($reservation->reserve_id, 6, '0', STR_PAD_LEFT),
                'date' => $reservation->date,
                'time' => $reservation->time,
                'venue' => $reservation->venue ?? 'N/A',
                'event_type' => $reservation->event_type ?? 'N/A',
                'theme_motif' => $reservation->theme_motif ?? 'N/A',
                'message' => $reservation->message ?? 'N/A',
                'status' => $reservation->status ?? 'pending',
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
            $reservation->delete();

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

            $context = [
                'client_name'    => $reservation->patron->name ?? 'Not specified',
                'client_email'   => $reservation->patron->email ?? '',
                'client_contact' => $reservation->patron->contact_number ?? '',
                'tracking_code'  => $inquiry->tracking_code ?? null,
                'event_type'     => $eventType ?? '',
                'venue'          => $venue ?? '',
                'theme_motif'    => $theme ?? '',
                'date'           => $reservation->date ? \Illuminate\Support\Carbon::parse($reservation->date)->format('F j, Y') : '',
                'time'           => $reservation->time ?? '',
                'status'         => $reservation->status ?? '',
                'message'        => $reservation->message ?? '',
                'form_data'      => $reservation->form_data ?? [],
            ];

            $brief = AiReplyService::generateEventBrief($context);

            return response()->json(['success' => true, 'brief' => $brief]);
        } catch (\Throwable $e) {
            Log::error('AI event brief failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
