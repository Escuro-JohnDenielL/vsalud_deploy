<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

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
                'id' => $reservation->id,
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
}
